// Get user ID and base path from global variables
const userId = window.userId || "";
const basePath = window.basePath ? window.basePath.replace(/\/+$/, '') : "";

// Simulated audience variables
let simAudienceEnabled = false; // Whether simulated audience is on
let currentSimAudio = [];

// SSE connection variables
let evtSource = null;
let reconnectAttempts = 0;
let reconnectTimer = null;
let currentCommandId = null;
let processedCommands = new Set(); // Track processed commands to prevent duplicates

// Command display timeouts
let clearInstructionTimeout = null;
let countdownTimeout = null;
let resetCommandTimeout = null;

// Microphone recording variables
let micStream = null;
let audioContext = null;
let mediaRecorder = null;
let audioChunks = [];
let micRecordingTimeout = null;
let micIndicator = null;

// Categories modal elements
let categoriesModal = null;
let closeModalBtn = null;
let updateCategoriesForm = null;

/**
 * Establishes Server-Sent Events connection
 */
function connectSSE() {
  if (evtSource) {
    evtSource.close();
  }

  evtSource = new EventSource(`${basePath}/sse`);

  evtSource.onopen = function() {
    console.log("SSE connection established");
    reconnectAttempts = 0;
  };

  evtSource.onerror = function(e) {
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

  // Event listeners
  evtSource.addEventListener("connected", function(event) {
    console.log("Connected to SSE server", JSON.parse(event.data));
  });

  evtSource.addEventListener("heartbeat", function(event) {
    const data = JSON.parse(event.data);
    console.log("Heartbeat received", data);

    if (data && typeof data.activeUsers !== "undefined") {
      const respondersElement = document.getElementById("audienceResponders");
      if (respondersElement) {
        respondersElement.textContent = data.activeUsers;
        
        // Add animation effect to the metric
        const metricContainer = respondersElement.closest('.audience-metric');
        if (metricContainer) {
          metricContainer.classList.add('metric-updated');
          setTimeout(() => {
            metricContainer.classList.remove('metric-updated');
          }, 1000);
        }
      }
    }
  });

  evtSource.addEventListener("command", async function(event) {
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
    clearCommandTimeouts();

    // Handle countdown
    let countdownSeconds = 0;
    if (data.countdown) {
      countdownSeconds = parseInt(data.countdown);
      startCountdown(countdownSeconds);
    } else {
      document.getElementById("countdownDisplay").textContent = "Now!";
    }

    // Handle microphone recording
    let duration = (typeof data.duration === "number" && data.duration > 0) ? data.duration : 0;
    
    // Schedule reset of command display after duration + countdown time
    const totalTime = (countdownSeconds + Math.max(duration, 1)) * 1000; // Ensure at least 1 second after countdown
    resetCommandTimeout = setTimeout(() => {
      resetCommandDisplay();
      // Stop any simulated audio still playing
      stopSimulatedAudio();
    }, totalTime);
    
    if (duration > 0) {
      // Schedule simulated audience sounds after countdown reaches 0
      if (simAudienceEnabled) {
        setTimeout(() => {
          playSimulatedAudience(data.type, data.intensity || 50, duration);
        }, countdownSeconds * 1000);
      }
      setTimeout(() => {
        startMicRecording(duration, data.id, data.type);
      }, countdownSeconds * 1000);
    }
  });
}

/**
 * Clears all command-related timeouts
 */
function clearCommandTimeouts() {
  if (clearInstructionTimeout) {
    clearTimeout(clearInstructionTimeout);
    clearInstructionTimeout = null;
  }
  if (countdownTimeout) {
    clearTimeout(countdownTimeout);
    countdownTimeout = null;
  }
  if (resetCommandTimeout) {
    clearTimeout(resetCommandTimeout);
    resetCommandTimeout = null;
  }
}

/**
 * Resets command display after command is complete
 */
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
}

/**
 * Initializes categories modal functionality
 */
