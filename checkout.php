<?php
/*w**
    Name: Khushleen Kaur
    Date: 29-11-2024
    Description: Checkout Page for Completing Orders
******/

session_start();

// Include the database connection
require_once 'connect.php';

// Process form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize and retrieve user input
    $firstName = htmlspecialchars($_POST['first_name']);
    $lastName = htmlspecialchars($_POST['last_name']);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $address = htmlspecialchars($_POST['address']);
    $city = htmlspecialchars($_POST['city']);
    $postalCode = htmlspecialchars($_POST['postal_code']);
    $total = calculateTotal($_SESSION['cart']);
    
    try {
        // Insert data into the checkout table
        $stmt = $pdo->prepare(
            "INSERT INTO checkout (first_name, last_name, email, address, city, postal_code, total, created_at) 
             VALUES (:first_name, :last_name, :email, :address, :city, :postal_code, :total, NOW())"
        );
        $stmt->execute([
            ':first_name' => $firstName,
            ':last_name' => $lastName,
            ':email' => $email,
            ':address' => $address,
            ':city' => $city,
            ':postal_code' => $postalCode,
            ':total' => $total
        ]);

        // Clear the cart after successful checkout
        unset($_SESSION['cart']);

        // Redirect or display success message
        header('Location: thank_you.php');
        exit;
    } catch (PDOException $e) {
        // Handle database error
        echo "Error: " . $e->getMessage();
    }
}

// Redirect to cart page if cart is empty
if (empty($_SESSION['cart'])) {
    header("Location: cart.php");
    exit();
}

// Function to calculate total
function calculateTotal($cart)
{
    $total = 0;
    foreach ($cart as $item) {
        $total += $item['price'] * $item['quantity'];
    }
    return $total;
}

// Handle form submission
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Validate inputs
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $address = trim($_POST['address']);
    $city = trim($_POST['city']);
    $postalCode = trim($_POST['postal_code']);

    if (empty($name)) $errors[] = "Name is required.";
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = "Valid email is required.";
    if (empty($address)) $errors[] = "Address is required.";
    if (empty($city)) $errors[] = "City is required.";
    if (empty($postalCode) || !preg_match('/^[A-Za-z]\d[A-Za-z] \d[A-Za-z]\d$/', $postalCode)) $errors[] = "Valid postal code is required (e.g., A1B 2C3).";

    // If no errors, process order
    if (empty($errors)) {
        // Simulate order processing
        $_SESSION['cart'] = []; // Clear the cart
        header("Location: confirmation.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Checkout</title>
    <link rel="stylesheet" href="../css/index.css">
</head>
<body>
<header>
    <h1>Checkout</h1>
</header>
<div class="checkout-container">
    <h2>Order Summary</h2>
    <table>
        <thead>
            <tr>
                <th>Product</th>
                <th>Price</th>
                <th>Quantity</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($_SESSION['cart'] as $productId => $product): ?>
                <tr>
                    <td><?= htmlspecialchars($product['name']) ?></td>
                    <td>$<?= number_format($product['price'], 2) ?></td>
                    <td><?= $product['quantity'] ?></td>
                    <td>$<?= number_format($product['price'] * $product['quantity'], 2) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <p><strong>Total: $<?= number_format(calculateTotal($_SESSION['cart']), 2) ?></strong></p>
</div>

<div class="checkout-form">
    <h2>Shipping & Billing Information</h2>
    <?php if (!empty($errors)): ?>
        <div class="errors">
            <ul>
                <?php foreach ($errors as $error): ?>
                    <li><?= htmlspecialchars($error) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    <form action="checkout.php" method="post">
        <label for="name">Full Name:</label>
        <input type="text" id="name" name="name" value="<?= isset($name) ? htmlspecialchars($name) : '' ?>" required>

        <label for="email">Email Address:</label>
        <input type="email" id="email" name="email" value="<?= isset($email) ? htmlspecialchars($email) : '' ?>" required>

        <label for="address">Address:</label>
        <input type="text" id="address" name="address" value="<?= isset($address) ? htmlspecialchars($address) : '' ?>" required>

        <label for="city">City:</label>
        <input type="text" id="city" name="city" value="<?= isset($city) ? htmlspecialchars($city) : '' ?>" required>

        <label for="postal_code">Postal Code:</label>
        <input type="text" id="postal_code" name="postal_code" value="<?= isset($postalCode) ? htmlspecialchars($postalCode) : '' ?>" required>

        <button type="submit" href="payment.php">Submit Order</button>
    </form>
</div>

<footer>
    <p>© 2024 by Khushleen Kaur. No rights reserved.</p>
</footer>
</body>
</html>
