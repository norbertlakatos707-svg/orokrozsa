<?php
require_once 'config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['product_id'])) {
    $product_id = $_POST['product_id'];
    
    // Termék ellenőrzése
    $sql = "SELECT * FROM products WHERE id = $product_id AND stock > 0";
    $result = $conn->query($sql);
    
    if ($result->num_rows > 0) {
        $product = $result->fetch_assoc();
        
        // Kosár inicializálása
        if (!isset($_SESSION['cart'])) {
            $_SESSION['cart'] = [];
        }
        
        // Termék keresése a kosárban
        $found = false;
        foreach ($_SESSION['cart'] as &$item) {
            if ($item['product_id'] == $product_id) {
                if ($item['quantity'] < $product['stock']) {
                    $item['quantity']++;
                }
                $found = true;
                break;
            }
        }
        
        // Ha nincs még a kosárban, hozzáadjuk
        if (!$found) {
            $_SESSION['cart'][] = [
                'product_id' => $product_id,
                'quantity' => 1
            ];
        }
    }
}

// Visszairányítás a főoldalra
header('Location: index.php');
exit;
?>
