<?php
require_once 'config.php';

// Termékek lekérése az adatbázisból
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($sql);
$products = [];

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Örökrózsa Webáruház</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }

        header {
            background: white;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        h1 {
            color: #764ba2;
            font-size: 2.5em;
        }

        .nav-buttons {
            display: flex;
            gap: 10px;
            align-items: center;
        }

        .nav-btn {
            padding: 10px 20px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: all 0.3s;
            text-decoration: none;
            color: #333;
            background: #f0f0f0;
        }

        .nav-btn:hover {
            background: #764ba2;
            color: white;
        }

        .cart-icon {
            position: relative;
            cursor: pointer;
            font-size: 1.5em;
            padding: 5px 10px;
            background: #f0f0f0;
            border-radius: 5px;
            text-decoration: none;
            color: #333;
        }

        .cart-count {
            position: absolute;
            top: -8px;
            right: -8px;
            background: #dc3545;
            color: white;
            border-radius: 50%;
            width: 20px;
            height: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 12px;
        }

        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .product-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
            transition: transform 0.3s;
        }

        .product-card:hover {
            transform: translateY(-5px);
        }

        .product-image {
            width: 100%;
            height: 200px;
            background: linear-gradient(45deg, #ff6b6b, #ff8e8e);
            border-radius: 10px;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 4em;
        }

        .product-title {
            font-size: 1.2em;
            font-weight: bold;
            margin-bottom: 10px;
            color: #333;
        }

        .product-description {
            color: #666;
            margin-bottom: 10px;
            font-size: 0.9em;
            line-height: 1.4;
        }

        .product-price {
            color: #764ba2;
            font-weight: bold;
            font-size: 1.3em;
            margin-bottom: 15px;
        }

        .product-stock {
            color: #28a745;
            margin-bottom: 15px;
            font-size: 0.9em;
        }

        .product-stock.low {
            color: #dc3545;
        }

        .buy-btn {
            width: 100%;
            padding: 10px;
            background: #764ba2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
        }

        .buy-btn:hover:not(:disabled) {
            background: #5a3780;
        }

        .buy-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }

        /* Kosár oldal stílusok */
        .cart-container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }

        .cart-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            border-bottom: 1px solid #eee;
        }

        .cart-item:last-child {
            border-bottom: none;
        }

        .cart-item-info {
            flex: 2;
        }

        .cart-item-price {
            font-weight: bold;
            color: #764ba2;
            margin: 0 20px;
        }

        .cart-item-quantity {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .quantity-btn {
            width: 30px;
            height: 30px;
            border: none;
            background: #f0f0f0;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .quantity-btn:hover {
            background: #764ba2;
            color: white;
        }

        .cart-total {
            text-align: right;
            font-size: 1.5em;
            font-weight: bold;
            margin: 20px 0;
            color: #333;
        }

        .checkout-form {
            max-width: 500px;
            margin: 0 auto;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #333;
        }

        .form-group input,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 5px;
            font-size: 1em;
        }

        .form-group input:focus,
        .form-group textarea:focus {
            outline: none;
            border-color: #764ba2;
        }

        .btn {
            padding: 10px 20px;
            background: #764ba2;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
            transition: background 0.3s;
            text-decoration: none;
            display: inline-block;
        }

        .btn:hover {
            background: #5a3780;
        }

        .btn-danger {
            background: #dc3545;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-success {
            background: #28a745;
        }

        .btn-success:hover {
            background: #218838;
        }

        .alert {
            padding: 15px;
            border-radius: 5px;
            margin-bottom: 20px;
        }

        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
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
                    <span class="cart-count">
                        <?php echo isset($_SESSION['cart']) ? count($_SESSION['cart']) : 0; ?>
                    </span>
                </a>
            </div>
        </header>

        <div class="products-grid">
            <?php foreach ($products as $product): ?>
            <div class="product-card">
                <div class="product-image">
                    <?php echo $product['image'] ?? '🌹'; ?>
                </div>
                <div class="product-title">
                    <?php echo htmlspecialchars($product['name']); ?>
                </div>
                <div class="product-description">
                    <?php echo htmlspecialchars($product['description'] ?? ''); ?>
                </div>
                <div class="product-price">
                    <?php echo number_format($product['price'], 0, ',', ' '); ?> Ft
                </div>
                <div class="product-stock <?php echo $product['stock'] < 5 ? 'low' : ''; ?>">
                    Készlet: <?php echo $product['stock']; ?> db
                </div>
                <form action="add-to-cart.php" method="POST">
                    <input type="hidden" name="product_id" value="<?php echo $product['id']; ?>">
                    <button type="submit" class="buy-btn" <?php echo $product['stock'] == 0 ? 'disabled' : ''; ?>>
                        <?php echo $product['stock'] == 0 ? 'Elfogyott' : 'Kosárba'; ?>
                    </button>
                </form>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</body>
</html>
