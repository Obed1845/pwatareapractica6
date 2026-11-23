<?php
session_start();
require 'db.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: index.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'];
$role = $_SESSION['role'];

$dishes = $conn->query("SELECT * FROM dishes ORDER BY id DESC");
$roles_list = $conn->query("SELECT * FROM roles");
$clients_list = $conn->query("SELECT u.id, u.username FROM users u JOIN roles r ON u.role_id = r.id WHERE r.name = 'Client'");

if ($role === 'Client') {
    $stmt = $conn->prepare("SELECT o.id, d.name AS dish_name, d.price, o.quantity, o.status, u.username FROM orders o JOIN dishes d ON o.dish_id = d.id JOIN users u ON o.user_id = u.id WHERE o.user_id = ? ORDER BY o.id DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $orders = $stmt->get_result();
} else {
    $orders = $conn->query("SELECT o.id, d.name AS dish_name, d.price, o.quantity, o.status, u.username FROM orders o JOIN dishes d ON o.dish_id = d.id JOIN users u ON o.user_id = u.id ORDER BY o.id DESC");
}

$users_list = null;
if ($role === 'Administrator') {
    $users_list = $conn->query("SELECT u.id, u.username, u.email, r.name AS role_name FROM users u JOIN roles r ON u.role_id = r.id ORDER BY u.id ASC");
}

function badgeClass($status) {
    if ($status === 'Pending') return 'bg-warning text-dark';
    if ($status === 'Preparing') return 'bg-info text-dark';
    if ($status === 'Ready to serve') return 'bg-primary';
    return 'bg-success';
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panel - <?php echo htmlspecialchars($role); ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #f1f5f9;
        }
        .navbar {
            background-color: #0f172a;
        }
        .card {
            border: none;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.06);
        }
        .dish-card {
            transition: transform 0.2s ease;
        }
        .dish-card:hover {
            transform: translateY(-4px);
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-dark px-4 py-3 mb-4">
        <span class="navbar-brand fw-bold">Restaurante Online</span>
        <div class="d-flex align-items-center gap-3 text-white">
            <span><?php echo htmlspecialchars($username); ?> <span class="badge bg-secondary ms-1"><?php echo htmlspecialchars($role); ?></span></span>
            <a href="logout.php" class="btn btn-outline-light btn-sm">Cerrar Sesión</a>
        </div>
    </nav>

    <div class="container pb-5">
        <?php if ($role === 'Administrator'): ?>
            <ul class="nav nav-tabs mb-4" id="adminTabs" role="tablist">
                <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-menu" type="button">Menú</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-orders" type="button">Pedidos</button></li>
                <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-users" type="button">Usuarios</button></li>
            </ul>

            <div class="tab-content">
                <div class="tab-pane fade show active" id="tab-menu">
                    <div class="card p-4 mb-4">
                        <h5 class="fw-bold mb-3">Agregar Nuevo Plato</h5>
                        <form action="acciones.php" method="POST" class="row g-3">
                            <input type="hidden" name="accion" value="agregar_plato">
                            <div class="col-md-3"><input type="text" name="name" class="form-control" placeholder="Nombre del plato" required></div>
                            <div class="col-md-5"><input type="text" name="description" class="form-control" placeholder="Descripción" required></div>
                            <div class="col-md-2"><input type="number" step="0.01" name="price" class="form-control" placeholder="Precio" required></div>
                            <div class="col-md-2"><button type="submit" class="btn btn-success w-100">Agregar</button></div>
                        </form>
                    </div>

                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Platos Registrados</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-dark">
                                    <tr><th>ID</th><th>Nombre</th><th>Descripción</th><th>Precio</th><th>Acciones</th></tr>
                                </thead>
                                <tbody>
                                    <?php while($d = $dishes->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $d['id']; ?></td>
                                        <td><?php echo htmlspecialchars($d['name']); ?></td>
                                        <td><?php echo htmlspecialchars($d['description']); ?></td>
                                        <td>$<?php echo number_format($d['price'], 2); ?></td>
                                        <td>
                                            <button class="btn btn-warning btn-sm" onclick="abrirModalEditar(<?php echo $d['id']; ?>, '<?php echo htmlspecialchars($d['name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($d['description'], ENT_QUOTES); ?>', <?php echo $d['price']; ?>)">Editar</button>
                                            <form action="acciones.php" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar este plato?');">
                                                <input type="hidden" name="accion" value="eliminar_plato">
                                                <input type="hidden" name="id" value="<?php echo $d['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                            </form>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-orders">
                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Monitoreo de Pedidos</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-dark">
                                    <tr><th>ID</th><th>Cliente</th><th>Plato</th><th>Cant.</th><th>Total</th><th>Estado</th></tr>
                                </thead>
                                <tbody>
                                    <?php while($o = $orders->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $o['id']; ?></td>
                                        <td><?php echo htmlspecialchars($o['username']); ?></td>
                                        <td><?php echo htmlspecialchars($o['dish_name']); ?></td>
                                        <td><?php echo $o['quantity']; ?></td>
                                        <td>$<?php echo number_format($o['price'] * $o['quantity'], 2); ?></td>
                                        <td><span class="badge <?php echo badgeClass($o['status']); ?>"><?php echo $o['status']; ?></span></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="tab-users">
                    <div class="card p-4 mb-4">
                        <h5 class="fw-bold mb-3">Crear Cuenta de Usuario</h5>
                        <form action="acciones.php" method="POST" class="row g-3">
                            <input type="hidden" name="accion" value="agregar_usuario">
                            <div class="col-md-3"><input type="text" name="username" class="form-control" placeholder="Usuario" required></div>
                            <div class="col-md-3"><input type="email" name="email" class="form-control" placeholder="Correo" required></div>
                            <div class="col-md-2"><input type="password" name="password" class="form-control" placeholder="Contraseña" required></div>
                            <div class="col-md-2">
                                <select name="role_id" class="form-select" required>
                                    <?php while($r = $roles_list->fetch_assoc()): ?>
                                        <option value="<?php echo $r['id']; ?>"><?php echo htmlspecialchars($r['name']); ?></option>
                                    <?php endwhile; ?>
                                </select>
                            </div>
                            <div class="col-md-2"><button type="submit" class="btn btn-primary w-100">Crear</button></div>
                        </form>
                    </div>

                    <div class="card p-4">
                        <h5 class="fw-bold mb-3">Usuarios del Sistema</h5>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-dark">
                                    <tr><th>ID</th><th>Usuario</th><th>Correo</th><th>Rol</th><th>Acción</th></tr>
                                </thead>
                                <tbody>
                                    <?php while($u = $users_list->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo $u['id']; ?></td>
                                        <td><?php echo htmlspecialchars($u['username']); ?></td>
                                        <td><?php echo htmlspecialchars($u['email']); ?></td>
                                        <td><span class="badge bg-secondary"><?php echo htmlspecialchars($u['role_name']); ?></span></td>
                                        <td>
                                            <?php if ($u['id'] != $user_id): ?>
                                            <form action="acciones.php" method="POST" class="d-inline" onsubmit="return confirm('¿Eliminar usuario?');">
                                                <input type="hidden" name="accion" value="eliminar_usuario">
                                                <input type="hidden" name="id" value="<?php echo $u['id']; ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">Eliminar</button>
                                            </form>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="modal fade" id="modalEditarPlato" tabindex="-1">
                <div class="modal-dialog">
                    <form action="acciones.php" method="POST" class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Editar Plato</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <input type="hidden" name="accion" value="editar_plato">
                            <input type="hidden" name="id" id="edit-id">
                            <div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="name" id="edit-name" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Descripción</label><input type="text" name="description" id="edit-description" class="form-control" required></div>
                            <div class="mb-3"><label class="form-label">Precio</label><input type="number" step="0.01" name="price" id="edit-price" class="form-control" required></div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                            <button type="submit" class="btn btn-primary">Guardar Cambios</button>
                        </div>
                    </form>
                </div>
            </div>

        <?php elseif ($role === 'Chef'): ?>
            <div class="card p-4">
                <h4 class="fw-bold mb-3">Panel de Cocina - Pedidos</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr><th>ID</th><th>Cliente</th><th>Plato</th><th>Cantidad</th><th>Estado</th><th>Actualizar</th></tr>
                        </thead>
                        <tbody>
                            <?php while($o = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $o['id']; ?></td>
                                <td><?php echo htmlspecialchars($o['username']); ?></td>
                                <td><?php echo htmlspecialchars($o['dish_name']); ?></td>
                                <td><?php echo $o['quantity']; ?></td>
                                <td><span class="badge <?php echo badgeClass($o['status']); ?>"><?php echo $o['status']; ?></span></td>
                                <td>
                                    <?php if ($o['status'] === 'Pending'): ?>
                                        <form action="acciones.php" method="POST" class="d-inline">
                                            <input type="hidden" name="accion" value="cambiar_estado">
                                            <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                            <input type="hidden" name="status" value="Preparing">
                                            <button type="submit" class="btn btn-info btn-sm">En Preparación</button>
                                        </form>
                                    <?php elseif ($o['status'] === 'Preparing'): ?>
                                        <form action="acciones.php" method="POST" class="d-inline">
                                            <input type="hidden" name="accion" value="cambiar_estado">
                                            <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                            <input type="hidden" name="status" value="Ready to serve">
                                            <button type="submit" class="btn btn-primary btn-sm">Listo para Servir</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Sin acciones</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($role === 'Waiter'): ?>
            <div class="card p-4 mb-4">
                <h5 class="fw-bold mb-3">Registrar Pedido en Nombre de un Cliente</h5>
                <form action="acciones.php" method="POST" class="row g-3">
                    <input type="hidden" name="accion" value="crear_pedido">
                    <div class="col-md-4">
                        <select name="client_id" class="form-select" required>
                            <option value="">Seleccionar Cliente...</option>
                            <?php while($c = $clients_list->fetch_assoc()): ?>
                                <option value="<?php echo $c['id']; ?>"><?php echo htmlspecialchars($c['username']); ?></option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="dish_id" class="form-select" required>
                            <option value="">Seleccionar Plato...</option>
                            <?php while($d = $dishes->fetch_assoc()): ?>
                                <option value="<?php echo $d['id']; ?>"><?php echo htmlspecialchars($d['name']); ?> ($<?php echo number_format($d['price'], 2); ?>)</option>
                            <?php endwhile; ?>
                        </select>
                    </div>
                    <div class="col-md-2"><input type="number" name="quantity" class="form-control" min="1" value="1" required></div>
                    <div class="col-md-2"><button type="submit" class="btn btn-success w-100">Crear Pedido</button></div>
                </form>
            </div>

            <div class="card p-4">
                <h5 class="fw-bold mb-3">Lista de Pedidos</h5>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr><th>ID</th><th>Cliente</th><th>Plato</th><th>Cant.</th><th>Estado</th><th>Acción</th></tr>
                        </thead>
                        <tbody>
                            <?php while($o = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $o['id']; ?></td>
                                <td><?php echo htmlspecialchars($o['username']); ?></td>
                                <td><?php echo htmlspecialchars($o['dish_name']); ?></td>
                                <td><?php echo $o['quantity']; ?></td>
                                <td><span class="badge <?php echo badgeClass($o['status']); ?>"><?php echo $o['status']; ?></span></td>
                                <td>
                                    <?php if ($o['status'] === 'Ready to serve'): ?>
                                        <form action="acciones.php" method="POST" class="d-inline">
                                            <input type="hidden" name="accion" value="cambiar_estado">
                                            <input type="hidden" name="id" value="<?php echo $o['id']; ?>">
                                            <input type="hidden" name="status" value="Served">
                                            <button type="submit" class="btn btn-success btn-sm">Marcar Entregado</button>
                                        </form>
                                    <?php else: ?>
                                        <span class="text-muted small">Esperando cocina / Entregado</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php elseif ($role === 'Client'): ?>
            <h4 class="fw-bold mb-3">Menú del Restaurante</h4>
            <div class="row g-4 mb-5">
                <?php while($d = $dishes->fetch_assoc()): ?>
                <div class="col-md-4">
                    <div class="card dish-card h-100 p-3">
                        <div class="card-body d-flex flex-column">
                            <h5 class="card-title fw-bold"><?php echo htmlspecialchars($d['name']); ?></h5>
                            <p class="card-text text-muted flex-grow-1"><?php echo htmlspecialchars($d['description']); ?></p>
                            <p class="fs-5 fw-bold text-primary mb-2">$<?php echo number_format($d['price'], 2); ?></p>
                            <form action="acciones.php" method="POST" class="d-flex gap-2 align-items-center">
                                <input type="hidden" name="accion" value="crear_pedido">
                                <input type="hidden" name="dish_id" value="<?php echo $d['id']; ?>">
                                <input type="number" name="quantity" class="form-control form-control-sm" style="width: 75px;" min="1" value="1" oninput="calcularSubtotal(this, <?php echo $d['price']; ?>, 'sub-<?php echo $d['id']; ?>')" required>
                                <button type="submit" class="btn btn-primary btn-sm flex-grow-1">Pedir (<span id="sub-<?php echo $d['id']; ?>">$<?php echo number_format($d['price'], 2); ?></span>)</button>
                            </form>
                        </div>
                    </div>
                </div>
                <?php endwhile; ?>
            </div>

            <div class="card p-4">
                <h4 class="fw-bold mb-3">Historial y Estado de Mis Pedidos</h4>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-dark">
                            <tr><th>ID</th><th>Plato</th><th>Cantidad</th><th>Total</th><th>Estado</th></tr>
                        </thead>
                        <tbody>
                            <?php while($o = $orders->fetch_assoc()): ?>
                            <tr>
                                <td>#<?php echo $o['id']; ?></td>
                                <td><?php echo htmlspecialchars($o['dish_name']); ?></td>
                                <td><?php echo $o['quantity']; ?></td>
                                <td>$<?php echo number_format($o['price'] * $o['quantity'], 2); ?></td>
                                <td><span class="badge <?php echo badgeClass($o['status']); ?>"><?php echo $o['status']; ?></span></td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function abrirModalEditar(id, name, description, price) {
            document.getElementById('edit-id').value = id;
            document.getElementById('edit-name').value = name;
            document.getElementById('edit-description').value = description;
            document.getElementById('edit-price').value = price;
            const modal = new bootstrap.Modal(document.getElementById('modalEditarPlato'));
            modal.show();
        }

        function calcularSubtotal(input, precio, spanId) {
            const cantidad = Math.max(1, parseInt(input.value) || 1);
            const total = (cantidad * precio).toFixed(2);
            document.getElementById(spanId).innerText = '$' + total;
        }
    </script>
</body>
</html>
<?php
$conn->close();
?>