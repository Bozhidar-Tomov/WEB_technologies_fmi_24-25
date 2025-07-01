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
    <div class="cue-controls-fixed" aria-label="Cue Controls">
        <button id="flashCueToggle" class="cue-btn" aria-label="Toggle Flash Cue" type="button" title="Flash Cue">
            <span id="flashCueIcon">⚡</span>
        </button>
        <button id="beepCueToggle" class="cue-btn" aria-label="Toggle Beep Cue" type="button" title="Beep Cue">
            <span id="beepCueIcon">🔊</span>
        </button>
    </div>
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
                <li><strong>Reaction Accuracy:</strong> <span id="reactionAccuracy"><?= $reactionAccuracy ?>%</span></li>
            </ul>
        </section>
        <section class="panel meter" aria-label="Audience Reaction">
            <h2 class="panel-title">Audience Reaction</h2>
            <ul class="audience-info-list">
                <?php if (!file_exists(__DIR__ . '/../Database/sim_audience_on.flag')): ?>
                <li>🔥 Intensity: <span id="audienceIntensity"><?= $audienceIntensity ?></span></li>
                <?php endif; ?>
                <li>🔊 Volume: <span id="audienceVolume"><?= $audienceVolume ?></span></li>
                <li>👥 Responders: <span id="audienceResponders"><?= $responders ?></span></li>
            </ul>
        </section>
        <section class="panel" aria-label="Transfer Points">
            <h2 class="panel-title">💸 Transfer Points</h2>
            <form class="form-fields" action="<?= $basePath ?>/api/transfer_points.php" method="POST" autocomplete="off">
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

<div id="micRecordingIndicator" style="display:none; position: fixed; top: 12px; right: 18px; z-index: 10010; background: rgba(255,255,255,0.95); border-radius: 8px; padding: 6px 14px; box-shadow: 0 2px 8px rgba(0,0,0,0.08); font-weight: bold; color: #c92a2a; align-items: center; gap: 0.5em;">
    <span style="display:inline-block; width: 12px; height: 12px; background: #c92a2a; border-radius: 50%; margin-right: 8px; box-shadow: 0 0 6px 2px #faa2a2;"></span>
    Recording...
</div>

<script>
    window.userId = "<?= $_SESSION['user']['id'] ?? '' ?>";
    window.basePath = "<?= defined('BASE_PATH') ? BASE_PATH : '' ?>";
</script>
<script src="<?= defined('BASE_PATH') ? BASE_PATH : '' ?>/js/room.js"></script>