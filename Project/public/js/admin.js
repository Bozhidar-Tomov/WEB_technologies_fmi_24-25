const intensitySlider = document.getElementById('intensity');
const intensityValue = document.getElementById('intensityValue');

function updateSlider(slider) {
    const value = slider.value;
    const max = slider.max || 100;
    const percentage = (value / max) * 100;
    slider.style.setProperty('--range-progress', `${percentage}%`);
}

updateSlider(intensitySlider);

intensitySlider.addEventListener('input', function(e) {
    intensityValue.textContent = e.target.value;
    updateSlider(e.target);
});
document.getElementById('commandForm').addEventListener('submit', function(e) {
    const submitButton = this.querySelector('button[type="submit"]');
    submitButton.disabled = true;
    submitButton.textContent = 'Sending...';
});