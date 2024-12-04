<?php
// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require('connect.php');
include('navbar.php');

// Check if product_id is passed
$product_id = $_GET['id'] ?? null;
$captcha_error = $comment_error = $success_message = null;

// Generate CAPTCHA if not set
if (!isset($_SESSION['captcha_text'])) {
    $captcha_code = rand(1000, 9999);  // Simple numeric CAPTCHA
    $_SESSION['captcha_text'] = $captcha_code;
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CAPTCHA
    if (!isset($_POST['captcha']) || $_POST['captcha'] !== $_SESSION['captcha_text']) {
        $captcha_error = "Incorrect CAPTCHA. Please try again.";
    } else {
        // Process the comment
        $name = htmlspecialchars($_POST['name']);
        $comment = htmlspecialchars($_POST['comment']);
        
        if (empty($name) || empty($comment)) {
            $comment_error = "Both name and comment are required.";
        } else {
            // Insert the comment into the database
            $stmt = $pdo->prepare("INSERT INTO comments (product_id, name, comment, created_at) VALUES (:product_id, :name, :comment, NOW())");
            $stmt->execute([
                'product_id' => $product_id,
                'name' => $name,
                'comment' => $comment,
            ]);
            $success_message = "Comment submitted successfully!";
            // Regenerate CAPTCHA after submission
            unset($_SESSION['captcha_text']);
        }
    }
}

// Retrieve product details (if product_id is set)
if ($product_id) {
    $stmt = $pdo->prepare("SELECT name FROM products WHERE product_id = :id");
    $stmt->execute(['id' => $product_id]);
    $product = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$product) {
    echo "<p>Product not found.</p>";
    exit;
}

// Fetch comments in reverse chronological order
$comments = $pdo->prepare("SELECT name, comment, created_at FROM comments WHERE product_id = :id ORDER BY created_at DESC");
$comments->execute(['id' => $product_id]);
$comment_list = $comments->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Comments for <?= htmlspecialchars($product['name']) ?> | StyleHub</title>
    <link rel="stylesheet" href="../css/index.css">
</head>
<body>
<header><h1 class="main-title"><a href="index.php">StyleHub</a></h1></header>

<!-- Product Name -->
<h2>Comments for <?= htmlspecialchars($product['name']) ?></h2>

<!-- Comment Form -->
<div class="comment-section">
    <h3>Leave a Comment</h3>
    
    <?php if ($success_message): ?>
        <p class="success"><?= $success_message ?></p>
    <?php endif; ?>

    <?php if ($captcha_error): ?>
        <p class="error"><?= $captcha_error ?></p>
    <?php endif; ?>

    <?php if ($comment_error): ?>
        <p class="error"><?= $comment_error ?></p>
    <?php endif; ?>

    <form method="POST" action="">
        <label for="name">Name:</label>
        <input type="text" name="name" required>

        <label for="comment">Comment:</label>
        <textarea name="comment" required></textarea>

        <div class="captcha">
            <label for="captcha">Enter CAPTCHA: <?= isset($_SESSION['captcha_text']) ? $_SESSION['captcha_text'] : 'CAPTCHA not available' ?></label>
            <input type="text" name="captcha" required>
        </div>

        <button type="submit">Submit Comment</button>
    </form>
</div>

<!-- Display Comments -->
<div class="comments-list">
    <h3>Comments</h3>
    <?php if (empty($comment_list)): ?>
        <p>No comments yet.</p>
    <?php else: ?>
        <?php foreach ($comment_list as $comment): ?>
            <div class="comment">
                <strong><?= htmlspecialchars($comment['name']) ?></strong>
                <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                <p class="timestamp"><?= $comment['created_at'] ?></p>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<footer>
    <p>© 2024 by Khushleen Kaur. No rights reserved.</p>
</footer>
</body>
</html>
