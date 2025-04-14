<?php
/**
 * Gestión de asignaciones de formularios a usuarios
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../includes/user.php';
require_once '../includes/form.php';

// Verificar autenticación
requireAuth();

// Obtener ID del formulario
$formId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;

// Verificar si el formulario existe
$form = Form::getById($formId);

if (!$form) {
    setAlert('danger', 'Formulario no encontrado');
    redirect(APP_URL . '/admin/forms.php');
}

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setAlert('danger', 'Error de validación del formulario');
        redirect(APP_URL . '/admin/form_assignments.php?form_id=' . $formId);
    }
    
    // Determinar la acción a realizar
    $formAction = $_POST['form_action'] ?? '';
    
    switch ($formAction) {
        case 'assign_users':
            // Asignar usuarios al formulario
            $userIds = isset($_POST['user_ids']) ? $_POST['user_ids'] : [];
            
            // Obtener usuarios actualmente asignados
            $assignedUsers = Form::getAssignedUsers($formId);
            $currentUserIds = array_column($assignedUsers, 'id');
            
            // Usuarios a asignar (nuevos)
            $usersToAssign = array_diff($userIds, $currentUserIds);
            
            // Usuarios a desasignar (eliminados)
            $usersToUnassign = array_diff($currentUserIds, $userIds);
            
            // Asignar nuevos usuarios
            $assignSuccess = true;
            foreach ($usersToAssign as $userId) {
                $result = Form::assignFormToUser($formId, $userId);
                if (!$result) {
                    $assignSuccess = false;
                }
            }
            
            // Desasignar usuarios eliminados
            $unassignSuccess = true;
            foreach ($usersToUnassign as $userId) {
                $result = Form::unassignFormFromUser($formId, $userId);
                if (!$result) {
                    $unassignSuccess = false;
                }
            }
            
            if ($assignSuccess && $unassignSuccess) {
                setAlert('success', 'Asignaciones actualizadas correctamente');
            } else {
                setAlert('danger', 'Error al actualizar algunas asignaciones');
            }
            
            redirect(APP_URL . '/admin/form_assignments.php?form_id=' . $formId);
            break;
    }
}

// Obtener todos los usuarios activos
$result = User::getAll(1, 1000); // Obtener hasta 1000 usuarios (ajustar según necesidad)
$users = $result['users'];

// Obtener usuarios asignados al formulario
$assignedUsers = Form::getAssignedUsers($formId);
$assignedUserIds = array_column($assignedUsers, 'id');

// Obtener mensaje de alerta
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Asignación de usuarios - <?php echo APP_NAME; ?></title>
    
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
                <?php echo generateMenu('forms'); ?>
            </div>
            
            <!-- Contenido -->
            <div class="col-md-9 col-lg-10 ms-sm-auto main-content">
                <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $alert['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Asignar usuarios al formulario: <?php echo $form['titulo']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/forms.php" class="btn btn-sm btn-outline-secondary">
                            <i class="material-icons">arrow_back</i> Volver a formularios
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Seleccionar usuarios</h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted mb-3">
                            Selecciona los usuarios que tendrán acceso a este formulario. Si no seleccionas ningún usuario, 
                            el formulario estará disponible para todos los usuarios activos.
                        </p>
                        
                        <form method="POST" action="<?php echo APP_URL; ?>/admin/form_assignments.php?form_id=<?php echo $formId; ?>">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="form_action" value="assign_users">
                            
                            <div class="mb-3">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th style="width: 50px;">
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="selectAll">
                                                    </div>
                                                </th>
                                                <th>Usuario</th>
                                                <th>Nombre completo</th>
                                                <th>Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (count($users) > 0): ?>
                                                <?php foreach ($users as $user): ?>
                                                <tr>
                                                    <td>
                                                        <div class="form-check">
                                                            <input class="form-check-input user-checkbox" type="checkbox" 
                                                                name="user_ids[]" 
                                                                value="<?php echo $user['id']; ?>"
                                                                <?php echo in_array($user['id'], $assignedUserIds) ? 'checked' : ''; ?>>
                                                        </div>
                                                    </td>
                                                    <td><?php echo $user['username']; ?></td>
                                                    <td><?php echo $user['nombre_completo']; ?></td>
                                                    <td>
                                                        <span class="badge bg-<?php echo ($user['estado'] === 'activo') ? 'success' : 'danger'; ?>">
                                                            <?php echo $user['estado']; ?>
                                                        </span>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            <?php else: ?>
                                                <tr>
                                                    <td colspan="4" class="text-center">No hay usuarios disponibles</td>
                                                </tr>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">save</i> Guardar asignaciones
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript personalizado -->
    <script src="<?php echo APP_URL; ?>/assets/js/script.js"></script>
    
    <!-- JavaScript para la página -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Seleccionar/deseleccionar todos los usuarios
            const selectAllCheckbox = document.getElementById('selectAll');
            const userCheckboxes = document.querySelectorAll('.user-checkbox');
            
            if (selectAllCheckbox) {
                selectAllCheckbox.addEventListener('change', function() {
                    userCheckboxes.forEach(checkbox => {
                        checkbox.checked = selectAllCheckbox.checked;
                    });
                });
                
                // Actualizar "Seleccionar todos" si todos los usuarios están seleccionados
                function updateSelectAll() {
                    let allChecked = true;
                    userCheckboxes.forEach(checkbox => {
                        if (!checkbox.checked) {
                            allChecked = false;
                        }
                    });
                    selectAllCheckbox.checked = allChecked;
                }
                
                userCheckboxes.forEach(checkbox => {
                    checkbox.addEventListener('change', updateSelectAll);
                });
                
                // Inicializar estado
                updateSelectAll();
            }
        });
    </script>
</body>
</html>
