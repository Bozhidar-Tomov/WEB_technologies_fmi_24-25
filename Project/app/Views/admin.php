<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = null;
    $error = null;
    try {
        $formData = [
            'type' => $_POST['type'] ?? '',
            'intensity' => $_POST['intensity'] ?? '',
            'duration' => $_POST['duration'] ?? '',
            'countdown' => $_POST['countdown'] ?? '',
            'groups' => $_POST['groups'] ?? '',
            'message' => $_POST['message'] ?? ''
        ];
        // You may want to add validation here
        $ch = curl_init('http://localhost/admin/send-command');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $formData);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpCode === 200) {
            $result = json_decode($response, true);
        } else {
            $error = 'Failed to send command (HTTP ' . $httpCode . ')';
        }
    } catch (Exception $e) {
        $error = 'Error: ' . $e->getMessage();
    }
}
?>
<main class="admin-main">
    <header class="admin-header">
        <h1>Admin Dashboard</h1>
        
        <?php if (!empty($result['success'])): ?>
            <div class="form-feedback" role="status">Command sent successfully!</div>
        <?php elseif (!empty($error) || (!empty($result) && empty($result['success']))): ?>
            <div class="form-feedback" role="alert">Error: <?= htmlspecialchars($error ?? ($result['error'] ?? 'Unknown error')) ?></div>
        <?php endif; ?>
    </header>
    
    <section class="panels">
        <!-- Command Panel -->
        <section class="panel" aria-label="Send Command">
            <div class="panel-icon">🎮</div>
            <h2 class="panel-title">Send Command</h2>
            <form id="commandForm" method="post" class="form-fields">
                    <label for="commandType">Command Type:</label>
                    <select id="commandType" name="type" required>
                        <option value="applause">Applause</option>
                        <option value="cheer">Cheer</option>
                        <option value="boo">Boo</option>
                        <option value="murmur">Murmur</option>
                        <option value="stomp">Stomp</option>
                        <option value="silence">Silence</option>
                    </select>


                    <label for="intensity">Intensity (1-100):</label>
                    <div class="slider-container">
                        <input type="range" id="intensity" name="intensity" min="1" max="100" value="50">
                        <span id="intensityValue">50</span>
                    </div>


                    <label for="duration">Duration (seconds):</label>
                    <input type="number" id="duration" name="duration" min="1" max="30" value="5">


                    <label for="countdown">Countdown (seconds):</label>
                    <input type="number" id="countdown" name="countdown" min="0" max="10" value="3">


                    <label for="groups">Target Groups (comma-separated):</label>
                    <input type="text" id="groups" name="groups" placeholder="e.g., VIP,male,section-A">


                    <label for="message">Custom Message:</label>
                    <input type="text" id="message" name="message" placeholder="Optional instruction or message">


                <button type="submit" class="btn btn-primary">Send Command</button>
            </form>
        </section>

        <!-- Live Feedback Panel -->
        <section class="panel" aria-label="Live Feedback">
            <div class="panel-icon">📊</div>
            <h2 class="panel-title">Live Feedback</h2>
            <div class="metrics">
                <div class="metric">
                    <label>Active Users:</label>
                    <span id="activeUsers">0</span>
                </div>
                <div class="metric">
                    <label>Current Volume:</label>
                    <span id="currentVolume">0 dB</span>
                </div>
                <div class="metric">
                    <label>Response Rate:</label>
                    <span id="responseRate">0%</span>
                </div>
            </div>
        </section>
    </section>
</main>

<script>
    // Update intensity value display and slider fill
    const intensitySlider = document.getElementById('intensity');
    const intensityValue = document.getElementById('intensityValue');
    
    // Function to update slider progress
    function updateSlider(slider) {
        const value = slider.value;
        const max = slider.max || 100;
        const percentage = (value / max) * 100;
        slider.style.setProperty('--range-progress', `${percentage}%`);
    }
    
    // Set initial state
    updateSlider(intensitySlider);
    
    // Update on slider change
    intensitySlider.addEventListener('input', function(e) {
        intensityValue.textContent = e.target.value;
        updateSlider(e.target);
    });
</script>