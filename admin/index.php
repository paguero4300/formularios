<?php
/**
 * Dashboard principal
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

// Obtener estadísticas
$conn = getDBConnection();

// Total de usuarios
$sqlUsers = "SELECT COUNT(*) as total FROM usuarios";
$resultUsers = $conn->query($sqlUsers);
$totalUsers = $resultUsers->fetch_assoc()['total'];

// Total de formularios
$sqlForms = "SELECT COUNT(*) as total FROM formularios";
$resultForms = $conn->query($sqlForms);
$totalForms = $resultForms->fetch_assoc()['total'];

// Total de envíos
$sqlSubmissions = "SELECT COUNT(*) as total FROM envios_formulario";
$resultSubmissions = $conn->query($sqlSubmissions);
$totalSubmissions = $resultSubmissions->fetch_assoc()['total'];

// Envíos recientes
$sqlRecentSubmissions = "SELECT ef.id, ef.fecha_envio, u.username, f.titulo as formulario_titulo
                        FROM envios_formulario ef
                        JOIN usuarios u ON ef.id_usuario = u.id
                        JOIN formularios f ON ef.id_formulario = f.id
                        ORDER BY ef.fecha_envio DESC
                        LIMIT 5";
$resultRecentSubmissions = $conn->query($sqlRecentSubmissions);
$recentSubmissions = $resultRecentSubmissions->fetch_all(MYSQLI_ASSOC);

// Usuarios recientes
$sqlRecentUsers = "SELECT id, username, nombre_completo, fecha_creacion
                  FROM usuarios
                  ORDER BY fecha_creacion DESC
                  LIMIT 5";
$resultRecentUsers = $conn->query($sqlRecentUsers);
$recentUsers = $resultRecentUsers->fetch_all(MYSQLI_ASSOC);

// Cerrar conexión
$conn->close();

// Obtener mensaje de alerta
$alert = getAlert();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - <?php echo APP_NAME; ?></title>
    
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
                <?php echo generateMenu('dashboard'); ?>
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
                    <h1 class="h2">Dashboard</h1>
                    <div class="btn-toolbar mb-2 mb-md-0">
                        <div class="btn-group me-2">
                            <a href="<?php echo APP_URL; ?>/admin/forms.php" class="btn btn-sm btn-outline-primary">
                                <i class="material-icons">add</i> Nuevo formulario
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Tarjetas de estadísticas -->
                <div class="row">
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-body text-center">
                                <i class="material-icons dashboard-icon">people</i>
                                <h5 class="card-title">Usuarios</h5>
                                <h2 class="card-text"><?php echo $totalUsers; ?></h2>
                                <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-primary">Ver usuarios</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-body text-center">
                                <i class="material-icons dashboard-icon">description</i>
                                <h5 class="card-title">Formularios</h5>
                                <h2 class="card-text"><?php echo $totalForms; ?></h2>
                                <a href="<?php echo APP_URL; ?>/admin/forms.php" class="btn btn-sm btn-outline-primary">Ver formularios</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card dashboard-card">
                            <div class="card-body text-center">
                                <i class="material-icons dashboard-icon">send</i>
                                <h5 class="card-title">Envíos</h5>
                                <h2 class="card-text"><?php echo $totalSubmissions; ?></h2>
                                <a href="<?php echo APP_URL; ?>/admin/submissions.php" class="btn btn-sm btn-outline-primary">Ver envíos</a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Envíos recientes -->
                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Envíos recientes</h5>
                                <a href="<?php echo APP_URL; ?>/admin/submissions.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                            </div>
                            <div class="card-body">
                                <?php if (count($recentSubmissions) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Formulario</th>
                                                <th>Usuario</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentSubmissions as $submission): ?>
                                            <tr>
                                                <td><?php echo $submission['id']; ?></td>
                                                <td><?php echo $submission['formulario_titulo']; ?></td>
                                                <td><?php echo $submission['username']; ?></td>
                                                <td><?php echo formatDate($submission['fecha_envio']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-center text-muted">No hay envíos recientes</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Usuarios recientes -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header d-flex justify-content-between align-items-center">
                                <h5 class="mb-0">Usuarios recientes</h5>
                                <a href="<?php echo APP_URL; ?>/admin/users.php" class="btn btn-sm btn-outline-primary">Ver todos</a>
                            </div>
                            <div class="card-body">
                                <?php if (count($recentUsers) > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>ID</th>
                                                <th>Usuario</th>
                                                <th>Nombre</th>
                                                <th>Fecha</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentUsers as $user): ?>
                                            <tr>
                                                <td><?php echo $user['id']; ?></td>
                                                <td><?php echo $user['username']; ?></td>
                                                <td><?php echo $user['nombre_completo']; ?></td>
                                                <td><?php echo formatDate($user['fecha_creacion']); ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                                <?php else: ?>
                                <p class="text-center text-muted">No hay usuarios recientes</p>
                                <?php endif; ?>
                            </div>
                        </div>
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