function initCategoriesModal() {
  categoriesModal = document.getElementById('categoriesModal');
  closeModalBtn = document.querySelector('.close-modal');
  updateCategoriesForm = document.getElementById('updateCategoriesForm');
  const editCategoriesBtn = document.getElementById('editCategoriesBtn');
  
  if (!categoriesModal || !closeModalBtn || !updateCategoriesForm || !editCategoriesBtn) {
    return;
  }
  
  // Open modal when edit button is clicked
  editCategoriesBtn.addEventListener('click', () => {
    categoriesModal.style.display = 'block';
  });
  
  // Close modal when X is clicked
  closeModalBtn.addEventListener('click', () => {
    categoriesModal.style.display = 'none';
  });
  
  // Close modal when clicking outside of it
  window.addEventListener('click', (e) => {
    if (e.target === categoriesModal) {
      categoriesModal.style.display = 'none';
    }
  });
  
  // Handle form submission
  updateCategoriesForm.addEventListener('submit', (e) => {
    e.preventDefault();
    updateUserCategories();
  });
}

/**
 * Updates user categories via AJAX
 */
function updateUserCategories() {
  const formData = new FormData(updateCategoriesForm);
  const feedbackElement = updateCategoriesForm.querySelector('.form-feedback');
  
  feedbackElement.textContent = 'Updating...';
  feedbackElement.className = 'form-feedback info';
  
  fetch(`${basePath}/api/update_categories.php`, {
    method: 'POST',
    body: formData
  })
  .then(response => response.json())
  .then(data => {
    if (data.success) {
      feedbackElement.textContent = data.message;
      feedbackElement.className = 'form-feedback success';
      
      // Update the categories display in the user info panel
      const categoriesDisplay = document.getElementById('userCategories');
      if (categoriesDisplay) {
        categoriesDisplay.textContent = data.categories.join(', ');
      }
      
      // Close modal after a short delay
      setTimeout(() => {
        categoriesModal.style.display = 'none';
        feedbackElement.textContent = '';
        feedbackElement.className = 'form-feedback';
      }, 1500);
    } else {
      feedbackElement.textContent = data.error || 'An error occurred';
      feedbackElement.className = 'form-feedback error';
    }
  })
  .catch(error => {
    console.error('Error updating categories:', error);
    feedbackElement.textContent = 'Network error. Please try again.';
    feedbackElement.className = 'form-feedback error';
  });
}

/**
 * Creates or returns the flash overlay element
 */
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

/**
 * Triggers visual flash cue
 */
function triggerFlash() {
  const overlay = createFlashOverlay();
  overlay.style.opacity = 0.3; 
  setTimeout(() => {
    overlay.style.opacity = 0;
  }, 180); 
}

/**
 * Triggers audio beep cue
 */
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
    console.error('Error triggering beep:', e);
  }
}

/**
 * Gets user cue preferences from localStorage
 */
function getCuePrefs() {
  return {
    flash: localStorage.getItem('flashCueEnabled') !== 'false',
    beep: localStorage.getItem('beepCueEnabled') !== 'false',
  };
}

/**
 * Sets user cue preferences in localStorage
 */
function setCuePrefs(flash, beep) {
  localStorage.setItem('flashCueEnabled', flash);
  localStorage.setItem('beepCueEnabled', beep);
}

/**
 * Updates cue control button styling based on current preferences
 */
function updateCueIcons() {
  const flashBtn = document.getElementById('flashCueToggle');
  const beepBtn = document.getElementById('beepCueToggle');
  
  if (!flashBtn || !beepBtn) return;
  
  const flashIcon = document.getElementById('flashCueIcon');
  const beepIcon = document.getElementById('beepCueIcon');
  const prefs = getCuePrefs();
  
  if (prefs.flash) {
    flashBtn.classList.add('cue-on');
    flashBtn.classList.remove('cue-off');
    if (flashIcon) flashIcon.style.opacity = 1;
    flashBtn.style.background = '#ffe066';
    flashBtn.style.border = '2px solid #ffd700';
  } else {
    flashBtn.classList.remove('cue-on');
    flashBtn.classList.add('cue-off');
    if (flashIcon) flashIcon.style.opacity = 0.4;
    flashBtn.style.background = '#f8f9fa';
    flashBtn.style.border = '2px solid #ccc';
  }

  if (prefs.beep) {
    beepBtn.classList.add('cue-on');
    beepBtn.classList.remove('cue-off');
    if (beepIcon) beepIcon.style.opacity = 1;
    beepBtn.style.background = '#ffe066';
    beepBtn.style.border = '2px solid #ffd700';
  } else {
    beepBtn.classList.remove('cue-on');
    beepBtn.classList.add('cue-off');
    if (beepIcon) beepIcon.style.opacity = 0.4;
    beepBtn.style.background = '#f8f9fa';
    beepBtn.style.border = '2px solid #ccc';
  }
}

