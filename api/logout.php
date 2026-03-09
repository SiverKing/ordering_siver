<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';
start_session();

session_destroy();
json_response(['success' => true, 'message' => '已退出登录']);
