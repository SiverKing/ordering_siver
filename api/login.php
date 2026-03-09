<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once __DIR__ . '/auth.php';
start_session();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'message' => '请求方法错误']);
}

$data = json_decode(file_get_contents('php://input'), true);
$username = trim($data['username'] ?? '');
$password = trim($data['password'] ?? '');

if (empty($username) || empty($password)) {
    json_response(['success' => false, 'message' => '请输入用户名和密码']);
}

$users = load_users();

if (!isset($users[$username])) {
    json_response(['success' => false, 'message' => '用户名或密码错误']);
}

if (!verify_password($password, $users[$username]['password'])) {
    json_response(['success' => false, 'message' => '用户名或密码错误']);
}

// 登录成功
$_SESSION['user'] = $username;
$_SESSION['logged_in'] = true;
$_SESSION['login_time'] = time();

json_response([
    'success' => true,
    'username' => $username,
    'message' => '登录成功'
]);
