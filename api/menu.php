<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';
start_session();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    // 前台获取菜单（需要登录）
    require_user_login();
    $menu = load_menu();
    json_response(['success' => true, 'data' => $menu]);
}

json_response(['success' => false, 'message' => '请求方法不支持']);
