<?php
/**
 * 管理员数据文件 - 请勿直接通过浏览器访问
 */

if (!defined('RESTAURANT_APP')) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    exit('403 Forbidden - Access Denied');
}

$RESTAURANT_ADMINS = array (
  'admin' => 
  array (
    'password' => '30ed51c18a3d73a2a4c675a52a7c686cd82a9dadc620c462457d74ee82075182',
    'created' => '2026-03-09',
  ),
);

return $RESTAURANT_ADMINS;
