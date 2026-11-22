<?php

// Start the session
session_start();

// Count visits
if (!isset($_SESSION['visits'])) {
    $_SESSION['visits'] = 1;
} else {
    $_SESSION['visits']++;
}

// Connect to database
require_once 'db.php';

// Handle message submission
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $author = trim($_POST['author'] ?? '');
    $body = trim($_POST['body'] ?? '');

    if ($author !== '' && $body !== '') {

        // Save message using prepared statement
        $sql = "INSERT INTO messages (author, body)
                VALUES (:author, :body)";

        $stmt = $pdo->prepare($sql);

        $stmt->execute([
            ':author' => $author,
            ':body' => $body
        ]);

        // Remember name if checkbox is checked
        if (isset($_POST['remember'])) {

            setcookie(
                'remembered_name',
                $author,
                [
                    'expires' => time() + (30 * 24 * 60 * 60),
                    'path' => '/',
                    'samesite' => 'Lax'
                ]
            );

        } else {
            // Remove previous remembered name
            setcookie(
                'remembered_name',
                '',
                [
                    'expires' => time() - 3600,
                    'path' => '/'
                ]
            );
        }

        // Prevent form resubmission
        header("Location: index.php");
        exit;
    }
}

// Search messages using GET
$search = trim($_GET['search'] ?? '');

if ($search !== '') {

    $sql = "SELECT * FROM messages
            WHERE author ILIKE :search
            OR body ILIKE :search
            ORDER BY created_at DESC";

    $stmt = $pdo->prepare($sql);

    $stmt->execute([
        ':search' => '%' . $search . '%'
    ]);

} else {

    // Display all messages, newest first
    $sql = "SELECT * FROM messages
            ORDER BY created_at DESC";

    $stmt = $pdo->query($sql);
}

$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get remembered name
$rememberedName = $_COOKIE['remembered_name'] ?? '';

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <title>ClassWall Message Board</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f2f4f7;
            margin: 0;
            padding: 30px;
        }

        .container {
            max-width: 700px;
            margin: auto;
            background: white;
            padding: 25px;
            border-radius: 10px;
        }

        h1 {
            text-align: center;
        }

        input, textarea, button {
            width: 100%;
            padding: 12px;
            margin-top: 10px;
            box-sizing: border-box;
        }

        textarea {
            height: 100px;
        }

        button {
            background: #2563eb;
            color: white;
            border: none;
            cursor: pointer;
        }

        .message {
            background: #f2f4f7;
            padding: 15px;
            margin-top: 15px;
            border-radius: 8px;
        }

        .message small {
            color: #666;
        }

        .remember {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .remember input {
            width: auto;
        }
    </style>
</head>

<body>

<div class="container">

    <h1>ClassWall Message Board</h1>

    <p>
        You've visited this page
        <strong><?= (int) $_SESSION['visits'] ?></strong>
        times during this session.
    </p>

    <hr>

    <h2>Post a Message</h2>

    <form method="POST" action="index.php">

        <label>Your Name</label>

        <input
            type="text"
            name="author"
            placeholder="Enter your name"
            value="<?= htmlspecialchars($rememberedName, ENT_QUOTES, 'UTF-8') ?>"
            required
        >

        <label>Your Message</label>

        <textarea
            name="body"
            placeholder="Write your message..."
            required
        ></textarea>

        <label class="remember">
            <input type="checkbox" name="remember">
            Remember my name
        </label>

        <button type="submit">Post Message</button>

    </form>

    <hr>

    <h2>Search Messages</h2>

    <form method="GET" action="index.php">

        <input
            type="text"
            name="search"
            placeholder="Search messages..."
            value="<?= htmlspecialchars($search, ENT_QUOTES, 'UTF-8') ?>"
        >

        <button type="submit">Search</button>

    </form>

    <hr>

    <h2>Messages</h2>

    <?php if (count($messages) > 0): ?>

        <?php foreach ($messages as $message): ?>

            <div class="message">

                <h3>
                    <?= htmlspecialchars($message['author'], ENT_QUOTES, 'UTF-8') ?>
                </h3>

                <p>
                    <?= nl2br(htmlspecialchars($message['body'], ENT_QUOTES, 'UTF-8')) ?>
                </p>

                <small>
                    Posted:
                    <?= htmlspecialchars($message['created_at'], ENT_QUOTES, 'UTF-8') ?>
                </small>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <p>No messages found.</p>

    <?php endif; ?>

</div>

</body>
</html>