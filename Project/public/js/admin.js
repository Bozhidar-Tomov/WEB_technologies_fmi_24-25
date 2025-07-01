const intensitySlider = document.getElementById('intensity');
const intensityValue = document.getElementById('intensityValue');

function updateSlider(slider) {
    if (!slider) return;
    const value = slider.value;
    const max = slider.max || 100;
    const percentage = (value / max) * 100;
    slider.style.setProperty('--range-progress', `${percentage}%`);
}

if (intensitySlider && intensityValue) {
  updateSlider(intensitySlider);
  intensitySlider.addEventListener('input', function(e) {
    intensityValue.textContent = e.target.value;
    updateSlider(e.target);
  });
}

// Add form submission handler
const commandForm = document.getElementById('commandForm');
if (commandForm) {
  commandForm.addEventListener('submit', function(e) {
      const submitButton = this.querySelector('button[type="submit"]');
      const feedbackElement = document.getElementById('commandFormFeedback');
      
      if (feedbackElement) {
        feedbackElement.textContent = 'Sending command...';
        feedbackElement.style.color = '#2b8a3e';
      }
      
      if (submitButton) {
        submitButton.disabled = true;
        submitButton.textContent = 'Sending...';
      }
  });
}

// Function to update statistics section
function updateStatistics() {
  fetch('../api/admin_stats.php')
    .then(response => {
      if (!response.ok) {
        throw new Error(`HTTP error ${response.status}`);
      }
      return response.json();
    })
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
        statusDotElement.className = `status-dot ${data.sseStatus || 'offline'}`;
        statusTextElement.textContent = data.statusText || 'Status unknown';
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
    element.textContent = value !== undefined ? value : '';
  }
}

// Update last command information
function updateLastCommandInfo(cmd) {
  const lastCommandInfoElement = document.getElementById('lastCommandInfo');
  if (!lastCommandInfoElement || !cmd) return;
  
  try {
    const html = `
      <strong>Last Command:</strong><br>
      <b>Type:</b> ${cmd.type || 'Unknown'}<br>
      <b>Intensity:</b> ${cmd.intensity || '0'}<br>
      <b>Duration:</b> ${cmd.duration || '0'}s<br>
      <b>Countdown:</b> ${cmd.countdown || '0'}s<br>
      <b>Target Groups:</b> ${Array.isArray(cmd.targetGroups) ? cmd.targetGroups.join(', ') : (cmd.targetGroups || 'All')}<br>
      <b>Target Tags:</b> ${Array.isArray(cmd.targetTags) ? cmd.targetTags.join(', ') : (cmd.targetTags || 'All')}<br>
      <b>Target Gender:</b> ${cmd.targetGender || 'All'}<br>
      <b>Message:</b> ${cmd.message || ''}<br>
      <b>Sent at:</b> ${cmd.timestamp ? new Date(cmd.timestamp * 1000).toLocaleString() : 'Unknown'}
    `;
    lastCommandInfoElement.innerHTML = html;
  } catch (error) {
    console.error('Error updating last command info:', error);
    lastCommandInfoElement.innerHTML = '<strong>Error displaying last command</strong>';
  }
}

// Update simulated audience button and slider
function updateSimAudienceBtnAndSlider() {
  const simBtn = document.getElementById('simAudienceBtn');
  const simBtnText = document.getElementById('simAudienceBtnText');
  const sliderWrapper = document.getElementById('intensitySliderWrapper');
  
  if (!simBtn || !simBtnText) return;
  
  fetch('../api/toggle_sim_audience.php', { method: 'GET' })
    .then(r => {
      if (!r.ok) {
        throw new Error(`HTTP error ${r.status}`);
      }
      return r.json();
    })
    .then(resp => {
      if (resp && resp.success) {
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
    
    fetch('../api/toggle_sim_audience.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: enable ? 'sim_audience=on' : ''
    })
      .then(r => {
        if (!r.ok) {
          throw new Error(`HTTP error ${r.status}`);
        }
        return r.json();
      })
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
  
  // Use a safe interval to prevent memory leaks
  let statsInterval = setInterval(updateStatistics, 10000); // Update every 10 seconds
  
  // Clean up interval if page is unloaded
  window.addEventListener('beforeunload', function() {
    clearInterval(statsInterval);
  });
});