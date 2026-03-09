<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';
start_session();

if (is_user_logged_in()) {
    json_response(['success' => true, 'logged_in' => true, 'username' => get_session_user()]);
} else {
    json_response(['success' => true, 'logged_in' => false]);
}
