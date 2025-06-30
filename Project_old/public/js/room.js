// Get user ID from global variable
const userId = window.userId || "";
let evtSource = null;
let reconnectAttempts = 0;
let currentCommandId = null;
let reconnectTimer = null;
let processedCommands = new Set(); // Track processed commands to prevent duplicates
let clearInstructionTimeout = null;
let countdownTimeout = null;

// --- Microphone Recording & Analysis ---
let micStream = null;
let audioContext = null;
let mediaRecorder = null;
let audioChunks = [];
let micRecordingTimeout = null;
let micIndicator = null;

// --- Audio Players ---
let ambientAudio = null;
let reactionAudio = null;

console.log("Room.js loaded");

// Function to establish SSE connection
function connectSSE() {
  if (evtSource) {
    evtSource.close();
  }

  evtSource = new EventSource("/sse");

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

    document.getElementById("commandText").textContent =
      data.type + (data.message ? ": " + data.message : "");

    // Clear any previous timeouts
    if (clearInstructionTimeout) {
      clearTimeout(clearInstructionTimeout);
    }
    if (countdownTimeout) {
      clearTimeout(countdownTimeout);
    }

    let countdownSeconds = 0;
    if (data.countdown) {
      countdownSeconds = parseInt(data.countdown);
      startCountdown(countdownSeconds);
    } else {
      document.getElementById("countdownDisplay").textContent = "Now!";
      // If no countdown, trigger audio cues immediately
      const prefs = getCuePrefs();
      if (prefs.flash) triggerFlash();
      if (prefs.beep) triggerBeep();
    }

    // Always start mic recording after countdown, for the duration
    let duration = (typeof data.duration === "number" && data.duration > 0) ? data.duration : 0;
    if (duration > 0) {
      // Play the reaction sound when the countdown ends
      setTimeout(() => {
        playReactionSound(data.type);
        startMicRecording(duration, data.id, data.type);
      }, countdownSeconds * 1000);
      
      // Set a timeout to clear the command after its duration
      clearInstructionTimeout = setTimeout(() => {
        document.getElementById("commandText").textContent = "Awaiting next command...";
        document.getElementById("countdownDisplay").textContent = "";
      }, (countdownSeconds + duration) * 1000 + 1000); // Add 1 second buffer
    }
  });
}

