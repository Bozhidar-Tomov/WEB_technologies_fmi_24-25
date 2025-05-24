<?php
$title = "Page Not Found";
$viewStyle = "/css/views/404.css";
ob_start();
?>

<section class="not-found">
    <h1>404 - Page Not Found</h1>
    <p>Sorry, the page you are looking for does not exist.</p>
    <a href="/">Go back to the homepage</a>
</section>

<?php
$content = ob_get_clean();
include 'layout.php';
