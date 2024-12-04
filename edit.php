<?php
session_start();

// Check if user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

require('connect.php');

// Check request method
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $product_id = $_POST['product_id'];
    $name = $_POST['name'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock_quantity = $_POST['stock_quantity'];
    $image_url = null; // Initialize as null

    // Handle image upload
    if (isset($_FILES['image']['name']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $targetDir = "uploads/";
        $imageName = basename($_FILES['image']['name']);
        $targetFile = $targetDir . $imageName;

        // Create directory if it doesn't exist
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0777, true);
        }

        // Move uploaded file
        if (move_uploaded_file($_FILES['image']['tmp_name'], $targetFile)) {
            $image_url = $targetFile; // Save the new file path
        } else {
            echo "<script>
                alert('Error: Unable to upload the image. Please try again.');
                window.history.back();
            </script>";
            exit;
        }
    }

    try {
        // Update product details in the database
        if ($image_url) {
            // If a new image is uploaded, update all fields including the image_url
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ?, image_url = ? WHERE product_id = ?");
            $stmt->execute([$name, $description, $price, $stock_quantity, $image_url, $product_id]);
        } else {
            // If no new image is uploaded, update all fields except the image_url
            $stmt = $pdo->prepare("UPDATE products SET name = ?, description = ?, price = ?, stock_quantity = ? WHERE product_id = ?");
            $stmt->execute([$name, $description, $price, $stock_quantity, $product_id]);
        }

        // Show success prompt and redirect
        echo "<script>
            alert('Product updated successfully!');
            window.location.href = 'admin_dashboard.php';
        </script>";
        exit;
    } catch (PDOException $e) {
        echo "<script>
            alert('Error updating product: " . htmlspecialchars($e->getMessage()) . "');
            window.history.back();
        </script>";
        exit;
    }
} else {
    $product_id = $_GET['id'];

    try {
        // Fetch product details
        $stmt = $pdo->prepare("SELECT * FROM products WHERE product_id = ?");
        $stmt->execute([$product_id]);
        $product = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$product) {
            echo "<script>
                alert('Product not found.');
                window.location.href = 'admin_dashboard.php';
            </script>";
            exit;
        }
    } catch (PDOException $e) {
        echo "<script>
            alert('Error fetching product: " . htmlspecialchars($e->getMessage()) . "');
            window.location.href = 'admin_dashboard.php';
        </script>";
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Product</title>
    <link rel="stylesheet" href="../css/index.css">
    <style>
        /* General body styles */
body {
    font-family: Arial, sans-serif;
    background-color: #f4f4f4;
    margin: 0;
    padding: 0;
}

/* Main container */
.container {
    width: 80%;
    margin: 0 auto;
    padding: 20px;
    background-color: #fff;
    box-shadow: 0 0 10px rgba(0, 0, 0, 0.1);
    border-radius: 8px;
    margin-top: 50px;
}

/* Title styles */
h1 {
    text-align: center;
    color: #333;
    font-size: 2em;
    margin-bottom: 20px;
}

/* Form styles */
form {
    display: flex;
    flex-direction: column;
    gap: 15px;
}

/* Label styles */
label {
    font-weight: bold;
    color: #555;
    margin-bottom: 5px;
}

/* Input field styles */
input[type="text"], input[type="number"], textarea, input[type="file"] {
    padding: 10px;
    border: 1px solid #ccc;
    border-radius: 4px;
    font-size: 1em;
    width: 100%;
}

/* Input field focus */
input[type="text"]:focus, input[type="number"]:focus, textarea:focus, input[type="file"]:focus {
    border-color: #007BFF;
    outline: none;
}

/* Textarea specific */
textarea {
    resize: vertical;
    min-height: 100px;
}

/* Image preview */
img {
    max-width: 200px;
    max-height: 200px;
    border: 1px solid #ccc;
    border-radius: 4px;
    margin-bottom: 10px;
}

/* Button styles */
button {
    padding: 10px 20px;
    background-color: #28a745;
    color: #fff;
    border: none;
    border-radius: 4px;
    font-size: 1.1em;
    cursor: pointer;
    align-self: center;
    transition: background-color 0.3s ease;
}

/* Button hover effect */
button:hover {
    background-color: #218838;
}

/* Error message styles */
.error {
    color: red;
    font-size: 0.9em;
}

/* Media queries for responsiveness */
@media (max-width: 768px) {
    .container {
        width: 95%;
        padding: 10px;
    }

    h1 {
        font-size: 1.8em;
    }

    button {
        width: 100%;
        padding: 12px;
    }
}

        </style>
</head>
<body>
    <h1>Edit Product</h1>
    <form action="edit.php" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="product_id" value="<?= htmlspecialchars($product['product_id']) ?>">

        <label for="name">Name:</label>
        <input type="text" id="name" name="name" value="<?= htmlspecialchars($product['name']) ?>" required>

        <label for="description">Description:</label>
        <textarea id="description" name="description" required><?= htmlspecialchars($product['description']) ?></textarea>

        <label for="price">Price:</label>
        <input type="number" step="0.01" id="price" name="price" value="<?= htmlspecialchars($product['price']) ?>" required>

        <label for="stock_quantity">Stock Quantity:</label>
        <input type="number" id="stock_quantity" name="stock_quantity" value="<?= htmlspecialchars($product['stock_quantity']) ?>" required>

        <label for="current_image">Current Image:</label>
        <img src="<?= htmlspecialchars($product['image_url']) ?>" alt="Current Image" style="max-width: 200px; max-height: 200px;">

        <label for="image">Upload New Image (optional):</label>
        <input type="file" id="image" name="image" accept="image/*">

        <button type="submit">Update Product</button>
    </form>
</body>
</html>
