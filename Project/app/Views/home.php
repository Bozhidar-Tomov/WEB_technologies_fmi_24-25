<div class="common-container">
    <?php if (!empty($data['title'])): ?>
        <h2 class="page-title"><?= htmlspecialchars($data['title']) ?></h2>
    <?php endif; ?>

    <div class="buttons">
        <a href="/login" class="btn btn-primary">Login</a>
        <a href="/register" class="btn btn-secondary">Register</a>
    </div>

    <p><a href="/emotion" class="link">🎬 Try the Emotion Director</a></p>
</div>