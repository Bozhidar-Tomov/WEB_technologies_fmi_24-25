<?php
$username = $username ?? 'Guest';
$points = $points ?? 0;
$role = $role ?? 'Participant';
$group = isset($groups) && is_array($groups) && count($groups) > 0 ? implode(', ', $groups) : 'General';
$reactionAccuracy = $reactionAccuracy ?? 0; // Could be calculated elsewhere
$leaderboardRank = $leaderboardRank ?? '-'; // Could be calculated elsewhere
$currentCommand = $currentCommand ?? 'Awaiting next command...';
$countdown = $countdown ?? '-';
$nextCommand = $nextCommand ?? 'TBA';
$audienceIntensity = $audienceIntensity ?? '-';
$audienceVolume = $audienceVolume ?? '-';
$responders = $responders ?? '-';
?>

<main class="room-main" aria-label="Room View">
    <section class="panels">
        <section class="panel command-panel" aria-label="Current Command">
            <div class="panel-icon">🎬</div>
            <h2 class="panel-title">Current Command</h2>
            <p class="command-text"><strong><?= $currentCommand ?></strong></p>
            <p class="countdown">⏱ Countdown: <?= $countdown ?>...</p>
            <p class="next-command">Next: <?= $nextCommand ?></p>
        </section>
        <section class="panel user-panel" aria-label="Your Info">
            <div class="panel-icon">👤</div>
            <h2 class="panel-title">Your Info</h2>
            <ul class="user-info-list">
                <li><strong>Name:</strong> <?= $username ?></li>
                <li><strong>Points:</strong> <?= $points ?></li>
                <li><strong>Role:</strong> <?= $role ?></li>
                <li><strong>Group:</strong> <?= $group ?></li>
                <li><strong>Reaction Accuracy:</strong> <?= $reactionAccuracy ?>%</li>
                <li><strong>Leaderboard Rank:</strong> #<?= $leaderboardRank ?></li>
            </ul>
        </section>
        <section class="panel meter" aria-label="Audience Reaction">
            <div class="panel-icon">📊</div>
            <h2 class="panel-title">Audience Reaction</h2>
            <ul class="audience-info-list">
                <li>🔥 Intensity: <?= $audienceIntensity ?>%</li>
                <li>🔊 Volume: <?= $audienceVolume ?></li>
                <li>👥 Responders: <?= $responders ?></li>
            </ul>
        </section>
        <section class="panel" aria-label="Transfer Points">
            <div class="panel-icon">💸</div>
            <h2 class="panel-title">Transfer Points</h2>
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
