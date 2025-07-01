// Get user ID from global variable
const userId = window.userId || "";
// Get the base path from a global variable that will be set in the view
const basePath = window.basePath || "";
let evtSource = null;
let reconnectAttempts = 0;
let currentCommandId = null;
let reconnectTimer = null;
let processedCommands = new Set(); // Track processed commands to prevent duplicates
let clearInstructionTimeout = null;
let countdownTimeout = null;
let resetCommandTimeout = null; // Add timeout for resetting command display

// --- Microphone Recording & Analysis ---
let micStream = null;
let audioContext = null;
let mediaRecorder = null;
let audioChunks = [];
let micRecordingTimeout = null;
let micIndicator = null;

console.log("Room.js loaded");

// Function to establish SSE connection
function connectSSE() {
  if (evtSource) {
    evtSource.close();
  }

  evtSource = new EventSource(basePath + "/sse");

  evtSource.onopen = function () {
    console.log("SSE connection established");
    reconnectAttempts = 0;
  };

  evtSource.onerror = function (e) {
    console.error("SSE connection error", e);

    evtSource.close();
    evtSource = null;

    if (reconnectAttempts < 10) {
      const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
      console.log(`Attempting to reconnect in ${delay / 1000} seconds...`);

      clearTimeout(reconnectTimer);
      reconnectTimer = setTimeout(() => {
        reconnectAttempts++;
        connectSSE();
      }, delay);
    } else {
      alert("Connection lost. Please refresh the page to reconnect.");
    }
  };

  evtSource.addEventListener("connected", function (event) {
    console.log("Connected to SSE server", JSON.parse(event.data));
  });

  evtSource.addEventListener("heartbeat", function (event) {
    const data = JSON.parse(event.data);
    console.log("Heartbeat received", data);

    if (data && typeof data.activeUsers !== "undefined") {
      document.getElementById("audienceResponders").textContent = data.activeUsers;
    }
  });

  evtSource.addEventListener("command", function (event) {
    const data = JSON.parse(event.data);
    console.log("Command received:", data);

    // Skip if we've already processed this command
    if (!data.id || processedCommands.has(data.id)) {
      console.log(`Skipping duplicate command: ${data.id}`);
      return;
    }

    // Mark this command as processed
    processedCommands.add(data.id);
    currentCommandId = data.id;

    // Limit the size of the processedCommands set to prevent memory issues
    if (processedCommands.size > 50) {
      const iterator = processedCommands.values();
      processedCommands.delete(iterator.next().value);
    }

    // Update the command text with command type
    const commandElement = document.getElementById("commandText");
    commandElement.textContent = data.type;
    
    // Display the admin message if present
    if (data.message) {
      // Check if message element already exists, if not create it
      let messageElement = document.getElementById("adminMessage");
      if (!messageElement) {
        messageElement = document.createElement("p");
        messageElement.id = "adminMessage";
        messageElement.className = "admin-message";
        commandElement.parentNode.insertBefore(messageElement, document.getElementById("countdownDisplay"));
      }
      messageElement.textContent = data.message;
      messageElement.style.display = "block";
    } else {
      // Hide message element if no message
      const existingMessage = document.getElementById("adminMessage");
      if (existingMessage) {
        existingMessage.style.display = "none";
      }
    }

    // Clear any previous timeouts
    if (clearInstructionTimeout) {
      clearTimeout(clearInstructionTimeout);
    }
    if (countdownTimeout) {
      clearTimeout(countdownTimeout);
    }
    if (resetCommandTimeout) {
      clearTimeout(resetCommandTimeout);
    }

    let countdownSeconds = 0;
    if (data.countdown) {
      countdownSeconds = parseInt(data.countdown);
      startCountdown(countdownSeconds);
    } else {
      document.getElementById("countdownDisplay").textContent = "Now!";
    }

    // Always start mic recording after countdown, for the duration
    let duration = (typeof data.duration === "number" && data.duration > 0) ? data.duration : 0;
    
    // Schedule reset of command display after duration + countdown time
    const totalTime = (countdownSeconds + Math.max(duration, 1)) * 1000; // Ensure at least 1 second after countdown
    resetCommandTimeout = setTimeout(() => {
      resetCommandDisplay();
    }, totalTime);
    
    if (duration > 0) {
      setTimeout(() => {
        startMicRecording(duration, data.id, data.type);
      }, countdownSeconds * 1000);
    }
  });
}

