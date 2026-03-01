<?php
require_once 'config.php';

// Egyszerű admin hitelesítés (élesben jobb megoldás kell!)
$admin_logged_in = isset($_SESSION['admin_logged_in']) && $_SESSION['admin_logged_in'] === true;

// Bejelentkezés
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];
    
    // Egyszerű ellenőrzés (élesben adatbázisból kellene!)
    if ($username === 'admin' && $password === 'admin123') {
        $_SESSION['admin_logged_in'] = true;
        $admin_logged_in = true;
    } else {
        $login_error = "Hibás felhasználónév vagy jelszó!";
    }
}

// Kijelentkezés
if (isset($_GET['logout'])) {
    unset($_SESSION['admin_logged_in']);
    $admin_logged_in = false;
}

// Termék hozzáadása
if ($admin_logged_in && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_product'])) {
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $image = $_POST['image'] ?? '🌹';
    $description = $_POST['description'];
    
    $stmt = $conn->prepare("INSERT INTO products (name, price, stock, image, description) VALUES (?, ?, ?, ?, ?)");
    $stmt->bind_param("siiss", $name, $price, $stock, $image, $description);
    
    if ($stmt->execute()) {
        $success = "Termék sikeresen hozzáadva!";
    } else {
        $error = "Hiba a termék hozzáadása során!";
    }
}

// Termék szerkesztése
if ($admin_logged_in && isset($_POST['edit_product'])) {
    $id = $_POST['id'];
    $name = $_POST['name'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $image = $_POST['image'];
    $description = $_POST['description'];
    
    $stmt = $conn->prepare("UPDATE products SET name=?, price=?, stock=?, image=?, description=? WHERE id=?");
    $stmt->bind_param("siissi", $name, $price, $stock, $image, $description, $id);
    
    if ($stmt->execute()) {
        $success = "Termék sikeresen módosítva!";
    } else {
        $error = "Hiba a termék módosítása során!";
    }
}

// Termék törlése
if ($admin_logged_in && isset($_GET['delete'])) {
    $id = $_GET['delete'];
    
    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        $success = "Termék sikeresen törölve!";
    } else {
        $error = "Hiba a termék törlése során!";
    }
}

// Termékek lekérése
$products = [];
$sql = "SELECT * FROM products ORDER BY id DESC";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $products[] = $row;
    }
}

// Rendelések lekérése
$orders = [];
$sql = "SELECT * FROM orders ORDER BY created_at DESC LIMIT 10";
$result = $conn->query($sql);

if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
}
?>

