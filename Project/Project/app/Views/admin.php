<?php
header("Refresh: 10");
?>
<main class="admin-main">    
    <section class="panels">
        <section class="panel" aria-label="Send Command">
            <?php
                $statusTypes = ['error', 'warning', 'success'];
                foreach ($statusTypes as $type):
                    if (!empty($_SESSION[$type])):
            ?>

            <div class="status status-<?= $type ?>" role="alert">
                <ul>
                    <?php foreach ($_SESSION[$type] as $message): ?>
                        <li><?= htmlspecialchars($message) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php unset($_SESSION[$type]); ?>
            </div>
            <?php
                endif;
                endforeach;
            ?>

            <h2 class="panel-title">Send Command</h2>
            <div id="simAudienceControl" style="margin-bottom:1em; display: flex; align-items: center; gap: 1em;">
                <button id="simAudienceBtn" type="button" class="btn btn-secondary">
                    <span id="simAudienceBtnText"><?php echo file_exists(__DIR__ . '/../Database/sim_audience_on.flag') ? 'Disable' : 'Enable'; ?></span> Simulated Audience
                </button>
                <span id="simAudienceFeedback" style="color:#2b8a3e;"></span>
            </div>
            <form id="commandForm" method="post" action="/admin/broadcast" class="form-fields">
                    <label for="commandType">Command Type:</label>
                    <select id="commandType" name="type" required>
                        <option value="applause">Applause</option>
                        <option value="cheer">Cheer</option>
                        <option value="boo">Boo</option>
                        <option value="murmur">Murmur</option>
                        <option value="stomp">Stomp</option>
                        <option value="silence">Silence</option>
                    </select>

                    <?php if (file_exists(__DIR__ . '/../Database/sim_audience_on.flag')): ?>
                    <div id="intensitySliderWrapper">
                        <label for="intensity">Intensity (1-100):</label>
                        <div class="slider-container">
                            <input type="range" id="intensity" name="intensity" min="1" max="100" value="50">
                            <span id="intensityValue">50</span>
                        </div>
                    </div>
                    <?php endif; ?>

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
            <h2 class="panel-title">Live Feedback</h2>
            <div class="connection-status">
                <div class="status-indicator" id="connectionStatus">
                    <?php
                    require_once __DIR__ . '/../Services/CommandService.php';
                    use App\Services\CommandService;
                    
                    $activeUsers = (new CommandService())->getActiveUserCount();
                    $sseStatus = $activeUsers > 0 ? 'online' : 'offline';
                    $statusText = $activeUsers > 0 ? 'SSE Server: Online' : 'SSE Server: Offline';
                    ?>
                    <span class="status-dot <?= $sseStatus ?>" id="statusDot"></span>
                    <span class="status-text" id="statusText"><?= $statusText ?></span>
                </div>
                <div class="last-command" id="lastCommandInfo">
                    <?php if (!empty($_SESSION['last_command'])): 
                        $cmd = $_SESSION['last_command'];
                    ?>
                        <strong>Last Command:</strong><br>
                        <b>Type:</b> <?= htmlspecialchars($cmd['type']) ?><br>
                        <b>Intensity:</b> <?= htmlspecialchars($cmd['intensity']) ?><br>
                        <b>Duration:</b> <?= htmlspecialchars($cmd['duration']) ?>s<br>
                        <b>Countdown:</b> <?= htmlspecialchars($cmd['countdown']) ?>s<br>
                        <b>Groups:</b> <?= htmlspecialchars($cmd['groups']) ?><br>
                        <b>Message:</b> <?= htmlspecialchars($cmd['message']) ?><br>
                        <b>Sent at:</b> <?= date('Y-m-d H:i:s', $cmd['timestamp']) ?>
                    <?php else: ?>
                        No commands sent yet
                    <?php endif; ?>
                    <?php unset($_SESSION['last_command']); ?>
                </div>
            </div>
            <div class="metrics">
                <div class="metric">
                    <label>Active Users:</label>
                    <span id="activeUsers"><?= $activeUsers ?></span>
                </div>
                <div class="metric">
                    <label>Current Volume:</label>
                    <span id="currentVolume"><?php
                        // Calculate average volume for current command
                        $currentVolume = 0;
                        $responseRate = 0;
                        $numResponded = 0;
                        $numActive = $activeUsers;
                        $avgVolume = 0;
                        $cmdId = null;
                        $micResults = [];
                        $resultsFile = __DIR__ . '/../Database/mic_results.json';
                        $activeCmdFile = __DIR__ . '/../Database/commands/active_command.json';
                        if (file_exists($activeCmdFile)) {
                            $activeCmd = json_decode(file_get_contents($activeCmdFile), true);
                            $cmdId = $activeCmd['id'] ?? null;
                        }
                        if ($cmdId && file_exists($resultsFile)) {
                            $micResults = json_decode(file_get_contents($resultsFile), true) ?: [];
                            $volumes = [];
                            $responded = 0;
                            foreach ($micResults as $result) {
                                if (($result['commandId'] ?? null) === $cmdId) {
                                    $volumes[] = $result['volume'] ?? 0;
                                    if (($result['reactionAccuracy'] ?? 0) >= 15) {
                                        $responded++;
                                    }
                                }
                            }
                            if (count($volumes) > 0) {
                                $avgVolume = round(array_sum($volumes) / count($volumes));
                            }
                            $numResponded = $responded;
                        }
                        echo $avgVolume . ' dB';
                    ?></span>
                </div>
                <div class="metric">
                    <label>Response Rate:</label>
                    <span id="responseRate"><?php
                        $rate = 0;
                        if ($numActive > 0) {
                            $rate = round(($numResponded / $numActive) * 100);
                        }
                        echo $rate . '%';
                    ?></span>
                </div>
            </div>
        </section>
    </section>
</main>

<script src="../js/admin.js"></script>