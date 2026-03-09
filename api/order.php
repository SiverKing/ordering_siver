<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/auth.php';
start_session();

require_user_login();

$method = $_SERVER['REQUEST_METHOD'];
$username = get_session_user();

if ($method === 'GET') {
    $action = $_GET['action'] ?? 'list';

    if ($action === 'list') {
        // 获取订单列表（分页）
        $page = max(1, intval($_GET['page'] ?? 1));
        $limit = 10; // 每次加载10个订单日期
        
        $dir = get_order_dir($username);
        $files = glob($dir . '/*.json');
        if (!$files) $files = [];
        
        // 按日期降序排列
        rsort($files);
        
        $total = count($files);
        $paged = array_slice($files, ($page - 1) * $limit, $limit);
        
        $orders = [];
        foreach ($paged as $file) {
            $date = basename($file, '.json');
            $data = json_decode(file_get_contents($file), true);
            if ($data) {
                $orders[] = [
                    'date' => $date,
                    'meals' => $data
                ];
            }
        }
        
        json_response([
            'success' => true,
            'data' => $orders,
            'total' => $total,
            'page' => $page,
            'has_more' => ($page * $limit) < $total
        ]);
    }
    
    if ($action === 'detail') {
        $date = preg_replace('/[^0-9]/', '', $_GET['date'] ?? '');
        if (!$date) json_response(['success' => false, 'message' => '日期参数错误']);
        
        $dir = get_order_dir($username);
        $file = $dir . '/' . $date . '.json';
        
        if (!file_exists($file)) {
            json_response(['success' => true, 'data' => null]);
        }
        
        $data = json_decode(file_get_contents($file), true);
        json_response(['success' => true, 'data' => $data, 'date' => $date]);
    }
    
    if ($action === 'history_dates') {
        // 获取最近N天内有记录的日期（用于随机出菜去重）
        $days = max(1, min(90, intval($_GET['days'] ?? 7)));
        
        $dir = get_order_dir($username);
        $files = glob($dir . '/*.json');
        if (!$files) $files = [];
        
        $cutoff = date('Ymd', strtotime('-' . $days . ' days'));
        $dishes_in_history = [];
        
        foreach ($files as $file) {
            $date = basename($file, '.json');
            if ($date < $cutoff) continue;
            
            $data = json_decode(file_get_contents($file), true);
            if (!$data) continue;
            
            foreach (['breakfast', 'lunch', 'dinner'] as $meal) {
                if (!empty($data[$meal]['items'])) {
                    foreach ($data[$meal]['items'] as $item) {
                        $dishes_in_history[] = $item['name'];
                    }
                }
            }
        }
        
        json_response(['success' => true, 'dishes' => array_unique($dishes_in_history)]);
    }
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $action = $data['action'] ?? 'save';
    
    if ($action === 'save') {
        // 保存订单
        $meal_type = $data['meal_type'] ?? ''; // breakfast/lunch/dinner
        $items = $data['items'] ?? [];
        $date = $data['date'] ?? date('Ymd');
        $time = $data['time'] ?? date('H:i:s');
        
        // 验证
        $date = preg_replace('/[^0-9]/', '', $date);
        if (!in_array($meal_type, ['breakfast', 'lunch', 'dinner'])) {
            json_response(['success' => false, 'message' => '餐次类型错误']);
        }
        if (empty($items)) {
            json_response(['success' => false, 'message' => '请选择菜品']);
        }
        
        $dir = get_order_dir($username);
        $file = $dir . '/' . $date . '.json';
        
        // 读取已有订单
        $existing = [];
        if (file_exists($file)) {
            $existing = json_decode(file_get_contents($file), true) ?? [];
        }
        
        // 覆盖对应餐次
        $existing[$meal_type] = [
            'items' => $items,
            'time' => $time,
            'count' => count($items)
        ];
        
        file_put_contents($file, json_encode($existing, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
        json_response(['success' => true, 'message' => '订单已保存']);
    }
}

json_response(['success' => false, 'message' => '请求方法不支持']);
