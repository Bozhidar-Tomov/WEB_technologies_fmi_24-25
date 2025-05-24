<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Crowd Pulse') ?></title>
    <link rel="stylesheet" href="/css/layouts/layout.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Metal+Mania&display=swap" rel="stylesheet">
    <?php if (!empty($viewStyle)) : ?>
        <link rel="stylesheet" href="<?= htmlspecialchars($viewStyle) ?>">
    <?php endif; ?>
</head>

<body>
    <header>
        <div class="site-title">
            <h1>Crowd Pulse</h1>
            <h2>Feel the beat of every emotion</h2>
        </div>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer>
        &copy; <?= date('Y') ?>
        <span class="project-name">Crowd Pulse</span>
        <span class="separator">|</span>
        <span class="authors">Bozhidar Tomov, Mira Velikova</span>
    </footer>
</body>

</html>