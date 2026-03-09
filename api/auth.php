<?php
/**
 * 认证公共函数库
 */

define('RESTAURANT_APP', true);

function start_session() {
    if (session_status() === PHP_SESSION_NONE) {
        session_name('RESTAURANT_SID');
        session_start();
    }
}

function get_users_file() {
    return dirname(__DIR__) . '/admin/user.php';
}

function get_admins_file() {
    return dirname(__DIR__) . '/admin/admin.php';
}

function load_users() {
    return include get_users_file();
}

function load_admins() {
    return include get_admins_file();
}

function save_users($users) {
    $file = get_users_file();
    $php = "<?php\n/**\n * 用户数据文件 - 请勿直接通过浏览器访问\n */\n\nif (!defined('RESTAURANT_APP')) {\n    http_response_code(403);\n    header('Content-Type: text/plain; charset=utf-8');\n    exit('403 Forbidden - Access Denied');\n}\n\ndefine('PASS_SALT', 'restaurant_salt_key_2026');\n\nfunction hash_user_password(\$password) {\n    return hash('sha256', \$password . PASS_SALT);\n}\n\nfunction verify_user_password(\$password, \$hash) {\n    return hash_equals(hash('sha256', \$password . PASS_SALT), \$hash);\n}\n\n\$RESTAURANT_USERS = ";
    $php .= var_export($users, true);
    $php .= ";\n\nreturn \$RESTAURANT_USERS;\n";
    return file_put_contents($file, $php) !== false;
}

function save_admins($admins) {
    $file = get_admins_file();
    $php = "<?php\n/**\n * 管理员数据文件 - 请勿直接通过浏览器访问\n */\n\nif (!defined('RESTAURANT_APP')) {\n    http_response_code(403);\n    header('Content-Type: text/plain; charset=utf-8');\n    exit('403 Forbidden - Access Denied');\n}\n\n\$RESTAURANT_ADMINS = ";
    $php .= var_export($admins, true);
    $php .= ";\n\nreturn \$RESTAURANT_ADMINS;\n";
    return file_put_contents($file, $php) !== false;
}

function hash_password($password) {
    return hash('sha256', $password . 'restaurant_salt_key_2026');
}

function verify_password($password, $hash) {
    return hash_equals(hash('sha256', $password . 'restaurant_salt_key_2026'), $hash);
}

function is_user_logged_in() {
    start_session();
    return isset($_SESSION['user']) && !empty($_SESSION['user']);
}

function is_admin_logged_in() {
    start_session();
    return isset($_SESSION['admin']) && !empty($_SESSION['admin']);
}

function get_session_user() {
    start_session();
    return $_SESSION['user'] ?? null;
}

function get_session_admin() {
    start_session();
    return $_SESSION['admin'] ?? null;
}

function require_user_login() {
    if (!is_user_logged_in()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '请先登录', 'code' => 401]);
        exit;
    }
}

function require_admin_login() {
    if (!is_admin_logged_in()) {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => '请先以管理员身份登录', 'code' => 401]);
        exit;
    }
}

function json_response($data) {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function get_order_dir($username) {
    $base = dirname(__DIR__) . '/data/order/' . $username;
    if (!is_dir($base)) {
        mkdir($base, 0755, true);
    }
    return $base;
}

function get_menu_file() {
    return dirname(__DIR__) . '/data/menu.json';
}

function load_menu() {
    $file = get_menu_file();
    if (!file_exists($file)) return [];
    return json_decode(file_get_contents($file), true) ?? [];
}

function save_menu($menu) {
    return file_put_contents(get_menu_file(), json_encode($menu, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)) !== false;
}

// 验证用户名格式（英文字母开头，只含字母和数字，2-16位）
function validate_username($username) {
    return preg_match('/^[a-zA-Z][a-zA-Z0-9]{1,15}$/', $username);
}

// 验证密码格式（只含字母和数字，6-16位）
function validate_password($password) {
    return preg_match('/^[a-zA-Z0-9]{6,16}$/', $password);
}
