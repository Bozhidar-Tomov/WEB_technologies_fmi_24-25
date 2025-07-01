// Global variables
let eventSource = null;
let statusCheckTimer = null;
let lastCommandData = null;
let reconnectAttempts = 0;
let reconnectTimer = null;

document.addEventListener('DOMContentLoaded', function() {
    // Connect to SSE server
    connectSSE();
    
    // Setup the intensity slider display
    const intensitySlider = document.getElementById('intensity');
    const intensityValue = document.getElementById('intensityValue');
    if (intensitySlider && intensityValue) {
        intensitySlider.addEventListener('input', function() {
            intensityValue.textContent = this.value;
        });
    }

    // Setup simulated audience toggle button
    const simAudienceBtn = document.getElementById('simAudienceBtn');
    const simAudienceBtnText = document.getElementById('simAudienceBtnText');
    if (simAudienceBtn) {
        simAudienceBtn.addEventListener('click', function() {
            toggleSimAudience(simAudienceBtnText.textContent === 'Enable');
        });
    }
});

// Fetch admin stats periodically
function fetchAdminStats() {
    fetch('/api/admin_stats.php')
        .then(response => response.json())
        .then(data => {
            document.getElementById('activeUsers').textContent = data.activeUsers || 0;
            document.getElementById('currentVolume').textContent = data.currentVolume ? (data.currentVolume + ' dB') : '0 dB';
            document.getElementById('responseRate').textContent = data.responseRate ? (data.responseRate + '%') : '0%';
        })
        .catch(error => {
            console.error('Error fetching admin stats:', error);
        });
}

// Initialize stats refresh
setInterval(fetchAdminStats, 5000);
fetchAdminStats(); // Initial fetch

// Function to establish SSE connection
function connectSSE() {
    if (eventSource) {
        eventSource.close();
    }

    eventSource = new EventSource('/sse');

    eventSource.onopen = function() {
        console.log('SSE connection established');
        reconnectAttempts = 0;
        updateConnectionStatus('connected');
    };

    eventSource.onerror = function(e) {
        console.error('SSE connection error', e);
        updateConnectionStatus('disconnected');

        eventSource.close();
        eventSource = null;

        if (reconnectAttempts < 10) {
            const delay = Math.min(1000 * Math.pow(2, reconnectAttempts), 30000);
            console.log(`Attempting to reconnect in ${delay/1000} seconds...`);

            clearTimeout(reconnectTimer);
            reconnectTimer = setTimeout(() => {
                reconnectAttempts++;
                connectSSE();
            }, delay);
        } else {
            alert("Connection to server lost. Please refresh the page to reconnect.");
        }
    };

    // Handle incoming events
    eventSource.addEventListener('connected', function(event) {
        console.log('Connected to SSE server', JSON.parse(event.data));
    });

    eventSource.addEventListener('command', function(event) {
        const data = JSON.parse(event.data);
        console.log('Command received:', data);
        updateLastCommandInfo(data);
    });
}

// Toggle simulated audience
function toggleSimAudience(enable) {
    const simAudienceBtn = document.getElementById('simAudienceBtn');
    const simAudienceBtnText = document.getElementById('simAudienceBtnText');
    const feedback = document.getElementById('simAudienceFeedback');
    
    if (simAudienceBtn) simAudienceBtn.disabled = true;
    if (feedback) feedback.textContent = 'Processing...';

    // First, check current status
    fetch('/api/toggle_sim_audience.php', { method: 'GET' })
        .then(response => response.json())
        .then(data => {
            const currentStatus = data.enabled;
            const newStatus = enable; // We want to enable it if it's currently disabled
            
            if (currentStatus === newStatus) {
                // Status already matches what we want
                if (feedback) feedback.textContent = `Simulated audience is already ${newStatus ? 'enabled' : 'disabled'}.`;
                if (simAudienceBtn) simAudienceBtn.disabled = false;
                return;
            }
            
            // Toggle the status
            fetch('/api/toggle_sim_audience.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({ enable: newStatus }),
            })
            .then(response => response.json())
            .then(result => {
                if (feedback) feedback.textContent = result.message;
                if (simAudienceBtnText) {
                    simAudienceBtnText.textContent = result.enabled ? 'Disable' : 'Enable';
                }
                
                // Show/hide intensity slider based on status
                const intensityWrapper = document.getElementById('intensitySliderWrapper');
                if (intensityWrapper) {
                    intensityWrapper.style.display = result.enabled ? 'block' : 'none';
                }
            })
            .catch(error => {
                console.error('Error toggling simulated audience:', error);
                if (feedback) feedback.textContent = 'An error occurred while toggling simulated audience.';
            })
            .finally(() => {
                if (simAudienceBtn) simAudienceBtn.disabled = false;
            });
        })
        .catch(error => {
            console.error('Error checking simulated audience status:', error);
            if (feedback) feedback.textContent = 'Failed to check current status.';
            if (simAudienceBtn) simAudienceBtn.disabled = false;
        });
}