<!DOCTYPE html>
<html lang="hu">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin - Örökrózsa Webáruház</title>
    <style>
        /* ... (az előző stílusok kiegészítve) ... */
        .admin-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
        }
        
        .admin-tab {
            padding: 10px 20px;
            background: #f0f0f0;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }
        
        .admin-tab.active {
            background: #764ba2;
            color: white;
        }
        
        .admin-content {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        
        .admin-section {
            display: none;
        }
        
        .admin-section.active {
            display: block;
        }
        
        .orders-table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .orders-table th,
        .orders-table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        
        .orders-table th {
            background: #f8f9fa;
            font-weight: bold;
        }
        
        .orders-table tr:hover {
            background: #f5f5f5;
        }
        
        .status-pending {
            background: #ffc107;
            color: #000;
            padding: 3px 8px;
            border-radius: 3px;
        }
        
        .status-processing {
            background: #17a2b8;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
        }
        
        .status-completed {
            background: #28a745;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
        }
        
        .status-cancelled {
            background: #dc3545;
            color: white;
            padding: 3px 8px;
            border-radius: 3px;
        }
        
        .login-form {
            max-width: 400px;
            margin: 50px auto;
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🌹 Örökrózsa Admin</h1>
            <div class="nav-buttons">
                <a href="index.php" class="nav-btn">Vissza a boltba</a>
                <?php if ($admin_logged_in): ?>
                <a href="?logout=1" class="nav-btn">Kijelentkezés</a>
                <?php endif; ?>
            </div>
        </header>

        <?php if (!$admin_logged_in): ?>
            <div class="login-form">
                <h2>Admin bejelentkezés</h2>
                
                <?php if (isset($login_error)): ?>
                    <div class="alert alert-error"><?php echo $login_error; ?></div>
                <?php endif; ?>
                
                <form method="POST">
                    <div class="form-group">
                        <label>Felhasználónév:</label>
                        <input type="text" name="username" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Jelszó:</label>
                        <input type="password" name="password" required>
                    </div>
                    
                    <button type="submit" name="login" class="btn">Bejelentkezés</button>
                </form>
            </div>
        <?php else: ?>
            
            <?php if (isset($success)): ?>
                <div class="alert alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <?php if (isset($error)): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="admin-tabs">
                <button class="admin-tab active" onclick="showTab('products')">Termékek</button>
                <button class="admin-tab" onclick="showTab('orders')">Rendelések</button>
                <button class="admin-tab" onclick="showTab('add')">Új termék</button>
            </div>
            
            <div class="admin-content">
                <!-- Termékek lista -->
                <div id="products-section" class="admin-section active">
                    <h2>Termékek kezelése</h2>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Név</th>
                                <th>Ár</th>
                                <th>Készlet</th>
                                <th>Műveletek</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($products as $product): ?>
                            <tr>
                                <td>#<?php echo $product['id']; ?></td>
                                <td><?php echo htmlspecialchars($product['name']); ?></td>
                                <td><?php echo number_format($product['price'], 0, ',', ' '); ?> Ft</td>
                                <td><?php echo $product['stock']; ?> db</td>
                                <td>
                                    <button class="btn" onclick="editProduct(<?php echo $product['id']; ?>)">Szerkeszt</button>
                                    <a href="?delete=<?php echo $product['id']; ?>" class="btn btn-danger" onclick="return confirm('Biztosan törlöd?')">Törlés</a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Rendelések -->
                <div id="orders-section" class="admin-section">
                    <h2>Legutóbbi rendelések</h2>
                    <table class="orders-table">
                        <thead>
                            <tr>
                                <th>Rendelés #</th>
                                <th>Név</th>
                                <th>Összeg</th>
                                <th>Státusz</th>
                                <th>Dátum</th>
                                <th>Művelet</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orders as $order): ?>
                            <tr>
                                <td>#<?php echo $order['id']; ?></td>
                                <td><?php echo htmlspecialchars($order['customer_name']); ?></td>
                                <td><?php echo number_format($order['total_amount'], 0, ',', ' '); ?> Ft</td>
                                <td>
                                    <span class="status-<?php echo $order['status']; ?>">
                                        <?php echo $order['status']; ?>
                                    </span>
                                </td>
                                <td><?php echo date('Y-m-d H:i', strtotime($order['created_at'])); ?></td>
                                <td>
                                    <button class="btn" onclick="viewOrder(<?php echo $order['id']; ?>)">Részletek</button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- Új termék hozzáadása -->
                <div id="add-section" class="admin-section">
                    <h2>Új termék hozzáadása</h2>
                    <form method="POST" class="checkout-form">
                        <div class="form-group">
                            <label>Termék neve:</label>
                            <input type="text" name="name" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Ár (Ft):</label>
                            <input type="number" name="price" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Készlet:</label>
                            <input type="number" name="stock" value="10" required>
                        </div>
                        
                        <div class="form-group">
                            <label>Kép (emoji vagy URL):</label>
                            <input type="text" name="image" value="🌹">
                        </div>
                        
                        <div class="form-group">
                            <label>Leírás:</label>
                            <textarea name="description" rows="3"></textarea>
                        </div>
                        
                        <button type="submit" name="add_product" class="btn">Termék hozzáadása</button>
                    </form>
                </div>
            </div>
            
            <script>
                function showTab(tab) {
                    document.querySelectorAll('.admin-section').forEach(section => {
                        section.classList.remove('active');
                    });
                    
                    document.querySelectorAll('.admin-tab').forEach(btn => {
                        btn.classList.remove('active');
                    });
                    
                    document.getElementById(tab + '-section').classList.add('active');
                    event.target.classList.add('active');
                }
                
                function editProduct(id) {
                    // Itt lehetne egy szerkesztő űrlap
                    alert('Szerkesztés funkció: ' + id);
                }
                
                function viewOrder(id) {
                    alert('Rendelés részletei: #' + id);
                }
            </script>
        <?php endif; ?>
    </div>
</body>
</html>
