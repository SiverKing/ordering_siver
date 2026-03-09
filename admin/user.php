<?php
/**
 * 用户数据文件 - 请勿直接通过浏览器访问
 */

if (!defined('RESTAURANT_APP')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden - Access Denied');
}

define('PASS_SALT', 'restaurant_salt_key_2026');

function hash_user_password($password) {
    return hash('sha256', $password . PASS_SALT);
}

function verify_user_password($password, $hash) {
    return hash_equals(hash('sha256', $password . PASS_SALT), $hash);
}

$RESTAURANT_USERS = array (
  'admin1' => 
  array (
    'password' => '30ed51c18a3d73a2a4c675a52a7c686cd82a9dadc620c462457d74ee82075182',
    'created' => '2026-03-09',
  ),
);

return $RESTAURANT_USERS;