// --- Cue Controls ---
function createFlashOverlay() {
  let overlay = document.getElementById('flashCueOverlay');
  if (!overlay) {
    overlay = document.createElement('div');
    overlay.id = 'flashCueOverlay';
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
  const prefs = getCuePrefs();
  
  if (prefs.flash) {
    flashBtn.classList.add('cue-on');
    flashBtn.classList.remove('cue-off');
  } else {
    flashBtn.classList.remove('cue-on');
    flashBtn.classList.add('cue-off');
  }
  
  if (prefs.beep) {
    beepBtn.classList.add('cue-on');
    beepBtn.classList.remove('cue-off');
  } else {
    beepBtn.classList.remove('cue-on');
    beepBtn.classList.add('cue-off');
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

// --- Audio Functions ---
function initAudioPlayers() {
  // Create ambient audio player
  ambientAudio = new Audio('/audio/murmur1.mp3');
  ambientAudio.loop = true;
  ambientAudio.volume = 0.2;

  // Create reaction audio player
  reactionAudio = new Audio();
  
  // Test the audio context to see if it needs user interaction
  try {
    const testContext = new (window.AudioContext || window.webkitAudioContext)();
    testContext.close();
  } catch (e) {
    console.warn('Audio context requires user interaction: ', e);
    addAudioPlayButton();
  }
}

function playAmbientAudio() {
  if (ambientAudio) {
    ambientAudio.play().catch(err => {
      console.warn('Could not autoplay ambient audio: ', err);
      // Add a play button to the UI for user interaction
      addAudioPlayButton();
    });
  }
}

function addAudioPlayButton() {
  const audioBtn = document.createElement('button');
  audioBtn.textContent = '🔊 Play Room Audio';
  audioBtn.style.position = 'fixed';
  audioBtn.style.bottom = '20px';
  audioBtn.style.right = '20px';
  audioBtn.style.padding = '8px 15px';
  audioBtn.style.borderRadius = '20px';
  audioBtn.style.background = '#38bdf8';
  audioBtn.style.color = 'white';
  audioBtn.style.border = 'none';
  audioBtn.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
  audioBtn.style.cursor = 'pointer';
  audioBtn.style.zIndex = '1000';
  
  audioBtn.addEventListener('click', () => {
    playAmbientAudio();
    audioBtn.remove();
  });
  
  document.body.appendChild(audioBtn);
}

// Play a reaction sound for a specific reaction type
function playReactionSound(type) {
  if (!reactionAudio) return;
  
  console.log(`Playing reaction sound for: ${type}`);
  
  // Map of reaction types to audio files
  const audioMap = {
    'applause': '/audio/clap1.mp3',
    'cheer': '/audio/cheer1.mp3',
    'boo': '/audio/boo1.mp3',
    'murmur': '/audio/murmur2.mp3',
    'stomp': '/audio/stomping1.mp3',
    'silence': '/audio/silence.wav'
  };
  
  // Get random variation (1-4) for more variety
  const variation = Math.floor(Math.random() * 4) + 1;
  const baseType = type.replace(/[0-9]/g, '');
  const fileBase = audioMap[baseType] || audioMap['applause'];
  
  // Extract the base name and extension
  const filenameParts = fileBase.split('.');
  const ext = filenameParts.pop();
  const basename = filenameParts.join('.');
  const baseNumber = basename.match(/\d+$/)?.[0] || '';
  
  // Create filename with variation
  let filename;
  if (baseNumber) {
    filename = basename.replace(/\d+$/, variation) + '.' + ext;
  } else {
    filename = basename + variation + '.' + ext;
  }
  
  console.log(`Audio file to play: ${filename}`);
  
  // Check if the file exists, fallback to base file
  reactionAudio.src = filename;
  reactionAudio.volume = 0.5;
  reactionAudio.play().catch(err => {
    console.warn('Could not play reaction sound: ', err);
    // Fallback to the beep
    triggerBeep();
  });
}

// --- Initialize everything when DOM loads ---
document.addEventListener('DOMContentLoaded', function() {
  setupCueControls();
  initAudioPlayers();
  playAmbientAudio();
});

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

// Initialize
connectSSE();

window.addEventListener("beforeunload", () => {
  if (evtSource) evtSource.close();
  clearTimeout(reconnectTimer);
});

const cueControlsStyle = document.createElement('style');

document.head.appendChild(cueControlsStyle);

function showMicIndicator(show) {
  if (!micIndicator) micIndicator = document.getElementById('micRecordingIndicator');
  if (micIndicator) {
    if (show) {
      micIndicator.style.display = 'flex';
    } else {
      micIndicator.style.display = 'none';
    }
  }
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
  fetch('/api/mic_results.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      userId: window.userId,
      ...results
    })
  }).then(r => r.json()).then(resp => {
    console.log('Mic results sent:', resp);
    // Update UI with results
    if (results.intensity !== undefined) {
      document.getElementById('audienceIntensity').textContent = results.intensity + '%';
    }
    if (results.volume !== undefined) {
      document.getElementById('audienceVolume').textContent = results.volume + ' dB';
    }
    if (results.reactionAccuracy !== undefined) {
      if (document.getElementById('reactionAccuracy')) {
        document.getElementById('reactionAccuracy').textContent = results.reactionAccuracy + '%';
      }
    }
    // Update points instantly if returned
    if (resp.points !== undefined) {
      // Find the Points field in the user info panel and update it
      const pointsLi = Array.from(document.querySelectorAll('.user-info-list li')).find(li => li.textContent.includes('Points:'));
      if (pointsLi) {
        pointsLi.innerHTML = `<strong>Points:</strong> ${resp.points}`;
      }
    }
  }).catch(e => {
    console.error('Failed to send mic results:', e);
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
      fetch('/api/transfer_points.php', {
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
