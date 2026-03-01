<?php
require_once 'config.php';

// Kosár inicializálása ha még nem létezik
if (!isset($_SESSION['cart'])) {
    $_SESSION['cart'] = [];
}

// Rendelés leadása
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['checkout'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $address = $_POST['address'];
    
    if (empty($_SESSION['cart'])) {
        $error = "A kosár üres!";
    } else {
        // Rendelés összegének kiszámítása
        $total = 0;
        $items = [];
        
        foreach ($_SESSION['cart'] as $item) {
            $sql = "SELECT * FROM products WHERE id = " . $item['product_id'];
            $result = $conn->query($sql);
            $product = $result->fetch_assoc();
            
            $total += $product['price'] * $item['quantity'];
            $items[] = [
                'product' => $product,
                'quantity' => $item['quantity']
            ];
        }
        
        // Rendelés mentése
        $conn->begin_transaction();
        
        try {
            // Rendelés fejléc
            $stmt = $conn->prepare("INSERT INTO orders (customer_name, customer_email, customer_phone, customer_address, total_amount) VALUES (?, ?, ?, ?, ?)");
            $stmt->bind_param("ssssi", $name, $email, $phone, $address, $total);
            $stmt->execute();
            $order_id = $conn->insert_id;
            
            // Rendelés tételek
            foreach ($items as $item) {
                $product = $item['product'];
                $quantity = $item['quantity'];
                
                $stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, product_name, price, quantity) VALUES (?, ?, ?, ?, ?)");
                $stmt->bind_param("iisii", $order_id, $product['id'], $product['name'], $product['price'], $quantity);
                $stmt->execute();
                
                // Készlet csökkentése
                $stmt = $conn->prepare("UPDATE products SET stock = stock - ? WHERE id = ?");
                $stmt->bind_param("ii", $quantity, $product['id']);
                $stmt->execute();
            }
            
            $conn->commit();
            
            // Kosár ürítése
            $_SESSION['cart'] = [];
            
            $success = "Köszönjük a rendelést! Rendelés száma: #" . $order_id;
            
        } catch (Exception $e) {
            $conn->rollback();
            $error = "Hiba történt a rendelés feldolgozása során!";
        }
    }
}

// Mennyiség módosítása
if (isset($_GET['update'])) {
    $product_id = $_GET['product_id'];
    $action = $_GET['action'];
    
    foreach ($_SESSION['cart'] as &$item) {
        if ($item['product_id'] == $product_id) {
            if ($action === 'increase') {
                // Ellenőrizzük, hogy van-e elég készlet
                $sql = "SELECT stock FROM products WHERE id = $product_id";
                $result = $conn->query($sql);
                $product = $result->fetch_assoc();
                
                if ($product['stock'] > $item['quantity']) {
                    $item['quantity']++;
                }
            } elseif ($action === 'decrease' && $item['quantity'] > 1) {
                $item['quantity']--;
            }
            break;
        }
    }
    
    header('Location: cart.php');
    exit;
}

// Termék eltávolítása
if (isset($_GET['remove'])) {
    $product_id = $_GET['product_id'];
    
    $_SESSION['cart'] = array_filter($_SESSION['cart'], function($item) use ($product_id) {
        return $item['product_id'] != $product_id;
    });
    
    // Újraindexelés
    $_SESSION['cart'] = array_values($_SESSION['cart']);
    
    header('Location: cart.php');
    exit;
}

// Kosár tételek lekérése az adatbázisból
$cart_items = [];
$total = 0;

foreach ($_SESSION['cart'] as $item) {
    $sql = "SELECT * FROM products WHERE id = " . $item['product_id'];
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        $product['quantity'] = $item['quantity'];
        $cart_items[] = $product;
        $total += $product['price'] * $item['quantity'];
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kosár - Örökrózsa Webáruház</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <div class="container">
        <header>
            <h1>🌹 Örökrózsa</h1>
            <div class="nav-buttons">
                <a href="index.php" class="nav-btn">Főoldal</a>
                <a href="admin.php" class="nav-btn">Admin</a>
                <a href="cart.php" class="cart-icon">
                    🛒
                    <span class="cart-count"><?php echo count($_SESSION['cart']); ?></span>
                </a>
            </div>
        </header>

        <div class="cart-container">
            <h2>Kosár tartalma</h2>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if (empty($cart_items)): ?>
                <p>A kosár üres. <a href="index.php">Nézz szét a termékek között!</a></p>
            <?php else: ?>
                <?php foreach ($cart_items as $item): ?>
                <div class="cart-item">
                    <div class="cart-item-info">
                        <strong><?php echo htmlspecialchars($item['name']); ?></strong><br>
                        <small>Készlet: <?php echo $item['stock']; ?> db</small>
                    </div>
                    <div class="cart-item-price">
                        <?php echo number_format($item['price'], 0, ',', ' '); ?> Ft
                    </div>
                    <div class="cart-item-quantity">
                        <a href="?update=1&product_id=<?php echo $item['id']; ?>&action=decrease" class="quantity-btn">-</a>
                        <span><?php echo $item['quantity']; ?></span>
                        <a href="?update=1&product_id=<?php echo $item['id']; ?>&action=increase" class="quantity-btn">+</a>
                    </div>
                    <div>
                        <strong><?php echo number_format($item['price'] * $item['quantity'], 0, ',', ' '); ?> Ft</strong>
                    </div>
                    <a href="?remove=1&product_id=<?php echo $item['id']; ?>" class="btn btn-danger">❌</a>
                </div>
                <?php endforeach; ?>
                
                <div class="cart-total">
                    Összesen: <?php echo number_format($total, 0, ',', ' '); ?> Ft
                </div>
                
                <h3>Szállítási adatok</h3>
                <form method="POST" class="checkout-form">
                    <div class="form-group">
                        <label>Név:</label>
                        <input type="text" name="name" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Email:</label>
                        <input type="email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Telefonszám:</label>
                        <input type="tel" name="phone" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Cím:</label>
                        <textarea name="address" rows="3" required></textarea>
                    </div>
                    
                    <button type="submit" name="checkout" class="btn btn-success">Rendelés leadása</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
