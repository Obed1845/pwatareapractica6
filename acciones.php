<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id']) || $_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: index.php");
    exit;
}

$accion = $_POST['accion'] ?? '';
$role = $_SESSION['role'];
$user_id = $_SESSION['user_id'];

if ($accion === 'agregar_plato' && $role === 'Administrator') {
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stmt = $conn->prepare("INSERT INTO dishes (name, description, price) VALUES (?, ?, ?)");
    $stmt->bind_param("ssd", $name, $description, $price);
    $stmt->execute();
    $stmt->close();
} elseif ($accion === 'editar_plato' && $role === 'Administrator') {
    $id = (int)$_POST['id'];
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $price = (float)$_POST['price'];
    $stmt = $conn->prepare("UPDATE dishes SET name = ?, description = ?, price = ? WHERE id = ?");
    $stmt->bind_param("ssdi", $name, $description, $price, $id);
    $stmt->execute();
    $stmt->close();
} elseif ($accion === 'eliminar_plato' && $role === 'Administrator') {
    $id = (int)$_POST['id'];
    $stmt = $conn->prepare("DELETE FROM dishes WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->close();
} elseif ($accion === 'agregar_usuario' && $role === 'Administrator') {
    $username = trim($_POST['username']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $role_id = (int)$_POST['role_id'];
    $stmt = $conn->prepare("INSERT INTO users (username, email, password, role_id) VALUES (?, ?, ?, ?)");
    $stmt->bind_param("sssi", $username, $email, $password, $role_id);
    $stmt->execute();
    $stmt->close();
} elseif ($accion === 'eliminar_usuario' && $role === 'Administrator') {
    $id = (int)$_POST['id'];
    if ($id !== $user_id) {
        $stmt = $conn->prepare("DELETE FROM users WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $stmt->close();
    }
} elseif ($accion === 'crear_pedido' && ($role === 'Client' || $role === 'Waiter')) {
    $target_user = ($role === 'Waiter' && !empty($_POST['client_id'])) ? (int)$_POST['client_id'] : $user_id;
    $dish_id = (int)$_POST['dish_id'];
    $quantity = max(1, (int)$_POST['quantity']);
    $stmt = $conn->prepare("INSERT INTO orders (user_id, dish_id, quantity, status) VALUES (?, ?, ?, 'Pending')");
    $stmt->bind_param("iii", $target_user, $dish_id, $quantity);
    $stmt->execute();
    $stmt->close();
} elseif ($accion === 'cambiar_estado' && ($role === 'Chef' || $role === 'Waiter')) {
    $id = (int)$_POST['id'];
    $status = $_POST['status'];
    $permitidos = ($role === 'Chef') ? ['Preparing', 'Ready to serve'] : ['Served'];
    if (in_array($status, $permitidos)) {
        $stmt = $conn->prepare("UPDATE orders SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $status, $id);
        $stmt->execute();
        $stmt->close();
    }
}

$conn->close();
header("Location: dashboard.php");
exit;
?>