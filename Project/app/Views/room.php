<?php
// Use provided data or sensible defaults from controller
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

<div class="container">

    <!-- Command Panel -->
    <div class="command-panel panel">
        <div class="title">🎬 Current Command</div>
        <p><strong><?= $currentCommand ?></strong></p>
        <p class="countdown">⏱ Countdown: <?= $countdown ?>...</p>
        <p>Next: <?= $nextCommand ?></p>
    </div>

    <!-- User Info Panel -->
    <div class="user-panel panel">
        <div class="title">👤 Your Info</div>
        <p><strong>Name:</strong> <?= $username ?></p>
        <p><strong>Points:</strong> <?= $points ?></p>
        <p><strong>Role:</strong> <?= $role ?></p>
        <p><strong>Group:</strong> <?= $group ?></p>
        <p><strong>Reaction Accuracy:</strong> <?= $reactionAccuracy ?>%</p>
        <p><strong>Leaderboard Rank:</strong> #<?= $leaderboardRank ?></p>
    </div>

    <!-- Audience Meter -->
    <div class="meter panel">
        <div class="title">📊 Audience Reaction</div>
        <p>🔥 Intensity: <?= $audienceIntensity ?>%</p>
        <p>🔊 Volume: <?= $audienceVolume ?></p>
        <p>👥 Responders: <?= $responders ?></p>
    </div>

    <!-- Points Transfer -->
    <div class="transfer-form panel">
        <div class="title">💸 Transfer Points</div>
        <form action="transfer_points.php" method="POST">
            <label>Recipient Username:<br><input type="text" name="recipient" required></label><br><br>
            <label>Points:<br><input type="number" name="amount" min="1" max="<?= $points ?>" required></label><br><br>
            <label>Message (optional):<br><input type="text" name="message"></label><br><br>
            <button type="submit">Send Points</button>
        </form>
    </div>

</div>
