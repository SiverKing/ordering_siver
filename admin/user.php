<?php
/**
 * 用户数据文件 - 请勿直接通过浏览器访问
 * User data file - Do not access directly via browser
 * 通过管理后台 /admin 来管理用户
 */

// 防止直接访问
if (!defined('RESTAURANT_APP')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden - Access Denied');
}

/**
 * 密码哈希函数（SHA256 + 固定盐值）
 * 注意：请在生产环境中使用 PHP password_hash() 替换
 */
define('PASS_SALT', 'restaurant_salt_key_2026');

function hash_user_password($password) {
    return hash('sha256', $password . PASS_SALT);
}

function verify_user_password($password, $hash) {
    return hash_equals(hash('sha256', $password . PASS_SALT), $hash);
}

/**
 * 用户数据
 * 格式: 'username' => ['password' => hash, 'created' => '日期']
 * 默认账号: admin1 / admin123 (请立即在后台修改密码)
 */
$RESTAURANT_USERS = [
    'admin1' => [
        'password' => '6ed15c1add3ed080f0073f7f7fc3f4ffb7a09a4538bf7ff78e0fce0c674b19f9',
        'created' => '2026-03-09'
    ]
];

return $RESTAURANT_USERS;
