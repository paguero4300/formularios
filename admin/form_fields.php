<?php
/**
 * Gestión de campos de formulario
 * Panel administrativo Dr Security
 */

// Incluir archivos necesarios
require_once '../config/config.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
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
        redirect(APP_URL . '/admin/form_fields.php?form_id=' . $formId);
    }
    
    // Determinar la acción a realizar
    $formAction = $_POST['form_action'] ?? '';
    
    switch ($formAction) {
        case 'add_field':
            // Añadir nuevo campo
            $fieldData = [
                'id_formulario' => $formId,
                'tipo_campo' => sanitize($_POST['tipo_campo'] ?? ''),
                'etiqueta' => sanitize($_POST['etiqueta'] ?? ''),
                'requerido' => isset($_POST['requerido']) ? 1 : 0,
                'orden' => (int)($_POST['orden'] ?? 0)
            ];
            
            // Validar datos
            if (empty($fieldData['tipo_campo']) || empty($fieldData['etiqueta'])) {
                setAlert('danger', 'Todos los campos son obligatorios');
                redirect(APP_URL . '/admin/form_fields.php?form_id=' . $formId);
            }
            
            // Añadir campo
            $result = Form::addField($fieldData);
            
            if ($result) {
                setAlert('success', 'Campo añadido correctamente');
            } else {
                setAlert('danger', 'Error al añadir el campo');
            }
            
            redirect(APP_URL . '/admin/form_fields.php?form_id=' . $formId);
            break;
            
        case 'update_field':
            // Actualizar campo existente
            $fieldId = (int)($_POST['field_id'] ?? 0);
            $fieldData = [
                'tipo_campo' => sanitize($_POST['tipo_campo'] ?? ''),
                'etiqueta' => sanitize($_POST['etiqueta'] ?? ''),
                'requerido' => isset($_POST['requerido']) ? 1 : 0,
                'orden' => (int)($_POST['orden'] ?? 0)
            ];
            
            // Validar datos
            if (empty($fieldData['tipo_campo']) || empty($fieldData['etiqueta'])) {
                setAlert('danger', 'Todos los campos son obligatorios');
                redirect(APP_URL . '/admin/form_fields.php?form_id=' . $formId);
            }
            
            // Actualizar campo
            $result = Form::updateField($fieldId, $fieldData);
            
            if ($result) {
                setAlert('success', 'Campo actualizado correctamente');
            } else {
                setAlert('danger', 'Error al actualizar el campo');
            }
            
            redirect(APP_URL . '/admin/form_fields.php?form_id=' . $formId);
            break;
            
        case 'delete_field':
            // Eliminar campo
            $fieldId = (int)($_POST['field_id'] ?? 0);
            
            // Eliminar campo
            $result = Form::deleteField($fieldId);
            
            if ($result) {
                setAlert('success', 'Campo eliminado correctamente');
            } else {
                setAlert('danger', 'Error al eliminar el campo');
            }
            
            redirect(APP_URL . '/admin/form_fields.php?form_id=' . $formId);
            break;
    }
}

// Obtener campos del formulario
$fields = Form::getFields($formId);