// Function to reset command display after command is complete
function resetCommandDisplay() {
  const commandElement = document.getElementById("commandText");
  const countdownElement = document.getElementById("countdownDisplay");
  const messageElement = document.getElementById("adminMessage");
  
  // Reset command text
  commandElement.textContent = "Awaiting next command...";
  
  // Reset countdown display
  countdownElement.textContent = "";
  
  // Hide admin message if present
  if (messageElement) {
    messageElement.style.display = "none";
  }
  
  console.log("Command display reset");
}

// --- Cue Controls ---
function createFlashOverlay() {
  let overlay = document.getElementById('flashCueOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'flashCueOverlay';
    overlay.style.position = 'fixed';
    overlay.style.top = 0;
    overlay.style.left = 0;
    overlay.style.width = '100vw';
    overlay.style.height = '100vh';
    overlay.style.background = 'white';
    overlay.style.opacity = 0;
    overlay.style.zIndex = 9999;
    overlay.style.pointerEvents = 'none';
    overlay.style.transition = 'opacity 0.2s';
    document.body.appendChild(overlay);
  }
  return overlay;
}

function triggerFlash() {
  const overlay = createFlashOverlay();
  overlay.style.opacity = 0.3; 
  setTimeout(() => {
    overlay.style.opacity = 0;
  }, 180); 
}

function triggerBeep() {
  try {
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const o = ctx.createOscillator();
    const g = ctx.createGain();
    o.type = 'sine';
    o.frequency.value = 880;
    g.gain.value = 0.2;
    o.connect(g);
    g.connect(ctx.destination);
    o.start();
    setTimeout(() => {
      o.stop();
      ctx.close();
    }, 150);
  } catch (e) {
    // Ignore errors (e.g., user gesture required)
  }
}

function getCuePrefs() {
  return {
    flash: localStorage.getItem('flashCueEnabled') !== 'false',
    beep: localStorage.getItem('beepCueEnabled') !== 'false',
  };
}

function setCuePrefs(flash, beep) {
  localStorage.setItem('flashCueEnabled', flash);
  localStorage.setItem('beepCueEnabled', beep);
}

function updateCueIcons() {
  const flashBtn = document.getElementById('flashCueToggle');
  const beepBtn = document.getElementById('beepCueToggle');
  const flashIcon = document.getElementById('flashCueIcon');
  const beepIcon = document.getElementById('beepCueIcon');
  const prefs = getCuePrefs();
  if (prefs.flash) {
    flashBtn.classList.add('cue-on');
    flashBtn.classList.remove('cue-off');
    flashIcon.style.opacity = 1;
    flashBtn.style.background = '#ffe066';
    flashBtn.style.border = '2px solid #ffd700';
  } else {
    flashBtn.classList.remove('cue-on');
    flashBtn.classList.add('cue-off');
    flashIcon.style.opacity = 0.4;
    flashBtn.style.background = '#f8f9fa';
    flashBtn.style.border = '2px solid #ccc';
  }
  if (prefs.beep) {
    beepBtn.classList.add('cue-on');
    beepBtn.classList.remove('cue-off');
    beepIcon.style.opacity = 1;
    beepBtn.style.background = '#b2f2ff';
    beepBtn.style.border = '2px solid #38bdf8';
  } else {
    beepBtn.classList.remove('cue-on');
    beepBtn.classList.add('cue-off');
    beepIcon.style.opacity = 0.4;
    beepBtn.style.background = '#f8f9fa';
    beepBtn.style.border = '2px solid #ccc';
  }
}

