<!DOCTYPE html>
<html>

<head>
    <title>Emotion Director</title>
    <!-- <link rel="stylesheet" href="/css/views/index.css"> -->
</head>

<body>
    <h1>Emotion Director Interface</h1>
    <form method="POST" action="/send-command">
        <label>Command:</label>
        <select name="command">
            <option value="clap">Clap</option>
            <option value="cheer">Cheer</option>
            <option value="boo">Boo</option>
            <option value="murmur">Murmur</option>
            <option value="stomp">Stomp</option>
        </select>
        <label>Group:</label>
        <input type="text" name="group" placeholder="all">
        <label>Intensity (0-100):</label>
        <input type="number" name="intensity" min="0" max="100" value="50">
        <button type="submit">Send</button>
    </form>
    <script src="/js/reactions.js"></script>
</body>

</html>