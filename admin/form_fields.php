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

// Verificar si el usuario es administrador
$userId = Auth::id();
$sqlAdmin = "SELECT username FROM usuarios WHERE id = ? LIMIT 1";
$userResult = fetchOne($sqlAdmin, [$userId]);
$isAdmin = ($userResult && $userResult['username'] === 'admin');

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
        case 'reorder_fields':
            // Reordenar campos
            $fieldOrder = isset($_POST['field_order']) ? json_decode($_POST['field_order'], true) : [];

            if (!empty($fieldOrder)) {
                $result = Form::reorderFields($formId, $fieldOrder);

                // Responder con JSON para peticiones AJAX
                if (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                    header('Content-Type: application/json');
                    echo json_encode(['success' => $result]);
                    exit;
                }

                if ($result) {
                    setAlert('success', 'Campos reordenados correctamente');
                } else {
                    setAlert('danger', 'Error al reordenar los campos');
                }

                redirect(APP_URL . '/admin/form_fields.php?form_id=' . $formId);
            }
            break;

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

    <style>
        /* Estilos para iconos pequeños en badges */
        .material-icons-small {
            font-size: 16px;
            vertical-align: text-bottom;
            margin-right: 2px;
        }

        /* Estilos para la tabla de campos */
        .table-campos th {
            background-color: #f8f9fa;
            position: sticky;
            top: 0;
            z-index: 1;
        }

        .table-campos tr:hover {
            background-color: rgba(0, 150, 136, 0.05);
        }

        /* Estilos para la previsualización */
        #previewContainer {
            border: 1px solid #ddd;
            border-radius: 8px;
            padding: 20px;
            background-color: #f9f9f9;
            margin-bottom: 20px;
        }

        #previewContainer .form-label {
            font-weight: 500;
        }

        #previewContainer .required-asterisk {
            color: red;
            margin-left: 3px;
        }

        /* Estilos para tooltips */
        .custom-tooltip {
            --bs-tooltip-bg: #009688;
            --bs-tooltip-color: white;
        }

        /* Estilos para drag and drop */
        .sortable-ghost {
            background-color: #e9ecef;
            opacity: 0.8;
        }

        .handle {
            cursor: move;
            color: #adb5bd;
        }

        .handle:hover {
            color: #6c757d;
        }
    </style>
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

                <!-- Breadcrumbs -->
                <nav aria-label="breadcrumb" class="mt-3">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/admin/index.php">Inicio</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/admin/forms.php">Formularios</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Campos de "<?php echo $form['titulo']; ?>"</li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-2 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Campos del formulario: <?php echo $form['titulo']; ?></h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <a href="<?php echo APP_URL; ?>/admin/forms.php" class="btn btn-sm btn-outline-secondary me-2">
                            <i class="material-icons">arrow_back</i> Volver a formularios
                        </a>
                        <a href="#" class="btn btn-sm btn-outline-primary" id="previewFormBtn">
                            <i class="material-icons">visibility</i> Previsualizar formulario
                        </a>
                    </div>
                </div>

                <?php if ($isAdmin): ?>
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
                                    <!-- Tipos esenciales -->
                                    <option value="fecha_hora">Fecha y hora</option>
                                    <option value="ubicacion_gps">Ubicación GPS</option>

                                    <!-- Tipos dinámicos -->
                                    <option value="texto">Texto</option>
                                    <option value="numero">Número</option>
                                    <option value="textarea">Texto largo</option>
                                    <option value="select">Lista desplegable</option>
                                    <option value="checkbox">Casillas de verificación</option>
                                    <option value="radio">Opciones únicas</option>
                                    <option value="email">Correo electrónico</option>
                                    <option value="telefono">Teléfono</option>

                                    <!-- Tipos heredados (mantener compatibilidad) -->
                                    <option value="lugar">Lugar</option>
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

                            <!-- Propiedades dinámicas según el tipo de campo -->
                            <div class="col-12 mt-3" id="propiedades-container">
                                <!-- Propiedades para texto, email, telefono -->
                                <div class="row g-3 propiedades-grupo" id="propiedades-texto" style="display: none;">
                                    <div class="col-md-6">
                                        <label for="placeholder" class="form-label">Placeholder</label>
                                        <input type="text" class="form-control" id="placeholder" name="propiedades[placeholder]" placeholder="Texto de ayuda">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="longitud_maxima" class="form-label">Longitud máxima</label>
                                        <input type="number" class="form-control" id="longitud_maxima" name="propiedades[longitud_maxima]" min="1">
                                    </div>
                                </div>

                                <!-- Propiedades para número -->
                                <div class="row g-3 propiedades-grupo" id="propiedades-numero" style="display: none;">
                                    <div class="col-md-6">
                                        <label for="min" class="form-label">Valor mínimo</label>
                                        <input type="number" class="form-control" id="min" name="propiedades[min]" step="any">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="max" class="form-label">Valor máximo</label>
                                        <input type="number" class="form-control" id="max" name="propiedades[max]" step="any">
                                    </div>
                                </div>

                                <!-- Propiedades para select, checkbox, radio -->
                                <div class="row g-3 propiedades-grupo" id="propiedades-opciones" style="display: none;">
                                    <div class="col-12">
                                        <label class="form-label">Opciones</label>
                                        <div id="opciones-container">
                                            <div class="input-group mb-2 opcion-row">
                                                <input type="text" class="form-control" name="propiedades[opciones][0][valor]" placeholder="Valor" aria-label="Valor">
                                                <input type="text" class="form-control" name="propiedades[opciones][0][texto]" placeholder="Texto a mostrar" aria-label="Texto">
                                                <button class="btn btn-outline-danger remove-opcion" type="button"><i class="material-icons">delete</i></button>
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="add-opcion">
                                            <i class="material-icons">add</i> Añadir opción
                                        </button>
                                    </div>
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
                <?php endif; ?>

                <!-- Lista de campos -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Campos existentes</h5>
                    </div>
                    <div class="card-body">
                        <?php if (count($fields) > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-campos">
                                <thead>
                                    <tr>
                                        <?php if ($isAdmin): ?>
                                        <th width="40"></th>
                                        <?php endif; ?>
                                        <th width="50">ID</th>
                                        <th width="180">Tipo</th>
                                        <th>Etiqueta</th>
                                        <th width="100">Requerido</th>
                                        <th width="80">Orden</th>
                                        <th width="120">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody id="sortable-fields">
                                    <?php foreach ($fields as $field): ?>
                                    <tr data-id="<?php echo $field['id']; ?>">
                                        <?php if ($isAdmin): ?>
                                        <td class="text-center">
                                            <i class="material-icons handle">drag_indicator</i>
                                        </td>
                                        <?php endif; ?>
                                        <td><?php echo $field['id']; ?></td>
                                        <td>
                                            <?php
                                            $tipoLabel = '';
                                            $tipoIcon = '';
                                            $tipoBadgeClass = 'bg-secondary';

                                            switch ($field['tipo_campo']) {
                                                case 'lugar':
                                                    $tipoLabel = 'Lugar';
                                                    $tipoIcon = 'place';
                                                    $tipoBadgeClass = 'bg-info';
                                                    break;
                                                case 'fecha_hora':
                                                    $tipoLabel = 'Fecha y hora';
                                                    $tipoIcon = 'event';
                                                    $tipoBadgeClass = 'bg-primary';
                                                    break;
                                                case 'ubicacion_gps':
                                                    $tipoLabel = 'Ubicación GPS';
                                                    $tipoIcon = 'location_on';
                                                    $tipoBadgeClass = 'bg-danger';
                                                    break;
                                                case 'comentario':
                                                    $tipoLabel = 'Comentario';
                                                    $tipoIcon = 'comment';
                                                    $tipoBadgeClass = 'bg-secondary';
                                                    break;
                                                case 'texto':
                                                    $tipoLabel = 'Texto';
                                                    $tipoIcon = 'text_fields';
                                                    $tipoBadgeClass = 'bg-success';
                                                    break;
                                                case 'numero':
                                                    $tipoLabel = 'Número';
                                                    $tipoIcon = 'pin';
                                                    $tipoBadgeClass = 'bg-warning text-dark';
                                                    break;
                                                case 'textarea':
                                                    $tipoLabel = 'Texto largo';
                                                    $tipoIcon = 'notes';
                                                    $tipoBadgeClass = 'bg-success';
                                                    break;
                                                case 'select':
                                                    $tipoLabel = 'Lista desplegable';
                                                    $tipoIcon = 'arrow_drop_down_circle';
                                                    $tipoBadgeClass = 'bg-info';
                                                    break;
                                                case 'checkbox':
                                                    $tipoLabel = 'Casillas de verificación';
                                                    $tipoIcon = 'check_box';
                                                    $tipoBadgeClass = 'bg-info';
                                                    break;
                                                case 'radio':
                                                    $tipoLabel = 'Opciones únicas';
                                                    $tipoIcon = 'radio_button_checked';
                                                    $tipoBadgeClass = 'bg-info';
                                                    break;
                                                case 'email':
                                                    $tipoLabel = 'Correo electrónico';
                                                    $tipoIcon = 'email';
                                                    $tipoBadgeClass = 'bg-success';
                                                    break;
                                                case 'telefono':
                                                    $tipoLabel = 'Teléfono';
                                                    $tipoIcon = 'phone';
                                                    $tipoBadgeClass = 'bg-success';
                                                    break;
                                            }
                                            ?>
                                            <span class="badge <?php echo $tipoBadgeClass; ?>" data-bs-toggle="tooltip" title="<?php echo $tipoLabel; ?>">
                                                <i class="material-icons material-icons-small"><?php echo $tipoIcon; ?></i>
                                                <?php echo $tipoLabel; ?>
                                            </span>
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
                                                <?php if ($isAdmin): ?>
                                                <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#editModal<?php echo $field['id']; ?>">
                                                    <i class="material-icons">edit</i>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $field['id']; ?>">
                                                    <i class="material-icons">delete</i>
                                                </button>
                                                <?php else: ?>
                                                <button type="button" class="btn btn-sm btn-outline-secondary" disabled>
                                                    <i class="material-icons">visibility</i>
                                                </button>
                                                <?php endif; ?>
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
                                                                        <!-- Tipos esenciales -->
                                                                        <option value="fecha_hora" <?php echo ($field['tipo_campo'] === 'fecha_hora') ? 'selected' : ''; ?>>Fecha y hora</option>
                                                                        <option value="ubicacion_gps" <?php echo ($field['tipo_campo'] === 'ubicacion_gps') ? 'selected' : ''; ?>>Ubicación GPS</option>

                                                                        <!-- Tipos dinámicos -->
                                                                        <option value="texto" <?php echo ($field['tipo_campo'] === 'texto') ? 'selected' : ''; ?>>Texto</option>
                                                                        <option value="numero" <?php echo ($field['tipo_campo'] === 'numero') ? 'selected' : ''; ?>>Número</option>
                                                                        <option value="textarea" <?php echo ($field['tipo_campo'] === 'textarea') ? 'selected' : ''; ?>>Texto largo</option>
                                                                        <option value="select" <?php echo ($field['tipo_campo'] === 'select') ? 'selected' : ''; ?>>Lista desplegable</option>
                                                                        <option value="checkbox" <?php echo ($field['tipo_campo'] === 'checkbox') ? 'selected' : ''; ?>>Casillas de verificación</option>
                                                                        <option value="radio" <?php echo ($field['tipo_campo'] === 'radio') ? 'selected' : ''; ?>>Opciones únicas</option>
                                                                        <option value="email" <?php echo ($field['tipo_campo'] === 'email') ? 'selected' : ''; ?>>Correo electrónico</option>
                                                                        <option value="telefono" <?php echo ($field['tipo_campo'] === 'telefono') ? 'selected' : ''; ?>>Teléfono</option>

                                                                        <!-- Tipos heredados (mantener compatibilidad) -->
                                                                        <option value="lugar" <?php echo ($field['tipo_campo'] === 'lugar') ? 'selected' : ''; ?>>Lugar</option>
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

                                                                <?php
                                                                // Obtener propiedades del campo
                                                                $propiedades = isset($field['propiedades']) ? json_decode($field['propiedades'], true) : [];
                                                                if (!is_array($propiedades)) $propiedades = [];

                                                                // Valores por defecto
                                                                $placeholder = $propiedades['placeholder'] ?? '';
                                                                $longitud_maxima = $propiedades['longitud_maxima'] ?? '';
                                                                $min = $propiedades['min'] ?? '';
                                                                $max = $propiedades['max'] ?? '';
                                                                $opciones = $propiedades['opciones'] ?? [];
                                                                ?>

                                                                <!-- Propiedades dinámicas según el tipo de campo -->
                                                                <div class="propiedades-container-<?php echo $field['id']; ?>">
                                                                    <!-- Propiedades para texto, email, telefono -->
                                                                    <div class="mb-3 propiedades-grupo-<?php echo $field['id']; ?>" id="propiedades-texto-<?php echo $field['id']; ?>" style="display: <?php echo in_array($field['tipo_campo'], ['texto', 'email', 'telefono', 'textarea']) ? 'block' : 'none'; ?>">
                                                                        <div class="row g-3">
                                                                            <div class="col-md-6">
                                                                                <label for="placeholder_<?php echo $field['id']; ?>" class="form-label">Placeholder</label>
                                                                                <input type="text" class="form-control" id="placeholder_<?php echo $field['id']; ?>" name="propiedades[placeholder]" placeholder="Texto de ayuda" value="<?php echo htmlspecialchars($placeholder); ?>">
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <label for="longitud_maxima_<?php echo $field['id']; ?>" class="form-label">Longitud máxima</label>
                                                                                <input type="number" class="form-control" id="longitud_maxima_<?php echo $field['id']; ?>" name="propiedades[longitud_maxima]" min="1" value="<?php echo htmlspecialchars($longitud_maxima); ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Propiedades para número -->
                                                                    <div class="mb-3 propiedades-grupo-<?php echo $field['id']; ?>" id="propiedades-numero-<?php echo $field['id']; ?>" style="display: <?php echo $field['tipo_campo'] === 'numero' ? 'block' : 'none'; ?>">
                                                                        <div class="row g-3">
                                                                            <div class="col-md-6">
                                                                                <label for="min_<?php echo $field['id']; ?>" class="form-label">Valor mínimo</label>
                                                                                <input type="number" class="form-control" id="min_<?php echo $field['id']; ?>" name="propiedades[min]" step="any" value="<?php echo htmlspecialchars($min); ?>">
                                                                            </div>
                                                                            <div class="col-md-6">
                                                                                <label for="max_<?php echo $field['id']; ?>" class="form-label">Valor máximo</label>
                                                                                <input type="number" class="form-control" id="max_<?php echo $field['id']; ?>" name="propiedades[max]" step="any" value="<?php echo htmlspecialchars($max); ?>">
                                                                            </div>
                                                                        </div>
                                                                    </div>

                                                                    <!-- Propiedades para select, checkbox, radio -->
                                                                    <div class="mb-3 propiedades-grupo-<?php echo $field['id']; ?>" id="propiedades-opciones-<?php echo $field['id']; ?>" style="display: <?php echo in_array($field['tipo_campo'], ['select', 'checkbox', 'radio']) ? 'block' : 'none'; ?>">
                                                                        <label class="form-label">Opciones</label>
                                                                        <div id="opciones-container-<?php echo $field['id']; ?>">
                                                                            <?php if (empty($opciones)): ?>
                                                                            <div class="input-group mb-2 opcion-row">
                                                                                <input type="text" class="form-control" name="propiedades[opciones][0][valor]" placeholder="Valor" aria-label="Valor">
                                                                                <input type="text" class="form-control" name="propiedades[opciones][0][texto]" placeholder="Texto a mostrar" aria-label="Texto">
                                                                                <button class="btn btn-outline-danger remove-opcion" type="button"><i class="material-icons">delete</i></button>
                                                                            </div>
                                                                            <?php else: ?>
                                                                                <?php foreach ($opciones as $index => $opcion): ?>
                                                                                <div class="input-group mb-2 opcion-row">
                                                                                    <input type="text" class="form-control" name="propiedades[opciones][<?php echo $index; ?>][valor]" placeholder="Valor" aria-label="Valor" value="<?php echo htmlspecialchars($opcion['valor'] ?? ''); ?>">
                                                                                    <input type="text" class="form-control" name="propiedades[opciones][<?php echo $index; ?>][texto]" placeholder="Texto a mostrar" aria-label="Texto" value="<?php echo htmlspecialchars($opcion['texto'] ?? ''); ?>">
                                                                                    <button class="btn btn-outline-danger remove-opcion" type="button"><i class="material-icons">delete</i></button>
                                                                                </div>
                                                                                <?php endforeach; ?>
                                                                            <?php endif; ?>
                                                                        </div>
                                                                        <button type="button" class="btn btn-sm btn-outline-primary mt-2 add-opcion" data-field-id="<?php echo $field['id']; ?>">
                                                                            <i class="material-icons">add</i> Añadir opción
                                                                        </button>
                                                                    </div>
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

    <!-- Modal de previsualización -->
    <div class="modal fade" id="previewModal" tabindex="-1" aria-labelledby="previewModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="previewModalLabel">Previsualización del formulario: <?php echo $form['titulo']; ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div id="previewContainer">
                        <h4 class="mb-4"><?php echo $form['titulo']; ?></h4>
                        <p class="text-muted mb-4"><?php echo $form['descripcion']; ?></p>
                        <form id="previewForm">
                            <!-- Los campos se generarán dinámicamente aquí -->
                        </form>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Incluir Sortable.js para drag and drop -->
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

    <!-- JavaScript para campos dinámicos -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Inicializar tooltips
            const tooltipTriggerList = document.querySelectorAll('[data-bs-toggle="tooltip"]');
            [...tooltipTriggerList].map(tooltipTriggerEl => new bootstrap.Tooltip(tooltipTriggerEl, {
                customClass: 'custom-tooltip'
            }));

            // Inicializar Sortable para reordenamiento de campos
            const sortableFields = document.getElementById('sortable-fields');
            if (sortableFields) {
                new Sortable(sortableFields, {
                    handle: '.handle',
                    animation: 150,
                    ghostClass: 'sortable-ghost',
                    onEnd: function(evt) {
                        // Actualizar órdenes después de reordenar
                        actualizarOrdenes();
                    }
                });
            }

            // Manejar cambio de tipo de campo para mostrar propiedades específicas
            const tipoCampoSelect = document.getElementById('tipo_campo');
            if (tipoCampoSelect) {
                tipoCampoSelect.addEventListener('change', function() {
                    mostrarPropiedadesSegunTipo(this.value);
                });

                // Mostrar propiedades iniciales según el tipo seleccionado
                mostrarPropiedadesSegunTipo(tipoCampoSelect.value);
            }

            // Manejar cambio de tipo de campo en modales de edición
            document.querySelectorAll('select[id^="tipo_campo_"]').forEach(function(select) {
                select.addEventListener('change', function() {
                    const fieldId = this.id.replace('tipo_campo_', '');
                    mostrarPropiedadesSegunTipoEdicion(this.value, fieldId);
                });
            });

            // Añadir opción en formulario de creación
            const addOpcionBtn = document.getElementById('add-opcion');
            if (addOpcionBtn) {
                addOpcionBtn.addEventListener('click', function() {
                    addOpcion('opciones-container');
                });
            }

            // Añadir opción en formularios de edición
            document.querySelectorAll('.add-opcion').forEach(function(btn) {
                btn.addEventListener('click', function() {
                    const fieldId = this.getAttribute('data-field-id');
                    addOpcion('opciones-container-' + fieldId);
                });
            });

            // Eliminar opción (delegación de eventos)
            document.addEventListener('click', function(e) {
                if (e.target.classList.contains('remove-opcion') || e.target.closest('.remove-opcion')) {
                    const btn = e.target.classList.contains('remove-opcion') ? e.target : e.target.closest('.remove-opcion');
                    const row = btn.closest('.opcion-row');
                    if (row && row.parentNode.querySelectorAll('.opcion-row').length > 1) {
                        row.remove();
                    }
                }
            });

            // Botón de previsualización
            const previewBtn = document.getElementById('previewFormBtn');
            if (previewBtn) {
                previewBtn.addEventListener('click', function(e) {
                    e.preventDefault();
                    generarPrevisualizacion();
                    const previewModal = new bootstrap.Modal(document.getElementById('previewModal'));
                    previewModal.show();
                });
            }
        });

        // Función para actualizar órdenes después de reordenar
        function actualizarOrdenes() {
            const rows = document.querySelectorAll('#sortable-fields tr');
            const fieldIds = [];

            rows.forEach(function(row, index) {
                const fieldId = row.getAttribute('data-id');
                if (fieldId) {
                    fieldIds.push(fieldId);
                    // Actualizar el número de orden visible en la tabla
                    const ordenCell = row.querySelector('td:nth-last-child(2)');
                    if (ordenCell) {
                        ordenCell.textContent = index + 1;
                    }
                }
            });

            // Enviar la nueva ordenación al servidor mediante AJAX
            if (fieldIds.length > 0) {
                const formData = new FormData();
                formData.append('form_action', 'reorder_fields');
                formData.append('field_order', JSON.stringify(fieldIds));
                formData.append('csrf_token', document.querySelector('input[name="csrf_token"]').value);

                fetch(window.location.href, {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Mostrar mensaje de éxito
                        const alertContainer = document.createElement('div');
                        alertContainer.className = 'alert alert-success alert-dismissible fade show';
                        alertContainer.innerHTML = `
                            Orden actualizado correctamente
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        `;
                        document.querySelector('.main-content').prepend(alertContainer);

                        // Eliminar alerta después de 3 segundos
                        setTimeout(() => {
                            alertContainer.remove();
                        }, 3000);
                    }
                })
                .catch(error => {
                    console.error('Error al actualizar el orden:', error);
                });
            }
        }

        // Función para generar la previsualización del formulario
        function generarPrevisualizacion() {
            const previewForm = document.getElementById('previewForm');
            if (!previewForm) return;

            // Limpiar el formulario de previsualización
            previewForm.innerHTML = '';

            // Obtener todos los campos de la tabla
            const rows = document.querySelectorAll('#sortable-fields tr');

            rows.forEach(function(row) {
                const fieldId = row.getAttribute('data-id');
                if (!fieldId) return;

                const tipo = row.querySelector('td:nth-child(' + (row.querySelector('.handle') ? '3' : '2') + ') .badge').textContent.trim();
                const etiqueta = row.querySelector('td:nth-child(' + (row.querySelector('.handle') ? '4' : '3') + ')').textContent.trim();
                const requerido = row.querySelector('td:nth-child(' + (row.querySelector('.handle') ? '5' : '4') + ') .badge').textContent.trim() === 'Sí';

                // Crear grupo de formulario
                const formGroup = document.createElement('div');
                formGroup.className = 'mb-3';

                // Crear etiqueta
                const label = document.createElement('label');
                label.className = 'form-label';
                label.setAttribute('for', 'preview_' + fieldId);
                label.textContent = etiqueta;

                // Añadir asterisco si es requerido
                if (requerido) {
                    const asterisk = document.createElement('span');
                    asterisk.className = 'required-asterisk';
                    asterisk.textContent = '*';
                    label.appendChild(asterisk);
                }

                formGroup.appendChild(label);

                // Crear campo según el tipo
                let campo;

                switch (tipo.toLowerCase()) {
                    case 'texto':
                    case 'lugar':
                    case 'teléfono':
                    case 'correo electrónico':
                        campo = document.createElement('input');
                        campo.type = tipo.toLowerCase() === 'correo electrónico' ? 'email' :
                                    tipo.toLowerCase() === 'teléfono' ? 'tel' : 'text';
                        campo.className = 'form-control';
                        campo.id = 'preview_' + fieldId;
                        campo.placeholder = tipo.toLowerCase() === 'correo electrónico' ? 'ejemplo@dominio.com' :
                                           tipo.toLowerCase() === 'teléfono' ? '(123) 456-7890' : 'Ingrese ' + etiqueta.toLowerCase();
                        break;

                    case 'número':
                        campo = document.createElement('input');
                        campo.type = 'number';
                        campo.className = 'form-control';
                        campo.id = 'preview_' + fieldId;
                        campo.placeholder = 'Ingrese un valor numérico';
                        break;

                    case 'fecha y hora':
                        campo = document.createElement('input');
                        campo.type = 'datetime-local';
                        campo.className = 'form-control';
                        campo.id = 'preview_' + fieldId;
                        break;

                    case 'ubicación gps':
                        campo = document.createElement('div');
                        campo.className = 'input-group';
                        campo.innerHTML = `
                            <input type="text" class="form-control" id="preview_${fieldId}" placeholder="Latitud, Longitud" readonly>
                            <button class="btn btn-outline-secondary" type="button"><i class="material-icons">my_location</i></button>
                        `;
                        break;

                    case 'texto largo':
                    case 'comentario':
                        campo = document.createElement('textarea');
                        campo.className = 'form-control';
                        campo.id = 'preview_' + fieldId;
                        campo.rows = 3;
                        campo.placeholder = 'Ingrese ' + etiqueta.toLowerCase();
                        break;

                    case 'lista desplegable':
                        campo = document.createElement('select');
                        campo.className = 'form-select';
                        campo.id = 'preview_' + fieldId;

                        // Opción por defecto
                        const defaultOption = document.createElement('option');
                        defaultOption.value = '';
                        defaultOption.textContent = 'Seleccione una opción';
                        defaultOption.selected = true;
                        campo.appendChild(defaultOption);

                        // Opciones de ejemplo
                        ['Opción 1', 'Opción 2', 'Opción 3'].forEach(function(optionText, index) {
                            const option = document.createElement('option');
                            option.value = 'opcion' + (index + 1);
                            option.textContent = optionText;
                            campo.appendChild(option);
                        });
                        break;

                    case 'casillas de verificación':
                        campo = document.createElement('div');

                        // Opciones de ejemplo
                        ['Opción 1', 'Opción 2', 'Opción 3'].forEach(function(optionText, index) {
                            const checkDiv = document.createElement('div');
                            checkDiv.className = 'form-check';

                            const check = document.createElement('input');
                            check.type = 'checkbox';
                            check.className = 'form-check-input';
                            check.id = 'preview_' + fieldId + '_' + index;
                            check.name = 'preview_' + fieldId + '[]';
                            check.value = 'opcion' + (index + 1);

                            const checkLabel = document.createElement('label');
                            checkLabel.className = 'form-check-label';
                            checkLabel.setAttribute('for', 'preview_' + fieldId + '_' + index);
                            checkLabel.textContent = optionText;

                            checkDiv.appendChild(check);
                            checkDiv.appendChild(checkLabel);
                            campo.appendChild(checkDiv);
                        });
                        break;

                    case 'opciones únicas':
                        campo = document.createElement('div');

                        // Opciones de ejemplo
                        ['Opción 1', 'Opción 2', 'Opción 3'].forEach(function(optionText, index) {
                            const radioDiv = document.createElement('div');
                            radioDiv.className = 'form-check';

                            const radio = document.createElement('input');
                            radio.type = 'radio';
                            radio.className = 'form-check-input';
                            radio.id = 'preview_' + fieldId + '_' + index;
                            radio.name = 'preview_' + fieldId;
                            radio.value = 'opcion' + (index + 1);

                            const radioLabel = document.createElement('label');
                            radioLabel.className = 'form-check-label';
                            radioLabel.setAttribute('for', 'preview_' + fieldId + '_' + index);
                            radioLabel.textContent = optionText;

                            radioDiv.appendChild(radio);
                            radioDiv.appendChild(radioLabel);
                            campo.appendChild(radioDiv);
                        });
                        break;

                    default:
                        campo = document.createElement('input');
                        campo.type = 'text';
                        campo.className = 'form-control';
                        campo.id = 'preview_' + fieldId;
                        campo.placeholder = 'Campo de tipo desconocido';
                }

                formGroup.appendChild(campo);
                previewForm.appendChild(formGroup);
            });

            // Añadir botón de envío
            const submitBtn = document.createElement('button');
            submitBtn.type = 'button';
            submitBtn.className = 'btn btn-primary';
            submitBtn.textContent = 'Enviar formulario';
            previewForm.appendChild(submitBtn);
        }

        // Función para mostrar propiedades según el tipo de campo (creación)
        function mostrarPropiedadesSegunTipo(tipo) {
            // Ocultar todos los grupos de propiedades
            document.querySelectorAll('.propiedades-grupo').forEach(function(grupo) {
                grupo.style.display = 'none';
            });

            // Mostrar grupo correspondiente según el tipo
            if (['texto', 'email', 'telefono', 'textarea'].includes(tipo)) {
                document.getElementById('propiedades-texto').style.display = 'flex';
            } else if (tipo === 'numero') {
                document.getElementById('propiedades-numero').style.display = 'flex';
            } else if (['select', 'checkbox', 'radio'].includes(tipo)) {
                document.getElementById('propiedades-opciones').style.display = 'flex';
            }
        }

        // Función para mostrar propiedades según el tipo de campo (edición)
        function mostrarPropiedadesSegunTipoEdicion(tipo, fieldId) {
            // Ocultar todos los grupos de propiedades
            document.querySelectorAll('.propiedades-grupo-' + fieldId).forEach(function(grupo) {
                grupo.style.display = 'none';
            });

            // Mostrar grupo correspondiente según el tipo
            if (['texto', 'email', 'telefono', 'textarea'].includes(tipo)) {
                document.getElementById('propiedades-texto-' + fieldId).style.display = 'block';
            } else if (tipo === 'numero') {
                document.getElementById('propiedades-numero-' + fieldId).style.display = 'block';
            } else if (['select', 'checkbox', 'radio'].includes(tipo)) {
                document.getElementById('propiedades-opciones-' + fieldId).style.display = 'block';
            }
        }

        // Función para añadir una nueva opción
        function addOpcion(containerId) {
            const container = document.getElementById(containerId);
            if (!container) return;

            const opcionesCount = container.querySelectorAll('.opcion-row').length;
            const newRow = document.createElement('div');
            newRow.className = 'input-group mb-2 opcion-row';

            // Extraer el ID del campo si está presente en el containerId
            let fieldIdPart = '';
            if (containerId.includes('-')) {
                fieldIdPart = containerId.split('-').pop();
            }

            newRow.innerHTML = `
                <input type="text" class="form-control" name="propiedades[opciones][${opcionesCount}][valor]" placeholder="Valor" aria-label="Valor">
                <input type="text" class="form-control" name="propiedades[opciones][${opcionesCount}][texto]" placeholder="Texto a mostrar" aria-label="Texto">
                <button class="btn btn-outline-danger remove-opcion" type="button"><i class="material-icons">delete</i></button>
            `;

            container.appendChild(newRow);
        }
    </script>
</body>
</html>
