<?php
/**
 * Funciones generales
 * Panel administrativo Dr Security
 */

// Incluir archivos de configuración
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../config/database.php';

// Función para generar un slug a partir de un texto
function generateSlug($text) {
    // Convertir a minúsculas
    $text = strtolower($text);

    // Reemplazar espacios con guiones
    $text = str_replace(' ', '-', $text);

    // Eliminar caracteres especiales
    $text = preg_replace('/[^a-z0-9\-]/', '', $text);

    // Eliminar guiones duplicados
    $text = preg_replace('/-+/', '-', $text);

    // Eliminar guiones al inicio y al final
    $text = trim($text, '-');

    return $text;
}

// Función para formatear fechas
function formatDate($date, $format = 'd/m/Y H:i') {
    $datetime = new DateTime($date);
    return $datetime->format($format);
}

// Función para truncar texto
function truncateText($text, $length = 100, $append = '...') {
    if (strlen($text) <= $length) {
        return $text;
    }

    $text = substr($text, 0, $length);
    $text = substr($text, 0, strrpos($text, ' '));

    return $text . $append;
}

// Función para generar un token aleatorio
function generateToken($length = 32) {
    return bin2hex(random_bytes($length / 2));
}

// Función para validar una dirección de correo electrónico
function isValidEmail($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL);
}

// Función para obtener la IP del cliente
function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'];
    }
    return $ip;
}

// Función para registrar actividad
function logActivity($userId, $action, $details = '') {
    // Esta función podría implementarse para registrar actividad en una tabla de logs
    // Por ahora, solo registramos en el log del sistema
    $ip = getClientIP();
    $date = date('Y-m-d H:i:s');
    $logMessage = "[$date] Usuario ID: $userId | IP: $ip | Acción: $action | Detalles: $details" . PHP_EOL;

    // Registrar en un archivo de log (opcional)
    // file_put_contents(__DIR__ . '/../logs/activity.log', $logMessage, FILE_APPEND);
}

// Función para validar una fecha
function isValidDate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) === $date;
}

// Función para validar coordenadas GPS
function isValidGPSCoordinates($lat, $lng) {
    return is_numeric($lat) && is_numeric($lng) &&
           $lat >= -90 && $lat <= 90 &&
           $lng >= -180 && $lng <= 180;
}

// Función para generar un menú de navegación
function generateMenu($activeItem = '') {
    // Verificar si el usuario es administrador
    $userId = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;
    $isAdmin = false;

    if ($userId > 0) {
        $conn = getDBConnection();
        $sql = "SELECT username FROM usuarios WHERE id = ? LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("i", $userId);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            $isAdmin = ($user['username'] === 'admin');
        }

        $stmt->close();
        $conn->close();
    }

    // Menú para administradores (completo)
    $adminMenu = [
        'dashboard' => [
            'title' => 'Dashboard',
            'icon' => 'dashboard',
            'url' => APP_URL . '/admin/index.php'
        ],
        'users' => [
            'title' => 'Usuarios',
            'icon' => 'people',
            'url' => APP_URL . '/admin/users.php'
        ],
        'forms' => [
            'title' => 'Formularios',
            'icon' => 'description',
            'url' => APP_URL . '/admin/forms.php'
        ],
        'submissions' => [
            'title' => 'Envíos',
            'icon' => 'send',
            'url' => APP_URL . '/admin/submissions.php'
        ]
    ];

    // Menú para usuarios normales (limitado)
    $userMenu = [
        'forms' => [
            'title' => 'Formularios',
            'icon' => 'description',
            'url' => APP_URL . '/admin/forms.php'
        ],
        'submissions' => [
            'title' => 'Envíos',
            'icon' => 'send',
            'url' => APP_URL . '/admin/submissions.php'
        ]
    ];

    // Seleccionar el menú según el rol del usuario
    $menu = $isAdmin ? $adminMenu : $userMenu;

    $html = '<ul class="nav flex-column">';

    foreach ($menu as $key => $item) {
        $activeClass = ($activeItem === $key) ? 'active' : '';
        $html .= '<li class="nav-item">';
        $html .= '<a class="nav-link ' . $activeClass . '" href="' . $item['url'] . '">';
        $html .= '<i class="material-icons">' . $item['icon'] . '</i> ';
        $html .= $item['title'];
        $html .= '</a>';
        $html .= '</li>';
    }

    $html .= '</ul>';

    return $html;
}

// Función para generar paginación
function generatePagination($currentPage, $totalPages, $baseUrl) {
    if ($totalPages <= 1) {
        return '';
    }

    $html = '<nav aria-label="Paginación">';
    $html .= '<ul class="pagination justify-content-center">';

    // Botón anterior
    $prevDisabled = ($currentPage <= 1) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $prevDisabled . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage - 1) . '" tabindex="-1">Anterior</a>';
    $html .= '</li>';

    // Páginas
    $startPage = max(1, $currentPage - 2);
    $endPage = min($totalPages, $currentPage + 2);

    if ($startPage > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=1">1</a></li>';
        if ($startPage > 2) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }

    for ($i = $startPage; $i <= $endPage; $i++) {
        $activeClass = ($i === $currentPage) ? 'active' : '';
        $html .= '<li class="page-item ' . $activeClass . '">';
        $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . $i . '">' . $i . '</a>';
        $html .= '</li>';
    }

    if ($endPage < $totalPages) {
        if ($endPage < $totalPages - 1) {
            $html .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
        $html .= '<li class="page-item"><a class="page-link" href="' . $baseUrl . '?page=' . $totalPages . '">' . $totalPages . '</a></li>';
    }

    // Botón siguiente
    $nextDisabled = ($currentPage >= $totalPages) ? 'disabled' : '';
    $html .= '<li class="page-item ' . $nextDisabled . '">';
    $html .= '<a class="page-link" href="' . $baseUrl . '?page=' . ($currentPage + 1) . '">Siguiente</a>';
    $html .= '</li>';

    $html .= '</ul>';
    $html .= '</nav>';

    return $html;
}
?>
