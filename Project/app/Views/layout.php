<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($title ?? 'Crowd Pulse') ?></title>
    <link rel="shortcut icon" href="/favicon.ico" type="image/x-icon">
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="/css/layouts/layout.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Metal+Mania&display=swap" rel="stylesheet">
    <?php if (!empty($viewStyle)) : ?>
        <link rel="stylesheet" href="/<?= str_replace('../', '', htmlspecialchars($viewStyle)) ?>">
    <?php endif; ?>
</head>

<body>
    <header>
        <a class="site-title-link" href="/">
            <div class="site-title">
                <h1>Crowd Pulse</h1>
                <h2>Feel the beat of every emotion</h2>
            </div>
        </a>
        <?php if (isset($_SESSION['user'])): ?>
            <form action="/logout" method="get" class="header-logout">
                <button type="submit" class="btn btn-secondary">Logout</button>
            </form>
        <?php endif; ?>
    </header>

    <main>
        <?= $content ?>
    </main>

    <footer>
        <small>&copy; <?= date('Y') ?>
            <span class="project-name">Crowd Pulse</span>
            <span class="separator" aria-hidden="true">|</span>
            <span class="authors">Bozhidar Tomov, Mira Velikova</span>
        </small>
    </footer>
</body>

</html>