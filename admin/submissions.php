<?php
/**
 * Gestión de envíos de formularios
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
$submissionId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$formId = isset($_GET['form_id']) ? (int)$_GET['form_id'] : 0;

// Procesar formularios
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verificar token CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        setAlert('danger', 'Error de validación del formulario');
        redirect(APP_URL . '/admin/submissions.php');
    }
    
    // Determinar la acción a realizar
    $formAction = $_POST['form_action'] ?? '';
    
    switch ($formAction) {
        case 'delete':
            // Eliminar envío
            $submissionId = (int)($_POST['submission_id'] ?? 0);
            
            // Eliminar envío
            $result = Form::deleteSubmission($submissionId);
            
            if ($result) {
                setAlert('success', 'Envío eliminado correctamente');
            } else {
                setAlert('danger', 'Error al eliminar el envío');
            }
            
            redirect(APP_URL . '/admin/submissions.php');
            break;
    }
}

// Obtener datos según la acción
$submission = null;
$submissions = [];
$pagination = null;
$form = null;
$fields = [];

switch ($action) {
    case 'view':
        // Obtener datos del envío
        $submission = Form::getSubmissionById($submissionId);
        
        if (!$submission) {
            setAlert('danger', 'Envío no encontrado');
            redirect(APP_URL . '/admin/submissions.php');
        }
        
        // Obtener campos del formulario
        $fields = Form::getFields($submission['id_formulario']);
        break;
        
    default:
        // Listar envíos
        $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
        
        // Si se especificó un formulario, mostrar solo los envíos de ese formulario
        if ($formId > 0) {
            $form = Form::getById($formId);
            
            if (!$form) {
                setAlert('danger', 'Formulario no encontrado');
                redirect(APP_URL . '/admin/submissions.php');
            }
            
            $result = Form::getSubmissions($formId, $page);
            $submissions = $result['submissions'];
            $pagination = $result['pagination'];
        } else {
            // Mostrar todos los envíos
            $conn = getDBConnection();
            
            // Obtener total de registros
            $sqlCount = "SELECT COUNT(*) as total FROM envios_formulario";
            $resultCount = $conn->query($sqlCount);
            $total = $resultCount->fetch_assoc()['total'];
            
            // Calcular paginación
            $perPage = 10;
            $totalPages = ceil($total / $perPage);
            $offset = ($page - 1) * $perPage;
            
            // Obtener envíos
            $sql = "SELECT ef.*, u.username, u.nombre_completo, f.titulo as formulario_titulo
                    FROM envios_formulario ef
                    JOIN usuarios u ON ef.id_usuario = u.id
                    JOIN formularios f ON ef.id_formulario = f.id
                    ORDER BY ef.fecha_envio DESC
                    LIMIT $perPage OFFSET $offset";
            
            $result = $conn->query($sql);
            $submissions = $result->fetch_all(MYSQLI_ASSOC);
            
            $pagination = [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'total_pages' => $totalPages
            ];
            
            $conn->close();
        }
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
    <title>Envíos - <?php echo APP_NAME; ?></title>
    
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
                <?php echo generateMenu('submissions'); ?>
            </div>
            
            <!-- Contenido -->
            <div class="col-md-9 col-lg-10 ms-sm-auto main-content">
                <?php if ($alert): ?>
                <div class="alert alert-<?php echo $alert['type']; ?> alert-dismissible fade show" role="alert">
                    <?php echo $alert['message']; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>
                
                <?php if ($action === 'view' && $submission): ?>
                <!-- Vista detallada de un envío -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Detalle del envío #<?php echo $submission['id']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/submissions.php" class="btn btn-sm btn-outline-secondary">
                            <i class="material-icons">arrow_back</i> Volver
                        </a>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Información general</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Formulario:</strong> <?php echo $submission['formulario_titulo']; ?></p>
                                <p><strong>Usuario:</strong> <?php echo $submission['nombre_completo']; ?> (<?php echo $submission['username']; ?>)</p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Fecha de envío:</strong> <?php echo formatDate($submission['fecha_envio']); ?></p>
                                <p><strong>ID del envío:</strong> <?php echo $submission['id']; ?></p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Datos del formulario</h5>
                    </div>
                    <div class="card-body">
                        <?php
                        // Decodificar datos JSON
                        $datos = json_decode($submission['datos'], true);
                        
                        if ($datos && count($fields) > 0):
                        ?>
                        <div class="table-responsive">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Campo</th>
                                        <th>Valor</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($fields as $field): ?>
                                    <tr>
                                        <td><strong><?php echo $field['etiqueta']; ?></strong></td>
                                        <td>
                                            <?php
                                            $fieldValue = $datos[$field['id']] ?? '';
                                            
                                            switch ($field['tipo_campo']) {
                                                case 'fecha_hora':
                                                    echo formatDate($fieldValue);
                                                    break;
                                                    
                                                case 'ubicacion_gps':
                                                    if (!empty($fieldValue)) {
                                                        $coords = explode(',', $fieldValue);
                                                        if (count($coords) === 2) {
                                                            echo "Latitud: {$coords[0]}, Longitud: {$coords[1]}";
                                                            echo '<br><a href="https://maps.google.com/?q=' . $fieldValue . '" target="_blank" class="btn btn-sm btn-outline-primary mt-1">Ver en mapa</a>';
                                                        } else {
                                                            echo $fieldValue;
                                                        }
                                                    }
                                                    break;
                                                    
                                                default:
                                                    echo nl2br(htmlspecialchars($fieldValue));
                                                    break;
                                            }
                                            ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <p class="text-center text-muted">No hay datos disponibles para este envío</p>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer">
                        <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteModal">
                            <i class="material-icons">delete</i> Eliminar envío
                        </button>
                    </div>
                </div>
                
                <!-- Modal de confirmación de eliminación -->
                <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
                    <div class="modal-dialog">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="deleteModalLabel">Confirmar eliminación</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                ¿Estás seguro de que deseas eliminar este envío? Esta acción no se puede deshacer.
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                <form method="POST" action="<?php echo APP_URL; ?>/admin/submissions.php">
                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                    <input type="hidden" name="form_action" value="delete">
                                    <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
                                    <button type="submit" class="btn btn-danger">Eliminar</button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
                
                <?php else: ?>
                <!-- Lista de envíos -->
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">
                        <?php if ($form): ?>
                        Envíos del formulario: <?php echo $form['titulo']; ?>
                        <?php else: ?>
                        Todos los envíos
                        <?php endif; ?>
                    </h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <?php if ($form): ?>
                        <a href="<?php echo APP_URL; ?>/admin/submissions.php" class="btn btn-sm btn-outline-secondary">
                            <i class="material-icons">list</i> Ver todos los envíos
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                
                <!-- Tabla de envíos -->
                <div class="card">
                    <div class="card-body">
                        <?php if (count($submissions) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>ID</th>
                                        <th>Formulario</th>
                                        <th>Usuario</th>
                                        <th>Fecha de envío</th>
                                        <th>Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($submissions as $submission): ?>
                                    <tr>
                                        <td><?php echo $submission['id']; ?></td>
                                        <td><?php echo $submission['formulario_titulo']; ?></td>
                                        <td><?php echo $submission['nombre_completo']; ?> (<?php echo $submission['username']; ?>)</td>
                                        <td><?php echo formatDate($submission['fecha_envio']); ?></td>
                                        <td>
                                            <div class="btn-group" role="group">
                                                <a href="<?php echo APP_URL; ?>/admin/submissions.php?action=view&id=<?php echo $submission['id']; ?>" class="btn btn-sm btn-outline-primary" data-bs-toggle="tooltip" title="Ver detalle">
                                                    <i class="material-icons">visibility</i>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $submission['id']; ?>" title="Eliminar">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                            </div>
                                            
                                            <!-- Modal de confirmación de eliminación -->
                                            <div class="modal fade" id="deleteModal<?php echo $submission['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $submission['id']; ?>" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title" id="deleteModalLabel<?php echo $submission['id']; ?>">Confirmar eliminación</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            ¿Estás seguro de que deseas eliminar este envío? Esta acción no se puede deshacer.
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                                                            <form method="POST" action="<?php echo APP_URL; ?>/admin/submissions.php">
                                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                                <input type="hidden" name="form_action" value="delete">
                                                                <input type="hidden" name="submission_id" value="<?php echo $submission['id']; ?>">
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
                            $baseUrl = APP_URL . '/admin/submissions.php';
                            if ($formId > 0) {
                                $baseUrl .= '?form_id=' . $formId . '&';
                            } else {
                                $baseUrl .= '?';
                            }
                            echo generatePagination($pagination['current_page'], $pagination['total_pages'], $baseUrl);
                            ?>
                        </div>
                        <?php endif; ?>
                        
                        <?php else: ?>
                        <p class="text-center text-muted">No se encontraron envíos</p>
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
