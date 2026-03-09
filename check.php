<?php
/**
 * 环境诊断工具 - 用完请删除！
 * 访问：http://你的域名/check.php
 */
header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width,initial-scale=1">
<title>环境诊断</title>
<style>
body{font-family:monospace;padding:20px;background:#1a1a2e;color:#e0e0e0;font-size:14px;}
h2{color:#e8a930;margin-bottom:20px;}
.item{padding:8px 14px;margin:4px 0;border-radius:6px;display:flex;gap:16px;}
.ok{background:rgba(40,167,69,0.2);border-left:3px solid #28a745;}
.fail{background:rgba(220,53,69,0.2);border-left:3px solid #dc3545;}
.warn{background:rgba(255,193,7,0.2);border-left:3px solid #ffc107;}
.label{color:#aaa;width:220px;flex-shrink:0;}
.val{color:#fff;word-break:break-all;}
.section{margin-top:24px;}
.section h3{color:#c8392b;margin-bottom:8px;border-bottom:1px solid #333;padding-bottom:6px;}
.fix{background:#1e3a5f;border:1px solid #2a5298;border-radius:8px;padding:14px;margin-top:12px;}
.fix h4{color:#5bc0de;margin-bottom:8px;}
pre{background:#111;padding:12px;border-radius:6px;overflow-x:auto;color:#7ec8e3;font-size:13px;margin-top:6px;}
</style>
</head>
<body>
<h2>🔍 家庭点餐系统 - 环境诊断</h2>

<?php
$root = __DIR__;
$issues = [];

// =========== PHP基础 ===========
echo '<div class="section"><h3>PHP 基础环境</h3>';

// PHP版本
$phpver = phpversion();
$ok = version_compare($phpver, '7.4', '>=');
echo '<div class="item '.($ok?'ok':'fail').'"><span class="label">PHP 版本</span><span class="val">'.$phpver.' '.($ok?'✅ 满足要求':'❌ 需要 7.4+').'</span></div>';
if(!$ok) $issues[] = 'PHP版本过低';

// Session
@session_start();
$_SESSION['test'] = 'ok';
$sessionOk = isset($_SESSION['test']);
session_destroy();
echo '<div class="item '.($sessionOk?'ok':'fail').'"><span class="label">Session</span><span class="val">'.($sessionOk?'✅ 正常':'❌ Session 不可用，请检查 session.save_path').'</span></div>';
if(!$sessionOk) $issues[] = 'Session不可用';

// JSON
echo '<div class="item ok"><span class="label">JSON 扩展</span><span class="val">'.( extension_loaded('json') ? '✅ 可用' : '❌ 不可用' ).'</span></div>';

// 当前用户
$user = function_exists('posix_getpwuid') ? posix_getpwuid(posix_geteuid())['name'] : get_current_user();
echo '<div class="item warn"><span class="label">PHP 运行用户</span><span class="val">'.$user.'</span></div>';

echo '</div>';

// =========== 目录结构 ===========
echo '<div class="section"><h3>目录结构与权限</h3>';

$dirs = [
    'data'        => $root.'/data',
    'data/order'  => $root.'/data/order',
    'img'         => $root.'/img',
    'api'         => $root.'/api',
    'admin'       => $root.'/admin',
];
$files = [
    'index.html'      => $root.'/index.html',
    'data/menu.json'  => $root.'/data/menu.json',
    'api/auth.php'    => $root.'/api/auth.php',
    'api/login.php'   => $root.'/api/login.php',
    'api/menu.php'    => $root.'/api/menu.php',
    'api/order.php'   => $root.'/api/order.php',
    'admin/user.php'  => $root.'/admin/user.php',
    'admin/admin.php' => $root.'/admin/admin.php',
    'admin/api.php'   => $root.'/admin/api.php',
];

foreach($dirs as $name => $path){
    $exists = is_dir($path);
    $writable = $exists && is_writable($path);
    if(!$exists){
        echo '<div class="item fail"><span class="label">目录 '.$name.'</span><span class="val">❌ 不存在，需要创建</span></div>';
        $issues[] = '目录'.$name.'不存在';
    } elseif(!$writable){
        echo '<div class="item fail"><span class="label">目录 '.$name.'</span><span class="val">❌ 存在但不可写（权限问题）</span></div>';
        $issues[] = '目录'.$name.'不可写';
    } else {
        echo '<div class="item ok"><span class="label">目录 '.$name.'</span><span class="val">✅ 存在且可写</span></div>';
    }
}

foreach($files as $name => $path){
    $exists = file_exists($path);
    $readable = $exists && is_readable($path);
    if(!$exists){
        echo '<div class="item fail"><span class="label">文件 '.$name.'</span><span class="val">❌ 文件不存在</span></div>';
        $issues[] = '文件'.$name.'不存在';
    } elseif(!$readable){
        echo '<div class="item fail"><span class="label">文件 '.$name.'</span><span class="val">❌ 不可读（权限问题）</span></div>';
    } else {
        echo '<div class="item ok"><span class="label">文件 '.$name.'</span><span class="val">✅ 存在且可读</span></div>';
    }
}

echo '</div>';

// =========== API 功能测试 ===========
echo '<div class="section"><h3>API 功能测试</h3>';

// 测试 include auth.php
$authPath = $root.'/api/auth.php';
if(file_exists($authPath)){
    try {
        define('RESTAURANT_APP', true);
        ob_start();
        include_once $authPath;
        ob_end_clean();
        
        // 测试读取用户
        $userFile = $root.'/admin/user.php';
        if(file_exists($userFile)){
            ob_start();
            $users = include $userFile;
            ob_end_clean();
            if(is_array($users) && count($users) > 0){
                echo '<div class="item ok"><span class="label">读取用户数据</span><span class="val">✅ 成功，共 '.count($users).' 个用户：'.implode(', ', array_keys($users)).'</span></div>';
            } else {
                echo '<div class="item fail"><span class="label">读取用户数据</span><span class="val">❌ 用户数据为空或格式错误</span></div>';
                $issues[] = '用户数据异常';
            }
        }
        
        // 测试读取管理员
        $adminFile = $root.'/admin/admin.php';
        if(file_exists($adminFile)){
            ob_start();
            $admins = include $adminFile;
            ob_end_clean();
            if(is_array($admins) && count($admins) > 0){
                echo '<div class="item ok"><span class="label">读取管理员数据</span><span class="val">✅ 成功，管理员：'.implode(', ', array_keys($admins)).'</span></div>';
            } else {
                echo '<div class="item fail"><span class="label">读取管理员数据</span><span class="val">❌ 管理员数据异常</span></div>';
                $issues[] = '管理员数据异常';
            }
        }
        
        // 测试密码验证
        if(function_exists('hash_password') && function_exists('verify_password')){
            $testHash = hash_password('admin123');
            $verifyOk = verify_password('admin123', $testHash);
            echo '<div class="item '.($verifyOk?'ok':'fail').'"><span class="label">密码哈希验证</span><span class="val">'.($verifyOk?'✅ 正常':'❌ 哈希函数异常').'</span></div>';
        }
        
        // 测试菜单读取
        $menuFile = $root.'/data/menu.json';
        if(file_exists($menuFile)){
            $menu = json_decode(file_get_contents($menuFile), true);
            if(is_array($menu) && count($menu) > 0){
                echo '<div class="item ok"><span class="label">菜单数据</span><span class="val">✅ 成功读取，共 '.count($menu).' 道菜</span></div>';
            } else {
                echo '<div class="item fail"><span class="label">菜单数据</span><span class="val">❌ menu.json 格式异常</span></div>';
                $issues[] = 'menu.json格式异常';
            }
        }
        
    } catch(Exception $e){
        echo '<div class="item fail"><span class="label">API 加载</span><span class="val">❌ 错误：'.$e->getMessage().'</span></div>';
        $issues[] = 'API加载失败：'.$e->getMessage();
    }
}

// 测试写入权限
$testFile = $root.'/data/order/.write_test';
$writeOk = @file_put_contents($testFile, 'test') !== false;
if($writeOk){ @unlink($testFile); }
echo '<div class="item '.($writeOk?'ok':'fail').'"><span class="label">data/order 写入测试</span><span class="val">'.($writeOk?'✅ 可以写入':'❌ 无法写入，下单会失败').'</span></div>';
if(!$writeOk) $issues[] = 'data/order目录无法写入';

// 测试admin目录写入
$testFile2 = $root.'/admin/.write_test';
$writeOk2 = @file_put_contents($testFile2, 'test') !== false;
if($writeOk2){ @unlink($testFile2); }
echo '<div class="item '.($writeOk2?'ok':'warn').'"><span class="label">admin 目录写入测试</span><span class="val">'.($writeOk2?'✅ 可以写入（用户管理功能正常）':'⚠️ 无法写入（添加/修改用户会失败）').'</span></div>';

echo '</div>';

// =========== 服务器信息 ===========
echo '<div class="section"><h3>服务器信息</h3>';
echo '<div class="item warn"><span class="label">服务器软件</span><span class="val">'.($_SERVER['SERVER_SOFTWARE']??'未知').'</span></div>';
echo '<div class="item warn"><span class="label">文档根目录</span><span class="val">'.($_SERVER['DOCUMENT_ROOT']??'未知').'</span></div>';
echo '<div class="item warn"><span class="label">当前脚本路径</span><span class="val">'.__FILE__.'</span></div>';
echo '<div class="item warn"><span class="label">session.save_path</span><span class="val">'.ini_get('session.save_path').'</span></div>';
echo '</div>';

// =========== 修复建议 ===========
if(count($issues) > 0){
    echo '<div class="section"><h3>❌ 发现问题，修复建议</h3>';
    echo '<div class="fix"><h4>在服务器上执行以下命令（将路径替换为你的实际网站目录）：</h4>';
    echo '<pre>';
    $webroot = rtrim($root, '/');
    echo "# 创建缺少的目录\n";
    echo "mkdir -p {$webroot}/data/order\n";
    echo "mkdir -p {$webroot}/img\n\n";
    echo "# 修复权限（宝塔通常运行用户是 www）\n";
    echo "chown -R www:www {$webroot}/data/\n";
    echo "chown -R www:www {$webroot}/admin/\n";
    echo "chown -R www:www {$webroot}/img/\n";
    echo "chmod -R 755 {$webroot}/data/\n";
    echo "chmod -R 755 {$webroot}/admin/\n";
    echo "chmod -R 755 {$webroot}/img/\n";
    echo '</pre></div></div>';
} else {
    echo '<div class="section"><div class="item ok" style="font-size:16px;"><span class="val">✅ 环境检测全部通过！如果还有问题，请检查浏览器开发者工具的 Network 标签页，看 API 返回了什么内容。</span></div></div>';
}
?>

<div class="section" style="margin-top:30px;padding:14px;background:rgba(220,53,69,0.15);border:1px solid #dc3545;border-radius:8px;">
  <strong style="color:#dc3545;">⚠️ 重要提示：</strong> 诊断完成后请立即删除此文件（check.php），避免泄露服务器信息！
</div>

</body>
</html>