<?php
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

require_once dirname(__DIR__) . '/api/auth.php';
start_session();

$method = $_SERVER['REQUEST_METHOD'];
$input = $method === 'POST' ? json_decode(file_get_contents('php://input'), true) : $_GET;
$action = $input['action'] ?? '';

// ===================== 管理员登录/认证 =====================
if ($action === 'admin_login') {
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    
    if (empty($username) || empty($password)) {
        json_response(['success' => false, 'message' => '请输入用户名和密码']);
    }
    
    $admins = load_admins();
    if (!isset($admins[$username]) || !verify_password($password, $admins[$username]['password'])) {
        json_response(['success' => false, 'message' => '管理员账号或密码错误']);
    }
    
    $_SESSION['admin'] = $username;
    $_SESSION['admin_login_time'] = time();
    json_response(['success' => true, 'username' => $username, 'message' => '登录成功']);
}

if ($action === 'admin_logout') {
    unset($_SESSION['admin']);
    json_response(['success' => true, 'message' => '已退出']);
}

if ($action === 'admin_session') {
    json_response([
        'success' => true,
        'logged_in' => is_admin_logged_in(),
        'username' => get_session_admin()
    ]);
}

// 以下操作需要管理员登录
require_admin_login();

// ===================== 菜品管理 =====================
if ($action === 'menu_list') {
    json_response(['success' => true, 'data' => load_menu()]);
}

if ($action === 'menu_add') {
    $menu = load_menu();
    $item = [
        'id' => count($menu) > 0 ? (max(array_column($menu, 'id')) + 1) : 1,
        'category' => trim($input['category'] ?? ''),
        'name' => trim($input['name'] ?? ''),
        'image' => trim($input['image'] ?? ''),
        'ingredients' => trim($input['ingredients'] ?? ''),
        'process' => trim($input['process'] ?? '')
    ];
    
    if (empty($item['category']) || empty($item['name'])) {
        json_response(['success' => false, 'message' => '分类和菜名不能为空']);
    }
    
    $menu[] = $item;
    save_menu($menu);
    json_response(['success' => true, 'message' => '菜品已添加', 'data' => $item]);
}

if ($action === 'menu_update') {
    $menu = load_menu();
    $id = intval($input['id'] ?? 0);
    
    foreach ($menu as &$item) {
        if ($item['id'] === $id) {
            $item['category'] = trim($input['category'] ?? $item['category']);
            $item['name'] = trim($input['name'] ?? $item['name']);
            $item['image'] = trim($input['image'] ?? $item['image']);
            $item['ingredients'] = trim($input['ingredients'] ?? $item['ingredients']);
            $item['process'] = trim($input['process'] ?? $item['process']);
            save_menu($menu);
            json_response(['success' => true, 'message' => '菜品已更新', 'data' => $item]);
        }
    }
    json_response(['success' => false, 'message' => '菜品不存在']);
}

if ($action === 'menu_delete') {
    $menu = load_menu();
    $id = intval($input['id'] ?? 0);
    $new_menu = array_values(array_filter($menu, fn($item) => $item['id'] !== $id));
    
    if (count($new_menu) === count($menu)) {
        json_response(['success' => false, 'message' => '菜品不存在']);
    }
    
    save_menu($new_menu);
    json_response(['success' => true, 'message' => '菜品已删除']);
}

// ===================== 图片上传 =====================
if ($action === 'upload_image') {
    if (!isset($_FILES['image'])) {
        json_response(['success' => false, 'message' => '没有上传文件']);
    }
    
    $file = $_FILES['image'];
    $allowed = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
    
    if (!in_array($file['type'], $allowed)) {
        json_response(['success' => false, 'message' => '只支持 JPG/PNG/GIF/WEBP 格式']);
    }
    
    if ($file['size'] > 5 * 1024 * 1024) {
        json_response(['success' => false, 'message' => '文件大小不能超过5MB']);
    }
    
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = time() . '_' . uniqid() . '.' . $ext;
    $dest = dirname(__DIR__) . '/img/' . $filename;
    
    if (move_uploaded_file($file['tmp_name'], $dest)) {
        json_response(['success' => true, 'path' => './img/' . $filename, 'message' => '上传成功']);
    } else {
        json_response(['success' => false, 'message' => '上传失败，请检查目录权限']);
    }
}

// ===================== 用户管理 =====================
if ($action === 'user_list') {
    $users = load_users();
    $list = [];
    foreach ($users as $uname => $udata) {
        $list[] = ['username' => $uname, 'created' => $udata['created']];
    }
    json_response(['success' => true, 'data' => $list]);
}

