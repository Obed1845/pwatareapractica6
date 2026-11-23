<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit;
}

$error = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);

    $sql = "SELECT u.id, u.username, u.password, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id WHERE u.email = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($row = $res->fetch_assoc()) {
        if ($password === $row['password']) {
            $_SESSION['user_id'] = $row['id'];
            $_SESSION['username'] = $row['username'];
            $_SESSION['role'] = $row['role_name'];
            header("Location: dashboard.php");
            exit;
        } else {
            $error = "Contraseña incorrecta.";
        }
    } else {
        $error = "Usuario no encontrado.";
    }
    $stmt->close();
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Gestión de Restaurante</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1e293b, #0f172a);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-card {
            width: 100%;
            max-width: 420px;
            border-radius: 14px;
            border: none;
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.35);
        }
    </style>
</head>
<body>
    <div class="card login-card p-4">
        <div class="card-body">
            <h3 class="text-center fw-bold mb-1">Restaurante Online</h3>
            <p class="text-center text-muted mb-4">Inicia sesión según tu rol</p>
            <?php if ($error): ?>
                <div class="alert alert-danger py-2"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="mb-3">
                    <label class="form-label">Correo electrónico</label>
                    <input type="email" name="email" class="form-control" required placeholder="admin@restaurante.com">
                </div>
                <div class="mb-3">
                    <label class="form-label">Contraseña</label>
                    <input type="password" name="password" class="form-control" required placeholder="12345">
                </div>
                <button type="submit" class="btn btn-primary w-100 fw-semibold">Ingresar</button>
            </form>
            <hr class="my-4">
            <div class="small text-muted">
                <strong>Cuentas de prueba (clave: 12345):</strong><br>
                Admin: admin@restaurante.com<br>
                Chef: chef@restaurante.com<br>
                Camarero: mesero@restaurante.com<br>
                Cliente: cliente@restaurante.com
            </div>
        </div>
    </div>
</body>
</html>