/**
 * Sets up cue control buttons and event listeners
 */
function setupCueControls() {
  const flashBtn = document.getElementById('flashCueToggle');
  const beepBtn = document.getElementById('beepCueToggle');
  
  if (!flashBtn || !beepBtn) return;
  
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

/**
 * Starts countdown timer and displays it
 */
function startCountdown(seconds) {
  let countdown = parseInt(seconds);
  const countdownElement = document.getElementById("countdownDisplay");
  
  if (!countdownElement) return;

  countdownElement.textContent = countdown;

  // Clear any existing interval
  if (countdownTimeout) {
    clearInterval(countdownTimeout);
  }

  countdownTimeout = setInterval(() => {
    countdown--;

    if (countdown <= 0) {
      clearInterval(countdownTimeout);
      countdownTimeout = null;
      countdownElement.textContent = "Now!";
      
      // Trigger cues when countdown ends
      const prefs = getCuePrefs();
      if (prefs.flash) triggerFlash();
      if (prefs.beep) triggerBeep();
    } else {
      countdownElement.textContent = countdown;
    }
  }, 1000);
}

/**
 * Shows or hides microphone recording indicator
 */
function showMicIndicator(show) {
  if (!micIndicator) micIndicator = document.getElementById('micRecordingIndicator');
  if (micIndicator) micIndicator.style.display = show ? 'flex' : 'none';
}

/**
 * Starts microphone recording for specified duration
 */
async function startMicRecording(durationSec, commandId, commandType) {
  // Clean up any previous recording session
  cleanupMicRecording();
  
  try {
    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      console.error('Microphone access is not supported in this browser.');
      return;
    }
    
    // Request mic access
    micStream = await navigator.mediaDevices.getUserMedia({ audio: true });
    audioContext = new (window.AudioContext || window.webkitAudioContext)();
    mediaRecorder = new MediaRecorder(micStream);
    audioChunks = [];
    
    mediaRecorder.ondataavailable = e => {
      if (e.data.size > 0) audioChunks.push(e.data);
    };
    
    mediaRecorder.onstop = async () => {
      showMicIndicator(false);
      
      if (audioChunks.length > 0) {
        const audioBlob = new Blob(audioChunks, { type: 'audio/webm' });
        // Analyze audio
        const analysis = await analyzeAudioBlob(audioBlob, audioContext.sampleRate, commandType);
        // Update UI immediately
        updateAudienceMetrics(analysis);
        // Send results to server
        sendMicResults({
          commandId,
          ...analysis
        });
      }
      
      // Clean up
      cleanupMicRecording();
    };
    
    mediaRecorder.start();
    showMicIndicator(true);
    
    // Stop recording after specified duration
    micRecordingTimeout = setTimeout(() => {
      if (mediaRecorder && mediaRecorder.state === 'recording') {
        mediaRecorder.stop();
      }
    }, durationSec * 1000);
  } catch (err) {
    console.error('Microphone access denied or error:', err);
    showMicIndicator(false);
  }
}

/**
 * Cleans up microphone recording resources
 */
function cleanupMicRecording() {
  if (micRecordingTimeout) {
    clearTimeout(micRecordingTimeout);
    micRecordingTimeout = null;
  }
  
  if (mediaRecorder && mediaRecorder.state === 'recording') {
    mediaRecorder.stop();
  }
  
  if (micStream) {
    micStream.getTracks().forEach(track => track.stop());
    micStream = null;
  }
  
  if (audioContext) {
    audioContext.close().catch(console.error);
    audioContext = null;
  }
  
  mediaRecorder = null;
  audioChunks = [];
}