// Update connection status indicator
function updateConnectionStatus(status) {
    const statusDot = document.getElementById('statusDot');
    const statusText = document.getElementById('statusText');
    
    if (statusDot && statusText) {
        if (status === 'connected') {
            statusDot.style.backgroundColor = '#2b8a3e';
            statusText.textContent = 'SSE Server: Connected';
        } else {
            statusDot.style.backgroundColor = '#c92a2a';
            statusText.textContent = 'SSE Server: Disconnected';
        }
    }
}

// Update last command information
function updateLastCommandInfo(command) {
    const lastCommandInfo = document.getElementById('lastCommandInfo');
    if (lastCommandInfo) {
        const time = new Date().toLocaleTimeString();
        lastCommandInfo.innerHTML = `
            <div><strong>Last Command:</strong> ${command.type}</div>
            <div><strong>Time:</strong> ${time}</div>
            <div><strong>Duration:</strong> ${command.duration || 0}s</div>
            <div><strong>Target Groups:</strong> ${command.targetGroups && command.targetGroups.length ? command.targetGroups.join(', ') : 'All'}</div>
        `;
    }
}

// Add form submission handler
const commandForm = document.getElementById('commandForm');
if (commandForm) {
  commandForm.addEventListener('submit', function(e) {
      const submitButton = this.querySelector('button[type="submit"]');
      const feedbackElement = document.getElementById('commandFormFeedback');
      
      feedbackElement.textContent = 'Sending command...';
      feedbackElement.style.color = '#2b8a3e';
      
      submitButton.disabled = true;
      submitButton.textContent = 'Sending...';
  });
}

// Function to update statistics section
function updateStatistics() {
  fetch('/api/admin_stats.php')
    .then(response => response.json())
    .then(data => {
      if (data.error) {
        console.error('Error fetching statistics:', data.error);
        return;
      }
      
      // Update elements with data
      updateElement('activeUsers', data.activeUsers);
      updateElement('currentVolume', data.currentVolume);
      updateElement('responseRate', data.responseRate);
      
      // Update SSE status
      const statusDotElement = document.getElementById('statusDot');
      const statusTextElement = document.getElementById('statusText');
      if (statusDotElement && statusTextElement) {
        statusDotElement.className = `status-dot ${data.sseStatus}`;
        statusTextElement.textContent = data.statusText;
      }
      
      // Update last command info if available
      updateLastCommandInfo(data.lastCommand);
    })
    .catch(error => {
      console.error('Failed to fetch statistics:', error);
    });
}

// Helper function to update element text content
function updateElement(id, value) {
  const element = document.getElementById(id);
  if (element) {
    element.textContent = value;
  }
}

// Update simulated audience button and slider
function updateSimAudienceBtnAndSlider() {
  const simBtn = document.getElementById('simAudienceBtn');
  const simBtnText = document.getElementById('simAudienceBtnText');
  const sliderWrapper = document.getElementById('intensitySliderWrapper');
  
  if (!simBtn || !simBtnText) return;
  
  fetch('/api/toggle_sim_audience.php', { method: 'GET' })
    .then(r => r.json())
    .then(resp => {
      if (resp.success) {
        simBtnText.textContent = resp.enabled ? 'Disable' : 'Enable';
        if (sliderWrapper) sliderWrapper.style.display = resp.enabled ? '' : 'none';
      }
    })
    .catch(error => {
      console.error('Failed to check simulated audience status:', error);
    });
}

// Handle simulated audience toggle
function setupSimAudienceToggle() {
  const simBtn = document.getElementById('simAudienceBtn');
  const simBtnText = document.getElementById('simAudienceBtnText');
  const simFeedback = document.getElementById('simAudienceFeedback');
  
  if (!simBtn || !simBtnText || !simFeedback) return;
  
  simBtn.addEventListener('click', function () {
    simFeedback.textContent = '';
    const enable = simBtnText.textContent.trim().toLowerCase() === 'enable';
    
    fetch('/api/toggle_sim_audience.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: enable ? 'sim_audience=on' : ''
    })
      .then(r => r.json())
      .then(resp => {
        if (resp.success) {
          updateSimAudienceBtnAndSlider();
          simFeedback.textContent = 'Simulated Audience ' + (enable ? 'enabled!' : 'disabled!');
          simFeedback.style.color = '#2b8a3e';
        } else {
          simFeedback.textContent = resp.error || 'Failed to update.';
          simFeedback.style.color = '#c92a2a';
        }
      })
      .catch(e => {
        simFeedback.textContent = 'Failed: ' + e;
        simFeedback.style.color = '#c92a2a';
      });
  });
}

// Initialize everything when DOM is loaded
document.addEventListener('DOMContentLoaded', function () {
  updateSimAudienceBtnAndSlider();
  setupSimAudienceToggle();
  
  // Start periodic updates of statistics
  updateStatistics(); // Initial update
  setInterval(updateStatistics, 10000); // Update every 10 seconds
});