// Obtener mensaje de alerta
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campos del formulario - <?php echo APP_NAME; ?></title>
    
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
                    <h1 class="h2">Campos del formulario: <?php echo $form['titulo']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/forms.php" class="btn btn-sm btn-outline-secondary">
                            <i class="material-icons">arrow_back</i> Volver a formularios
                        </a>
                    </div>
                </div>
                
                <!-- Formulario para añadir campo -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Añadir nuevo campo</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="<?php echo APP_URL; ?>/admin/form_fields.php?form_id=<?php echo $formId; ?>" class="row g-3">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="form_action" value="add_field">
                            
                            <div class="col-md-4">
                                <label for="tipo_campo" class="form-label">Tipo de campo</label>
                                <select class="form-select" id="tipo_campo" name="tipo_campo" required>
                                    <option value="lugar">Lugar</option>
                                    <option value="fecha_hora">Fecha y hora</option>
                                    <option value="ubicacion_gps">Ubicación GPS</option>
                                    <option value="comentario">Comentario</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4">
                                <label for="etiqueta" class="form-label">Etiqueta</label>
                                <input type="text" class="form-control" id="etiqueta" name="etiqueta" required>
                            </div>
                            
                            <div class="col-md-2">
                                <label for="orden" class="form-label">Orden</label>
                                <input type="number" class="form-control" id="orden" name="orden" value="<?php echo count($fields) + 1; ?>" min="1">
                            </div>
                            
                            <div class="col-md-2">
                                <label class="form-check-label d-block">&nbsp;</label>
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="requerido" name="requerido" checked>
                                    <label class="form-check-label" for="requerido">
                                        Requerido
                                    </label>
                                </div>
                            </div>
                            
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">add</i> Añadir campo
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Lista de campos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Campos existentes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($fields) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Tipo</th>
                                        <th>Etiqueta</th>
                                        <th>Requerido</th>
                                        <th>Orden</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fields as $field): ?>
                                    <tr>
                                        <td><?php echo $field['id']; ?></td>
                                        <td>
                                            <?php
                                            $tipoLabel = '';
                                            switch ($field['tipo_campo']) {
                                                case 'lugar':
                                                    $tipoLabel = 'Lugar';
                                                    break;
                                                case 'fecha_hora':
                                                    $tipoLabel = 'Fecha y hora';
                                                    break;
                                                case 'ubicacion_gps':
                                                    $tipoLabel = 'Ubicación GPS';
                                                    break;
                                                case 'comentario':
                                                    $tipoLabel = 'Comentario';
                                                    break;
                                            }
                                            echo $tipoLabel;
                                            ?>
                                        </td>
                                        <td><?php echo $field['etiqueta']; ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($field['requerido']) ? 'success' : 'secondary'; ?>">
                                                <?php echo ($field['requerido']) ? 'Sí' : 'No'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo $field['orden']; ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $field['id']; ?>">
                                                    <i class="material-icons">edit</i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $field['id']; ?>">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                            </div>
                                            
                                            <!-- Modal de edición -->
                                            <div class="modal fade" id="editModal<?php echo $field['id']; ?>" tabindex="-1" aria-labelledby="editModalLabel<?php echo $field['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="editModalLabel<?php echo $field['id']; ?>">Editar campo</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <form method="POST" action="<?php echo APP_URL; ?>/admin/form_fields.php?form_id=<?php echo $formId; ?>">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="form_action" value="update_field">
                                                                <input type="hidden" name="field_id" value="<?php echo $field['id']; ?>">
                                                                
                                                                <div class="mb-3">
                                                                    <label for="tipo_campo_<?php echo $field['id']; ?>" class="form-label">Tipo de campo</label>
                                                                    <select class="form-select" id="tipo_campo_<?php echo $field['id']; ?>" name="tipo_campo" required>
                                                                        <option value="lugar" <?php echo ($field['tipo_campo'] === 'lugar') ? 'selected' : ''; ?>>Lugar</option>
                                                                        <option value="fecha_hora" <?php echo ($field['tipo_campo'] === 'fecha_hora') ? 'selected' : ''; ?>>Fecha y hora</option>
                                                                        <option value="ubicacion_gps" <?php echo ($field['tipo_campo'] === 'ubicacion_gps') ? 'selected' : ''; ?>>Ubicación GPS</option>
                                                                        <option value="comentario" <?php echo ($field['tipo_campo'] === 'comentario') ? 'selected' : ''; ?>>Comentario</option>
                                                                    </select>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="etiqueta_<?php echo $field['id']; ?>" class="form-label">Etiqueta</label>
                                                                    <input type="text" class="form-control" id="etiqueta_<?php echo $field['id']; ?>" name="etiqueta" value="<?php echo $field['etiqueta']; ?>" required>
                                                                </div>
                                                                
                                                                <div class="mb-3">
                                                                    <label for="orden_<?php echo $field['id']; ?>" class="form-label">Orden</label>
                                                                    <input type="number" class="form-control" id="orden_<?php echo $field['id']; ?>" name="orden" value="<?php echo $field['orden']; ?>" min="1">
                                                                </div>
                                                                
                                                                <div class="mb-3 form-check">
                                                                    <input type="checkbox" class="form-check-input" id="requerido_<?php echo $field['id']; ?>" name="requerido" <?php echo ($field['requerido']) ? 'checked' : ''; ?>>
                                                                    <label class="form-check-label" for="requerido_<?php echo $field['id']; ?>">Campo requerido</label>
                                                                </div>
                                                                
                                                                <div class="d-grid gap-2">
                                                                    <button type="submit" class="btn btn-primary">Guardar cambios</button>
                                                                </div>
                                                            </form>
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <!-- Modal de confirmación de eliminación -->
                                            <div class="modal fade" id="deleteModal<?php echo $field['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $field['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $field['id']; ?>">Confirmar eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Estás seguro de que deseas eliminar el campo <strong><?php echo $field['etiqueta']; ?></strong>? Esta acción no se puede deshacer.
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="POST" action="<?php echo APP_URL; ?>/admin/form_fields.php?form_id=<?php echo $formId; ?>">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="form_action" value="delete_field">
                                                                <input type="hidden" name="field_id" value="<?php echo $field['id']; ?>">
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
                        <?php else: ?>
                        <p class="text-center text-muted">No hay campos definidos para este formulario</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js"></script>
    
    <!-- JavaScript personalizado -->
    <script src="<?php echo APP_URL; ?>/assets/js/script.js"></script>
</body>
</html>