function setupCueControls() {
  const flashBtn = document.getElementById('flashCueToggle');
  const beepBtn = document.getElementById('beepCueToggle');
  // Set initial state
  updateCueIcons();
  flashBtn.addEventListener('click', () => {
    const prefs = getCuePrefs();
    setCuePrefs(!prefs.flash, prefs.beep);
    updateCueIcons();
  });
  beepBtn.addEventListener('click', () => {
    const prefs = getCuePrefs();
    setCuePrefs(prefs.flash, !prefs.beep);
    updateCueIcons();
  });
}

document.addEventListener('DOMContentLoaded', setupCueControls);
// --- End Cue Controls ---

function startCountdown(seconds) {
  let countdown = parseInt(seconds);
  const countdownElement = document.getElementById("countdownDisplay");

  countdownElement.textContent = countdown;

  const countdownInterval = setInterval(() => {
    countdown--;

    if (countdown <= 0) {
      clearInterval(countdownInterval);
      countdownElement.textContent = "Now!";
      // --- Trigger cues when countdown ends ---
      const prefs = getCuePrefs();
      if (prefs.flash) triggerFlash();
      if (prefs.beep) triggerBeep();
      // --- End cue trigger ---
    } else {
      countdownElement.textContent = countdown;
    }
  }, 1000);
}

// Play a placeholder sound for a reaction type (to be replaced with real files)
function playReactionSound(type) {
  // Example: play a beep for now, replace with real audio later
  triggerBeep();
}

// Initialize
connectSSE();

window.addEventListener("beforeunload", () => {
  if (evtSource) evtSource.close();
  clearTimeout(reconnectTimer);
});

const cueControlsStyle = document.createElement('style');
cueControlsStyle.innerHTML = `
.cue-controls-fixed {
  position: absolute;
  left: 80px;
  top: -20px;
  z-index: 10001;
  display: flex;
  gap: 0.5em;
  border-radius: 8px;
  box-shadow: 0 2px 8px rgba(0,0,0,0.08);
  padding: 6px 10px;
}
.cue-btn {
  border-radius: 6px;
  border: 2px solid #ccc;
  background: #f8f9fa;
  cursor: pointer;
  transition: background 0.2s, border 0.2s;
  outline: none;
  padding: 4px 8px;
  margin: 0;
  display: flex;
  align-items: center;
  justify-content: center;
}
.cue-btn:active {
  filter: brightness(0.65);
}
`;
document.head.appendChild(cueControlsStyle);

function showMicIndicator(show) {
  if (!micIndicator) micIndicator = document.getElementById('micRecordingIndicator');
  if (micIndicator) micIndicator.style.display = show ? 'flex' : 'none';
}

async function startMicRecording(durationSec, commandId, commandType) {
  try {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      alert('Microphone access is not supported in this browser.');
      return;
    }
    // Request mic access if not already granted
    micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    const source = audioContext.createMediaStreamSource(micStream);
    mediaRecorder = new MediaRecorder(micStream);
    audioChunks = [];
    mediaRecorder.ondataavailable = e => {
      if (e.data.size > 0) audioChunks.push(e.data);
    };
    mediaRecorder.onstop = async () => {
      showMicIndicator(false);
      const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
      // Analyze audio
      const analysis = await analyzeAudioBlob(audioBlob, audioContext.sampleRate, commandType);
      // Send results to server
      sendMicResults({
        commandId,
        ...analysis
      });
      // Clean up
      if (micStream) {
        micStream.getTracks().forEach(track => track.stop());
        micStream = null;
      }
      if (audioContext) {
        audioContext.close();
        audioContext = null;
      }
    };
    mediaRecorder.start();
    showMicIndicator(true);
    micRecordingTimeout = setTimeout(() => {
      if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
      }
    }, durationSec * 1000);
  } catch (err) {
    alert('Microphone access denied or error: ' + err.message);
    showMicIndicator(false);
  }
}

