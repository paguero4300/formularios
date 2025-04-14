<?php
/**
 * Gestión de usuarios
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/user.php';

// Verificar autenticación
requireAuth();

// Procesar acciones
$action = $_GET['action'] ?? '';
$userId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setAlert('danger', 'Error de validación del formulario');
        redirect(APP_URL . '/admin/users.php');
    }
    
    // Determinar la acción a realizar
    $formAction = $_POST['form_action'] ?? '';
    
    switch ($formAction) {
        case 'create':
            // Crear nuevo usuario
            $userData = [
                'username' => sanitize($_POST['username'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'nombre_completo' => sanitize($_POST['nombre_completo'] ?? ''),
                'estado' => sanitize($_POST['estado'] ?? 'activo')
            ];
            
            // Validar datos
            if (empty($userData['username']) || empty($userData['password']) || empty($userData['nombre_completo'])) {
                setAlert('danger', 'Todos los campos son obligatorios');
                redirect(APP_URL . '/admin/users.php?action=create');
            }
            
            // Crear usuario
            $result = User::create($userData);
            
            if ($result) {
                setAlert('success', 'Usuario creado correctamente');
                redirect(APP_URL . '/admin/users.php');
            } else {
                setAlert('danger', 'Error al crear el usuario. El nombre de usuario ya existe o se produjo un error en la base de datos');
                redirect(APP_URL . '/admin/users.php?action=create');
            }
            break;
            
        case 'update':
            // Actualizar usuario existente
            $userId = (int)($_POST['user_id'] ?? 0);
            $userData = [
                'username' => sanitize($_POST['username'] ?? ''),
                'nombre_completo' => sanitize($_POST['nombre_completo'] ?? ''),
                'estado' => sanitize($_POST['estado'] ?? 'activo')
            ];
            
            // Validar datos
            if (empty($userData['username']) || empty($userData['nombre_completo'])) {
                setAlert('danger', 'Todos los campos son obligatorios');
                redirect(APP_URL . '/admin/users.php?action=edit&id=' . $userId);
            }
            
            // Actualizar usuario
            $result = User::update($userId, $userData);
            
            if ($result) {
                setAlert('success', 'Usuario actualizado correctamente');
                redirect(APP_URL . '/admin/users.php');
            } else {
                setAlert('danger', 'Error al actualizar el usuario. El nombre de usuario ya existe o se produjo un error en la base de datos');
                redirect(APP_URL . '/admin/users.php?action=edit&id=' . $userId);
            }
            break;
            
        case 'delete':
            // Eliminar usuario
            $userId = (int)($_POST['user_id'] ?? 0);
            
            // Eliminar usuario
            $result = User::delete($userId);
            
            if ($result) {
                setAlert('success', 'Usuario eliminado correctamente');
            } else {
                setAlert('danger', 'Error al eliminar el usuario');
            }
            
            redirect(APP_URL . '/admin/users.php');
            break;
            
        case 'change_password':
            // Cambiar contraseña
            $userId = (int)($_POST['user_id'] ?? 0);
            $newPassword = $_POST['new_password'] ?? '';
            $confirmPassword = $_POST['confirm_password'] ?? '';
            
            // Validar datos
            if (empty($newPassword) || empty($confirmPassword)) {
                setAlert('danger', 'Todos los campos son obligatorios');
                redirect(APP_URL . '/admin/users.php?action=change_password&id=' . $userId);
            }
            
            if ($newPassword !== $confirmPassword) {
                setAlert('danger', 'Las contraseñas no coinciden');
                redirect(APP_URL . '/admin/users.php?action=change_password&id=' . $userId);
            }
            
            // Cambiar contraseña
            $result = Auth::resetPassword($userId, $newPassword);
            
            if ($result) {
                setAlert('success', 'Contraseña cambiada correctamente');
                redirect(APP_URL . '/admin/users.php');
            } else {
                setAlert('danger', 'Error al cambiar la contraseña');
                redirect(APP_URL . '/admin/users.php?action=change_password&id=' . $userId);
            }
            break;
            
        case 'change_status':
            // Cambiar estado
            $userId = (int)($_POST['user_id'] ?? 0);
            $newStatus = sanitize($_POST['estado'] ?? 'activo');
            
            // Cambiar estado
            $result = User::changeStatus($userId, $newStatus);
            
            if ($result) {
                setAlert('success', 'Estado del usuario cambiado correctamente');
            } else {
                setAlert('danger', 'Error al cambiar el estado del usuario');
            }
            
            redirect(APP_URL . '/admin/users.php');
            break;
    }
}

// Obtener datos según la acción
$user = null;
$users = [];
$pagination = null;

switch ($action) {
    case 'create':
        // Mostrar formulario de creación
        break;
        
    case 'edit':
        // Obtener datos del usuario a editar
        $user = User::getById($userId);
        
        if (!$user) {
            setAlert('danger', 'Usuario no encontrado');
            redirect(APP_URL . '/admin/users.php');
        }
        break;
        
    case 'change_password':
        // Obtener datos del usuario para cambiar contraseña
        $user = User::getById($userId);
        
        if (!$user) {
            setAlert('danger', 'Usuario no encontrado');
            redirect(APP_URL . '/admin/users.php');
        }
        break;
        
    default:
        // Listar usuarios
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $searchTerm = $_GET['search'] ?? '';
        
        if (!empty($searchTerm)) {
            $result = User::search($searchTerm, $page);
        } else {
            $result = User::getAll($page);
        }
        
        $users = $result['users'];
        $pagination = $result['pagination'];
        break;
}

// Obtener mensaje de alerta
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Usuarios - <?php echo APP_NAME; ?></title>
    
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet">
    
    <!-- Material Icons -->
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    
    <!-- Estilos personalizados -->
    <link href="<?php echo APP_URL; ?>/assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Barra de navegación -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="<?php echo APP_URL; ?>/admin/index.php">
                <i class="material-icons">security</i>
                <?php echo APP_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" id="sidebarToggle">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="material-icons">account_circle</i>
                            <?php echo Auth::fullName(); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/admin/profile.php">Mi perfil</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item" href="<?php echo APP_URL; ?>/logout.php">Cerrar sesión</a></li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <!-- Contenido principal -->
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3 col-lg-2 sidebar">
                <?php echo generateMenu('users'); ?>
            </div>
            
            <!-- Contenido -->
            <div class="col-md-9 col-lg-10 ms-sm-auto main-content">
                <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $alert['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'create'): ?>
                <!-- Formulario de creación de usuario -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Crear usuario</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="material-icons">arrow_back</i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="<?php echo APP_URL; ?>/admin/users.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="form_action" value="create">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de usuario</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Contraseña</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nombre_completo" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">save</i> Guardar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php elseif ($action === 'edit' && $user): ?>
                <!-- Formulario de edición de usuario -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar usuario</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="material-icons">arrow_back</i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="<?php echo APP_URL; ?>/admin/users.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="form_action" value="update">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Nombre de usuario</label>
                                <input type="text" class="form-control" id="username" name="username" value="<?php echo $user['username']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="nombre_completo" class="form-label">Nombre completo</label>
                                <input type="text" class="form-control" id="nombre_completo" name="nombre_completo" value="<?php echo $user['nombre_completo']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="activo" <?php echo ($user['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo ($user['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">save</i> Guardar cambios
                                </button>
                                <a href="<?php echo APP_URL; ?>/admin/users.php?action=change_password&id=<?php echo $user['id']; ?>" class="btn btn-outline-primary">
                                    <i class="material-icons">lock</i> Cambiar contraseña
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php elseif ($action === 'change_password' && $user): ?>
                <!-- Formulario de cambio de contraseña -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Cambiar contraseña</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-secondary">
                            <i class="material-icons">arrow_back</i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="<?php echo APP_URL; ?>/admin/users.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="form_action" value="change_password">
                            <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="username" class="form-label">Usuario</label>
                                <input type="text" class="form-control" id="username" value="<?php echo $user['username']; ?>" readonly>
                            </div>
                            
                            <div class="mb-3">
                                <label for="new_password" class="form-label">Nueva contraseña</label>
                                <input type="password" class="form-control" id="new_password" name="new_password" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirmar contraseña</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">lock_reset</i> Cambiar contraseña
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Lista de usuarios -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Usuarios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/users.php?action=create" class="btn btn-sm btn-primary">
                            <i class="material-icons">person_add</i> Nuevo usuario
                        </a>
                    </div>
                </div>
                
                <!-- Buscador -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="<?php echo APP_URL; ?>/admin/users.php" class="row g-3">
                            <div class="col-md-10">
                                <input type="text" class="form-control" id="search" name="search" placeholder="Buscar por nombre de usuario o nombre completo" value="<?php echo $_GET['search'] ?? ''; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="material-icons">search</i> Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de usuarios -->
                <div class="card">
                    <div class="card-body">
                        <?php if (count($users) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Usuario</th>
                                        <th>Nombre completo</th>
                                        <th>Estado</th>
                                        <th>Fecha de creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td><?php echo $user['username']; ?></td>
                                        <td><?php echo $user['nombre_completo']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($user['estado'] === 'activo') ? 'success' : 'danger'; ?>">
                                                <?php echo $user['estado']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($user['fecha_creacion']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo APP_URL; ?>/admin/users.php?action=edit&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Editar">
                                                    <i class="material-icons">edit</i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/admin/users.php?action=change_password&id=<?php echo $user['id']; ?>" class="btn btn-sm btn-outline-warning" data-bs-toggle="tooltip" title="Cambiar contraseña">
                                                    <i class="material-icons">lock</i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $user['id']; ?>" title="Eliminar">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                                
                                                <!-- Cambiar estado -->
                                                <form method="POST" action="<?php echo APP_URL; ?>/admin/users.php" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="form_action" value="change_status">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="estado" value="<?php echo ($user['estado'] === 'activo') ? 'inactivo' : 'activo'; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-<?php echo ($user['estado'] === 'activo') ? 'secondary' : 'success'; ?>" data-bs-toggle="tooltip" title="<?php echo ($user['estado'] === 'activo') ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="material-icons"><?php echo ($user['estado'] === 'activo') ? 'toggle_off' : 'toggle_on'; ?></i>
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Modal de confirmación de eliminación -->
                                            <div class="modal fade" id="deleteModal<?php echo $user['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $user['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $user['id']; ?>">Confirmar eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Estás seguro de que deseas eliminar al usuario <strong><?php echo $user['username']; ?></strong>? Esta acción no se puede deshacer.
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="POST" action="<?php echo APP_URL; ?>/admin/users.php">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="form_action" value="delete">
                                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                                <button type="submit" class="btn btn-danger">Eliminar</button>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        
                        <!-- Paginación -->
                        <?php if ($pagination['total_pages'] > 1): ?>
                        <div class="mt-4">
                            <?php
                            $baseUrl = APP_URL . '/admin/users.php';
                            if (!empty($_GET['search'])) {
                                $baseUrl .= '?search=' . urlencode($_GET['search']) . '&';
                            } else {
                                $baseUrl .= '?';
                            }
                            echo generatePagination($pagination['current_page'], $pagination['total_pages'], $baseUrl);
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <p class="text-center text-muted">No se encontraron usuarios</p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript personalizado -->
    <script src="<?php echo APP_URL; ?>/assets/js/script.js"></script>
</body>
</html>
