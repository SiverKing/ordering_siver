<?php
/**
 * 管理员数据文件 - 请勿直接通过浏览器访问
 * Admin credentials file - Do not access directly via browser
 */

if (!defined('RESTAURANT_APP')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden - Access Denied');
}

/**
 * 管理员账号
 * 默认账号: admin / admin123 (请立即修改密码)
 */
$RESTAURANT_ADMINS = [
    'admin' => [
        'password' => '6ed15c1add3ed080f0073f7f7fc3f4ffb7a09a4538bf7ff78e0fce0c674b19f9',
        'created' => '2026-03-09'
    ]
];

return $RESTAURANT_ADMINS;