if ($action === 'user_add') {
    $username = trim($input['username'] ?? '');
    $password = trim($input['password'] ?? '');
    
    if (!validate_username($username)) {
        json_response(['success' => false, 'message' => '用户名格式错误：需以英文字母开头，只含字母和数字，2-16位']);
    }
    if (!validate_password($password)) {
        json_response(['success' => false, 'message' => '密码格式错误：只含字母和数字，6-16位']);
    }
    
    $users = load_users();
    if (isset($users[$username])) {
        json_response(['success' => false, 'message' => '用户名已存在']);
    }
    
    $users[$username] = [
        'password' => hash_password($password),
        'created' => date('Y-m-d')
    ];
    
    // 创建用户订单目录
    get_order_dir($username);
    
    save_users($users);
    json_response(['success' => true, 'message' => '用户已添加']);
}

if ($action === 'user_update_password') {
    $username = trim($input['username'] ?? '');
    $new_password = trim($input['password'] ?? '');
    
    if (!validate_password($new_password)) {
        json_response(['success' => false, 'message' => '密码格式错误：只含字母和数字，6-16位']);
    }
    
    $users = load_users();
    if (!isset($users[$username])) {
        json_response(['success' => false, 'message' => '用户不存在']);
    }
    
    $users[$username]['password'] = hash_password($new_password);
    save_users($users);
    json_response(['success' => true, 'message' => '密码已更新']);
}

if ($action === 'user_delete') {
    $username = trim($input['username'] ?? '');
    
    $users = load_users();
    if (!isset($users[$username])) {
        json_response(['success' => false, 'message' => '用户不存在']);
    }
    
    unset($users[$username]);
    save_users($users);
    json_response(['success' => true, 'message' => '用户已删除']);
}

// ===================== 订单管理 =====================
if ($action === 'order_list') {
    // 列出所有用户的订单
    $base = dirname(__DIR__) . '/data/order';
    $result = [];
    
    $user_filter = $input['username'] ?? '';
    
    if ($user_filter) {
        $users_to_check = [$user_filter];
    } else {
        $users_to_check = array_keys(load_users());
    }
    
    foreach ($users_to_check as $uname) {
        $dir = $base . '/' . $uname;
        if (!is_dir($dir)) continue;
        
        $files = glob($dir . '/*.json');
        if (!$files) continue;
        
        foreach ($files as $file) {
            $date = basename($file, '.json');
            $data = json_decode(file_get_contents($file), true);
            $result[] = [
                'username' => $uname,
                'date' => $date,
                'meals' => $data
            ];
        }
    }
    
    // 按日期降序
    usort($result, fn($a, $b) => strcmp($b['date'], $a['date']));
    
    json_response(['success' => true, 'data' => $result]);
}

if ($action === 'order_delete') {
    $username = trim($input['username'] ?? '');
    $date = preg_replace('/[^0-9]/', '', $input['date'] ?? '');
    $meal = $input['meal'] ?? ''; // 可以删除整天或某餐
    
    $dir = dirname(__DIR__) . '/data/order/' . $username;
    $file = $dir . '/' . $date . '.json';
    
    if (!file_exists($file)) {
        json_response(['success' => false, 'message' => '订单不存在']);
    }
    
    if (empty($meal)) {
        // 删除整天订单
        unlink($file);
        json_response(['success' => true, 'message' => '订单已删除']);
    } else {
        // 删除某餐
        $data = json_decode(file_get_contents($file), true);
        unset($data[$meal]);
        if (empty($data)) {
            unlink($file);
        } else {
            file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        }
        json_response(['success' => true, 'message' => '餐次已删除']);
    }
}

if ($action === 'order_update') {
    $username = trim($input['username'] ?? '');
    $date = preg_replace('/[^0-9]/', '', $input['date'] ?? '');
    $meal = $input['meal'] ?? '';
    $items = $input['items'] ?? [];
    
    $dir = dirname(__DIR__) . '/data/order/' . $username;
    $file = $dir . '/' . $date . '.json';
    
    if (!file_exists($file)) {
        json_response(['success' => false, 'message' => '订单不存在']);
    }
    
    $data = json_decode(file_get_contents($file), true);
    if (!empty($meal) && in_array($meal, ['breakfast', 'lunch', 'dinner'])) {
        $data[$meal]['items'] = $items;
        $data[$meal]['count'] = count($items);
    }
    
    file_put_contents($file, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
    json_response(['success' => true, 'message' => '订单已更新']);
}

json_response(['success' => false, 'message' => '未知操作']);
