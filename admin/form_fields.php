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

    <!-- JavaScript para campos dinámicos -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
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
        });

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
