<?php
/**
 * Gestión de formularios
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

// Procesar acciones
$action = $_GET['action'] ?? '';
$formId = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setAlert('danger', 'Error de validación del formulario');
        redirect(APP_URL . '/admin/forms.php');
    }
    
    // Determinar la acción a realizar
    $formAction = $_POST['form_action'] ?? '';
    
    switch ($formAction) {
        case 'create':
            // Crear nuevo formulario
            $formData = [
                'titulo' => sanitize($_POST['titulo'] ?? ''),
                'descripcion' => sanitize($_POST['descripcion'] ?? ''),
                'estado' => sanitize($_POST['estado'] ?? 'activo')
            ];
            
            // Validar datos
            if (empty($formData['titulo'])) {
                setAlert('danger', 'El título del formulario es obligatorio');
                redirect(APP_URL . '/admin/forms.php?action=create');
            }
            
            // Crear formulario
            $formId = Form::create($formData);
            
            if ($formId) {
                // Si se creó el formulario, añadir los campos
                if (isset($_POST['tipo_campo']) && is_array($_POST['tipo_campo'])) {
                    foreach ($_POST['tipo_campo'] as $index => $tipoCampo) {
                        $fieldData = [
                            'id_formulario' => $formId,
                            'tipo_campo' => sanitize($tipoCampo),
                            'etiqueta' => sanitize($_POST['etiqueta'][$index] ?? ''),
                            'requerido' => isset($_POST['requerido'][$index]) ? 1 : 0,
                            'orden' => $index + 1
                        ];
                        
                        Form::addField($fieldData);
                    }
                }
                
                setAlert('success', 'Formulario creado correctamente');
                redirect(APP_URL . '/admin/forms.php');
            } else {
                setAlert('danger', 'Error al crear el formulario');
                redirect(APP_URL . '/admin/forms.php?action=create');
            }
            break;
            
        case 'update':
            // Actualizar formulario existente
            $formId = (int)($_POST['form_id'] ?? 0);
            $formData = [
                'titulo' => sanitize($_POST['titulo'] ?? ''),
                'descripcion' => sanitize($_POST['descripcion'] ?? ''),
                'estado' => sanitize($_POST['estado'] ?? 'activo')
            ];
            
            // Validar datos
            if (empty($formData['titulo'])) {
                setAlert('danger', 'El título del formulario es obligatorio');
                redirect(APP_URL . '/admin/forms.php?action=edit&id=' . $formId);
            }
            
            // Actualizar formulario
            $result = Form::update($formId, $formData);
            
            if ($result) {
                setAlert('success', 'Formulario actualizado correctamente');
                redirect(APP_URL . '/admin/forms.php');
            } else {
                setAlert('danger', 'Error al actualizar el formulario');
                redirect(APP_URL . '/admin/forms.php?action=edit&id=' . $formId);
            }
            break;
            
        case 'delete':
            // Eliminar formulario
            $formId = (int)($_POST['form_id'] ?? 0);
            
            // Eliminar formulario
            $result = Form::delete($formId);
            
            if ($result) {
                setAlert('success', 'Formulario eliminado correctamente');
            } else {
                setAlert('danger', 'Error al eliminar el formulario');
            }
            
            redirect(APP_URL . '/admin/forms.php');
            break;
            
        case 'change_status':
            // Cambiar estado
            $formId = (int)($_POST['form_id'] ?? 0);
            $newStatus = sanitize($_POST['estado'] ?? 'activo');
            
            // Cambiar estado
            $result = Form::changeStatus($formId, $newStatus);
            
            if ($result) {
                setAlert('success', 'Estado del formulario cambiado correctamente');
            } else {
                setAlert('danger', 'Error al cambiar el estado del formulario');
            }
            
            redirect(APP_URL . '/admin/forms.php');
            break;
    }
}

// Obtener datos según la acción
$form = null;
$fields = [];
$forms = [];
$pagination = null;

switch ($action) {
    case 'create':
        // Mostrar formulario de creación
        break;
        
    case 'edit':
        // Obtener datos del formulario a editar
        $form = Form::getById($formId);
        
        if (!$form) {
            setAlert('danger', 'Formulario no encontrado');
            redirect(APP_URL . '/admin/forms.php');
        }
        
        // Obtener campos del formulario
        $fields = Form::getFields($formId);
        break;
        
    case 'fields':
        // Obtener datos del formulario para gestionar campos
        $form = Form::getById($formId);
        
        if (!$form) {
            setAlert('danger', 'Formulario no encontrado');
            redirect(APP_URL . '/admin/forms.php');
        }
        
        // Obtener campos del formulario
        $fields = Form::getFields($formId);
        break;
        
    default:
        // Listar formularios
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        $searchTerm = $_GET['search'] ?? '';
        
        if (!empty($searchTerm)) {
            $result = Form::search($searchTerm, $page);
        } else {
            $result = Form::getAll($page);
        }
        
        $forms = $result['forms'];
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
    <title>Formularios - <?php echo APP_NAME; ?></title>
    
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
                
                <?php if ($action === 'create'): ?>
                <!-- Formulario de creación de formulario -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Crear formulario</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/forms.php" class="btn btn-sm btn-outline-secondary">
                            <i class="material-icons">arrow_back</i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="<?php echo APP_URL; ?>/admin/forms.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="form_action" value="create">
                            
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del formulario</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="activo">Activo</option>
                                    <option value="inactivo">Inactivo</option>
                                </select>
                            </div>
                            
                            <h4 class="mt-4 mb-3">Campos del formulario</h4>
                            
                            <div id="fieldContainer">
                                <!-- Campo 1 -->
                                <div class="card mb-3 form-field-card">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="tipo_campo_0" class="form-label">Tipo de campo</label>
                                                    <select class="form-select" id="tipo_campo_0" name="tipo_campo[]" required>
                                                        <option value="lugar">Lugar</option>
                                                        <option value="fecha_hora">Fecha y hora</option>
                                                        <option value="ubicacion_gps">Ubicación GPS</option>
                                                        <option value="comentario">Comentario</option>
                                                    </select>
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="mb-3">
                                                    <label for="etiqueta_0" class="form-label">Etiqueta</label>
                                                    <input type="text" class="form-control" id="etiqueta_0" name="etiqueta[]" required>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label class="form-check-label d-block">&nbsp;</label>
                                                    <div class="form-check">
                                                        <input class="form-check-input" type="checkbox" id="requerido_0" name="requerido[]" checked>
                                                        <label class="form-check-label" for="requerido_0">
                                                            Campo requerido
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="col-md-1">
                                                <div class="mb-3">
                                                    <label class="d-block">&nbsp;</label>
                                                    <button type="button" class="btn btn-danger btn-sm btn-remove-field" disabled>
                                                        <i class="material-icons">delete</i>
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="button" id="addFieldButton" class="btn btn-outline-primary">
                                    <i class="material-icons">add</i> Añadir campo
                                </button>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">save</i> Guardar formulario
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Template para nuevos campos -->
                <template id="fieldTemplate">
                    <div class="card mb-3 form-field-card">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="tipo_campo___INDEX__" class="form-label">Tipo de campo</label>
                                        <select class="form-select" id="tipo_campo___INDEX__" name="tipo_campo[]" required>
                                            <option value="lugar">Lugar</option>
                                            <option value="fecha_hora">Fecha y hora</option>
                                            <option value="ubicacion_gps">Ubicación GPS</option>
                                            <option value="comentario">Comentario</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="etiqueta___INDEX__" class="form-label">Etiqueta</label>
                                        <input type="text" class="form-control" id="etiqueta___INDEX__" name="etiqueta[]" required>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="mb-3">
                                        <label class="form-check-label d-block">&nbsp;</label>
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" id="requerido___INDEX__" name="requerido[]" checked>
                                            <label class="form-check-label" for="requerido___INDEX__">
                                                Campo requerido
                                            </label>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-1">
                                    <div class="mb-3">
                                        <label class="d-block">&nbsp;</label>
                                        <button type="button" class="btn btn-danger btn-sm btn-remove-field">
                                            <i class="material-icons">delete</i>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </template>
                
                <?php elseif ($action === 'edit' && $form): ?>
                <!-- Formulario de edición de formulario -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Editar formulario</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/forms.php" class="btn btn-sm btn-outline-secondary">
                            <i class="material-icons">arrow_back</i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-body">
                        <form method="POST" action="<?php echo APP_URL; ?>/admin/forms.php">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            <input type="hidden" name="form_action" value="update">
                            <input type="hidden" name="form_id" value="<?php echo $form['id']; ?>">
                            
                            <div class="mb-3">
                                <label for="titulo" class="form-label">Título del formulario</label>
                                <input type="text" class="form-control" id="titulo" name="titulo" value="<?php echo $form['titulo']; ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="descripcion" class="form-label">Descripción</label>
                                <textarea class="form-control" id="descripcion" name="descripcion" rows="3"><?php echo $form['descripcion']; ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="estado" class="form-label">Estado</label>
                                <select class="form-select" id="estado" name="estado">
                                    <option value="activo" <?php echo ($form['estado'] === 'activo') ? 'selected' : ''; ?>>Activo</option>
                                    <option value="inactivo" <?php echo ($form['estado'] === 'inactivo') ? 'selected' : ''; ?>>Inactivo</option>
                                </select>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="material-icons">save</i> Guardar cambios
                                </button>
                                <a href="<?php echo APP_URL; ?>/admin/form_fields.php?form_id=<?php echo $form['id']; ?>" class="btn btn-outline-primary">
                                    <i class="material-icons">list</i> Gestionar campos
                                </a>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Lista de formularios -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Formularios</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/forms.php?action=create" class="btn btn-sm btn-primary">
                            <i class="material-icons">add</i> Nuevo formulario
                        </a>
                    </div>
                </div>
                
                <!-- Buscador -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" action="<?php echo APP_URL; ?>/admin/forms.php" class="row g-3">
                            <div class="col-md-10">
                                <input type="text" class="form-control" id="search" name="search" placeholder="Buscar por título o descripción" value="<?php echo $_GET['search'] ?? ''; ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">
                                    <i class="material-icons">search</i> Buscar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <!-- Tabla de formularios -->
                <div class="card">
                    <div class="card-body">
                        <?php if (count($forms) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Título</th>
                                        <th>Descripción</th>
                                        <th>Estado</th>
                                        <th>Fecha de creación</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($forms as $form): ?>
                                    <tr>
                                        <td><?php echo $form['id']; ?></td>
                                        <td><?php echo $form['titulo']; ?></td>
                                        <td><?php echo truncateText($form['descripcion'], 50); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo ($form['estado'] === 'activo') ? 'success' : 'danger'; ?>">
                                                <?php echo $form['estado']; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($form['fecha_creacion']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo APP_URL; ?>/admin/forms.php?action=edit&id=<?php echo $form['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Editar">
                                                    <i class="material-icons">edit</i>
                                                </a>
                                                <a href="<?php echo APP_URL; ?>/admin/form_fields.php?form_id=<?php echo $form['id']; ?>" class="btn btn-sm btn-outline-info" data-bs-toggle="tooltip" title="Gestionar campos">
                                                    <i class="material-icons">list</i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $form['id']; ?>" title="Eliminar">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                                
                                                <!-- Cambiar estado -->
                                                <form method="POST" action="<?php echo APP_URL; ?>/admin/forms.php" class="d-inline">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <input type="hidden" name="form_action" value="change_status">
                                                    <input type="hidden" name="form_id" value="<?php echo $form['id']; ?>">
                                                    <input type="hidden" name="estado" value="<?php echo ($form['estado'] === 'activo') ? 'inactivo' : 'activo'; ?>">
                                                    <button type="submit" class="btn btn-sm btn-outline-<?php echo ($form['estado'] === 'activo') ? 'secondary' : 'success'; ?>" data-bs-toggle="tooltip" title="<?php echo ($form['estado'] === 'activo') ? 'Desactivar' : 'Activar'; ?>">
                                                        <i class="material-icons"><?php echo ($form['estado'] === 'activo') ? 'toggle_off' : 'toggle_on'; ?></i>
                                                    </button>
                                                </form>
                                            </div>
                                            
                                            <!-- Modal de confirmación de eliminación -->
                                            <div class="modal fade" id="deleteModal<?php echo $form['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $form['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $form['id']; ?>">Confirmar eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Estás seguro de que deseas eliminar el formulario <strong><?php echo $form['titulo']; ?></strong>? Esta acción no se puede deshacer y eliminará todos los campos y envíos asociados.
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="POST" action="<?php echo APP_URL; ?>/admin/forms.php">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="form_action" value="delete">
                                                                <input type="hidden" name="form_id" value="<?php echo $form['id']; ?>">
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
                            $baseUrl = APP_URL . '/admin/forms.php';
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
                        <p class="text-center text-muted">No se encontraron formularios</p>
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