/**
 * Analyzes audio blob for intensity, volume, and reaction accuracy
 */
async function analyzeAudioBlob(blob, sampleRate, commandType) {
  // Basic analysis: get average volume (RMS), peak, and duration
  try {
    const arrayBuffer = await blob.arrayBuffer();
    const ctx = new (window.AudioContext || window.webkitAudioContext)();
    const audioBuffer = await ctx.decodeAudioData(arrayBuffer);
    const data = audioBuffer.getChannelData(0);
    
    let sum = 0, peak = 0, startIdx = -1, endIdx = -1;
    const threshold = 0.02; // Audio detection threshold
    
    for (let i = 0; i < data.length; i++) {
      const abs = Math.abs(data[i]);
      sum += abs * abs;
      if (abs > peak) peak = abs;
      if (startIdx === -1 && abs > threshold) startIdx = i;
      if (abs > threshold) endIdx = i;
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
  } catch (err) {
    console.error('Error analyzing audio:', err);
    return {
      intensity: 0,
      volume: 0,
      reactionAccuracy: 0
    };
  }
}

/**
 * Sends microphone results to the server
 */
/**
 * Updates audience metrics (intensity & volume) in the Room view.
 */
function updateAudienceMetrics({ intensity, volume }) {
  const intensityEl = document.getElementById('audienceIntensity');
  const volumeEl = document.getElementById('audienceVolume');

  if (intensityEl && typeof intensity !== 'undefined') {
    intensityEl.textContent = intensity;
    const container = intensityEl.closest('.audience-metric');
    if (container) {
      container.classList.add('metric-updated');
      setTimeout(() => container.classList.remove('metric-updated'), 800);
    }
  }

  if (volumeEl && typeof volume !== 'undefined') {
    volumeEl.textContent = volume;
    const container = volumeEl.closest('.audience-metric');
    if (container) {
      container.classList.add('metric-updated');
      setTimeout(() => container.classList.remove('metric-updated'), 800);
    }
  }
}

/**
 * Sends microphone results to the server (flattened payload expected by API)
 */
function sendMicResults({ commandId, intensity, volume, reactionAccuracy }) {
  fetch(`${basePath}/api/mic_results.php`, {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      userId,
      commandId,
      intensity,
      volume,
      reactionAccuracy,
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

/**
 * Sets up transfer points form submission
 */
function setupTransferPointsForm() {
  const transferForm = document.getElementById('transferPointsForm');
  if (!transferForm) return;
  
  transferForm.addEventListener('submit', function(e) {
    e.preventDefault();
    
    const recipient = transferForm.querySelector('[name="recipient"]').value.trim();
    const amount = parseInt(transferForm.querySelector('[name="amount"]').value, 10);
    const message = transferForm.querySelector('[name="message"]').value.trim();
    const feedback = transferForm.querySelector('.form-feedback');
    
    // Reset feedback
    feedback.textContent = '';
    
    // Validate input
    if (!recipient || !amount || amount <= 0) {
      feedback.textContent = 'Please enter a valid recipient and amount.';
      feedback.className = 'form-feedback error';
      return;
    }
    
    // Disable submit button during request
    const submitButton = transferForm.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    
    // Show processing message
    feedback.textContent = 'Processing transfer...';
    feedback.className = 'form-feedback info';
    
    fetch(`${basePath}/api/transfer_points.php`, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({
        fromUserId: userId,
        toUsername: recipient,
        amount: amount,
        message: message
      })
    })
    .then(response => response.json())
    .then(data => {
      submitButton.disabled = false;
      
      if (data.success) {
        // Update points in UI
        const pointsDisplay = document.querySelector('.user-info-list .info-value');
        if (pointsDisplay && data.points) {
          pointsDisplay.textContent = data.points;
        }
        
        // Update max amount in the form
        const amountInput = document.getElementById('amount');
        if (amountInput && data.points) {
          amountInput.max = data.points;
        }
        
        feedback.textContent = data.message || 'Points transferred successfully!';
        feedback.className = 'form-feedback success';
        transferForm.reset();
      } else {
        feedback.textContent = data.error || 'Transfer failed.';
        feedback.className = 'form-feedback error';
      }
    })
    .catch(error => {
      console.error('Error transferring points:', error);
      submitButton.disabled = false;
      feedback.textContent = 'Network error. Please try again.';
      feedback.className = 'form-feedback error';
    });
  });
}

/**
 * Add CSS for cue controls
 */
function addCueControlsStyles() {
  const styleId = 'cueControlsStyle';
  
  // Don't add styles if they already exist
  if (document.getElementById(styleId)) return;
  
  const cueControlsStyle = document.createElement('style');
  cueControlsStyle.id = styleId;
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
}

/**
 * Cleanup resources on page unload
 */
function cleanup() {
  if (evtSource) {
    evtSource.close();
    evtSource = null;
  }
  
  clearTimeout(reconnectTimer);
  clearCommandTimeouts();
  cleanupMicRecording();
}

/**
 * Initialize everything when the DOM is loaded
 */
async function checkSimAudience() {
  try {
    const resp = await fetch(`${basePath}/api/toggle_sim_audience.php`, { method: 'GET' });
    const json = await resp.json();
    if (json && json.success) {
      simAudienceEnabled = json.enabled === true || json.enabled === '1';
    }
  } catch (e) {
    console.error('Failed to check simulated audience setting:', e);
  }
}

/**
 * Plays simulated audience sounds based on command type, intensity (0-100) and duration
 */
function playSimulatedAudience(commandType, intensity = 50, durationSec = 5) {
  stopSimulatedAudio(); // Make sure nothing else is playing

  const audioBase = `${basePath}/audio/`;
  const soundMap = {
    applause: ['clap1.mp3', 'clap2.mp3', 'clap3.mp3', 'clap4.mp3'],
    clap: ['clap1.mp3', 'clap2.mp3', 'clap3.mp3', 'clap4.mp3'],
    cheer: ['cheer1.mp3', 'cheer2.mp3', 'cheer3.mp3', 'cheer_whistle1.mp3', 'cheer_whistle2.mp3'],
    boo: ['boo1.mp3', 'boo2.mp3', 'boo3.mp3', 'boo4.mp3'],
    murmur: ['murmur1.mp3', 'murmur2.mp3', 'murmur3.mp3', 'murmur4.mp3'],
    stomp: ['stomping1.mp3', 'stomping2.mp3', 'stomping3.mp3', 'stopming4.mp3']
  };

  const clips = soundMap[commandType] || soundMap['applause'];
  if (!clips || clips.length === 0) return;

  // Determine how many clips to layer (1-8) based on intensity
  const layers = Math.max(1, Math.min(8, Math.round(intensity / 15)));

  for (let i = 0; i < layers; i++) {
    const file = clips[Math.floor(Math.random() * clips.length)];
    const audio = new Audio(audioBase + file);
    // Volume between 0.3-1 depending on intensity plus slight randomness
    const volume = Math.min(1, 0.3 + (intensity / 100) * 0.7 + (Math.random() * 0.1 - 0.05));
    audio.volume = volume;
    audio.play().catch(console.error);
    currentSimAudio.push(audio);
  }

  // Stop and clear after duration
  setTimeout(stopSimulatedAudio, durationSec * 1000);
}

function stopSimulatedAudio() {
  currentSimAudio.forEach(a => {
    try {
      a.pause();
      a.currentTime = 0;
    } catch (e) {}
  });
  currentSimAudio = [];
}

document.addEventListener('DOMContentLoaded', async function() {
  // Check sim audience first
  await checkSimAudience();
  // Add styles
  addCueControlsStyles();
  
  // Initialize components
  initCategoriesModal();
  setupCueControls();
  setupTransferPointsForm();
  
  // Check if simulated audience is enabled then connect SSE
  connectSSE();
  
  // Set up mic recording indicator
  micIndicator = document.getElementById('micRecordingIndicator');
});

// Clean up resources when page is unloaded
window.addEventListener("beforeunload", cleanup);
