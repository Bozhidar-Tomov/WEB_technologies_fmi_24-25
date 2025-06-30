const intensitySlider = document.getElementById('intensity');
const intensityValue = document.getElementById('intensityValue');

function updateSlider(slider) {
    const value = slider.value;
    const max = slider.max || 100;
    const percentage = (value / max) * 100;
    slider.style.setProperty('--range-progress', `${percentage}%`);
}

if (intensitySlider) {
  updateSlider(intensitySlider);
  intensitySlider.addEventListener('input', function(e) {
    intensityValue.textContent = e.target.value;
    updateSlider(e.target);
  });
}

document.getElementById('commandForm').addEventListener('submit', function(e) {
    const submitButton = this.querySelector('button[type="submit"]');
    const feedbackElement = document.getElementById('commandFormFeedback');
    
    feedbackElement.textContent = 'Sending command...';
    feedbackElement.style.color = '#2b8a3e';
    
    submitButton.disabled = true;
    submitButton.textContent = 'Sending...';
});

function updateSimAudienceBtnAndSlider() {
  const simBtn = document.getElementById('simAudienceBtn');
  const simBtnText = document.getElementById('simAudienceBtnText');
  const sliderWrapper = document.getElementById('intensitySliderWrapper');
  if (!simBtn || !simBtnText) return;
  fetch('../api/toggle_sim_audience.php', { method: 'GET' })
    .then(r => r.json())
    .then(resp => {
      if (resp.success) {
        simBtnText.textContent = resp.enabled ? 'Disable' : 'Enable';
        if (sliderWrapper) sliderWrapper.style.display = resp.enabled ? '' : 'none';
      }
    });
}

document.addEventListener('DOMContentLoaded', function () {
  updateSimAudienceBtnAndSlider();
  // Simulated Audience button AJAX handler
  const simBtn = document.getElementById('simAudienceBtn');
  const simBtnText = document.getElementById('simAudienceBtnText');
  const simFeedback = document.getElementById('simAudienceFeedback');
  if (simBtn && simBtnText) {
    simBtn.addEventListener('click', function () {
      simFeedback.textContent = '';
      const enable = simBtnText.textContent.trim().toLowerCase() === 'enable';
      fetch('../api/toggle_sim_audience.php', {
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
});