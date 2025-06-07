<?php
$username = $username ?? 'Guest';
$points = $points ?? 0;
$role = $role ?? 'Participant';
$group = isset($groups) && is_array($groups) && count($groups) > 0 ? implode(', ', $groups) : 'General';
$reactionAccuracy = $reactionAccuracy ?? 0;
$currentCommand = 'Awaiting next command...';
$countdown = '';
$audienceIntensity = '';
$audienceVolume = '';
$responders = '';
?>

<main class="room-main" aria-label="Room View">
    <section class="command-panel" aria-label="Current Command">
        <div class="command-icon">🎬</div>
        <p class="command-text" id="commandText"><?= $currentCommand ?></p>
        <p class="countdown" id="countdownDisplay"><?= $countdown ?></p>
    </section>
    
    <section class="panels">
        <section class="panel user-panel" aria-label="Your Info">
            <h2 class="panel-title">Your Info</h2>
            <ul class="user-info-list">
                <li><strong>Name:</strong> <?= $username ?></li>
                <li><strong>Points:</strong> <?= $points ?></li>
                <li><strong>Role:</strong> <?= $role ?></li>
                <li><strong>Group:</strong> <?= $group ?></li>
                <li><strong>Reaction Accuracy:</strong> <?= $reactionAccuracy ?>%</li>
            </ul>
        </section>
        <section class="panel meter" aria-label="Audience Reaction">
            <h2 class="panel-title">Audience Reaction</h2>
            <ul class="audience-info-list">
                <li>🔥 Intensity: <span id="audienceIntensity"><?= $audienceIntensity ?></span></li>
                <li>🔊 Volume: <span id="audienceVolume"><?= $audienceVolume ?></span></li>
                <li>👥 Responders: <span id="audienceResponders"><?= $responders ?></span></li>
            </ul>
        </section>
        <section class="panel" aria-label="Transfer Points">
            <h2 class="panel-title">💸 Transfer Points</h2>
            <form class="form-fields" action="transfer_points.php" method="POST" autocomplete="off">
                <label for="recipient">Recipient Username:</label>
                <input type="text" id="recipient" name="recipient" required>
                <label for="amount">Points:</label>
                <input type="number" id="amount" name="amount" min="1" max="<?= $points ?>" required>
                <label for="message">Message (optional):</label>
                <input type="text" id="message" name="message">
                <button class="btn btn-primary" type="submit">Send Points</button>
                <div class="form-feedback" aria-live="polite"></div>
            </form>
        </section>
    </section>
</main>

<script>
    window.userId = "<?= $_SESSION['user']['id'] ?? '' ?>";
</script>
<script src="../js/room.js"></script>