async function analyzeAudioBlob(blob, sampleRate, commandType) {
  // Basic analysis: get average volume (RMS), peak, and duration
  const arrayBuffer = await blob.arrayBuffer();
  const ctx = new (window.AudioContext || window.webkitAudioContext)();
  const audioBuffer = await ctx.decodeAudioData(arrayBuffer);
  const data = audioBuffer.getChannelData(0);
  let sum = 0, peak = 0, startIdx = -1, endIdx = -1;
  for (let i = 0; i < data.length; i++) {
    const abs = Math.abs(data[i]);
    sum += abs * abs;
    if (abs > peak) peak = abs;
    if (startIdx === -1 && abs > 0.02) startIdx = i;
    if (abs > 0.02) endIdx = i;
  }
  const rms = Math.sqrt(sum / data.length);
  const duration = audioBuffer.duration;
  let reactionAccuracy = 0;
  if (commandType === 'silence') {
    // Special logic: high accuracy if very little sound
    if (rms < 0.01) {
      reactionAccuracy = 100;
    } else {
      // Scale down accuracy based on how loud the sound was
      reactionAccuracy = Math.max(0, 100 - Math.round(rms * 1000)); // e.g., rms 0.05 -> 50% accuracy
    }
  } else {
    // Reaction accuracy: how soon after recording started did the sound start?
    if (startIdx !== -1) {
      const startSec = startIdx / sampleRate;
      reactionAccuracy = Math.max(0, 1 - startSec / duration); // 1 = instant, 0 = very late
      reactionAccuracy = Math.round(reactionAccuracy * 100);
    }
  }
  await ctx.close();
  return {
    intensity: Math.round(rms * 100),
    volume: Math.round(peak * 100),
    reactionAccuracy
  };
}

function sendMicResults(results) {
  fetch(basePath + "/api/mic_results.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      userId: userId,
      commandId: currentCommandId,
      results: results,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      console.log("Mic results sent successfully:", data);
    })
    .catch((error) => {
      console.error("Error sending mic results:", error);
    });
}

document.addEventListener('DOMContentLoaded', function () {
  // Transfer Points AJAX handler
  const transferForm = document.querySelector('form[action="transfer_points.php"]');
  if (transferForm) {
    transferForm.addEventListener('submit', function (e) {
      e.preventDefault();
      const recipient = transferForm.querySelector('[name="recipient"]').value.trim();
      const amount = parseInt(transferForm.querySelector('[name="amount"]').value, 10);
      const message = transferForm.querySelector('[name="message"]').value.trim();
      const feedback = transferForm.querySelector('.form-feedback');
      feedback.textContent = '';
      if (!recipient || !amount || amount <= 0) {
        feedback.textContent = 'Please enter a valid recipient and amount.';
        feedback.style.color = '#c92a2a';
        return;
      }
      transferForm.querySelector('button[type="submit"]').disabled = true;
      fetch('../api/transfer_points.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({
          fromUserId: window.userId,
          toUsername: recipient,
          amount: amount,
          message: message
        })
      })
        .then(r => r.json())
        .then(resp => {
          transferForm.querySelector('button[type="submit"]').disabled = false;
          if (resp.success) {
            // Update points in UI
            const pointsLi = Array.from(document.querySelectorAll('.user-info-list li')).find(li => li.textContent.includes('Points:'));
            if (pointsLi) {
              pointsLi.innerHTML = `<strong>Points:</strong> ${resp.points}`;
            }
            feedback.textContent = 'Points transferred successfully!';
            feedback.style.color = '#2b8a3e';
            transferForm.reset();
          } else {
            feedback.textContent = resp.error || 'Transfer failed.';
            feedback.style.color = '#c92a2a';
          }
        })
        .catch(e => {
          transferForm.querySelector('button[type="submit"]').disabled = false;
          feedback.textContent = 'Transfer failed: ' + e;
          feedback.style.color = '#c92a2a';
        });
    });
  }
});
