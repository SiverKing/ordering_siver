<?php
/**
 * 上传诊断工具 v2 - 用完请删除
 */
header('Content-Type: text/html; charset=utf-8');

$imgDir = dirname(__DIR__) . '/img/';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES)) {
    header('Content-Type: application/json');
    $f = $_FILES['image'] ?? null;
    if (!$f) { echo json_encode(['error'=>'没有名为 image 的文件字段']); exit; }

    // 扩展名判断
    $origExt = strtolower(pathinfo($f['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg'=>'jpg','jpeg'=>'jpg','png'=>'png','gif'=>'gif','webp'=>'webp'];
    $ext     = $allowed[$origExt] ?? null;

    // 尝试移动文件
    $moveResult = false;
    $moveDest   = '';
    if ($f['error'] === 0 && $ext) {
        $moveDest   = $imgDir . 'test_' . time() . '.' . $ext;
        $moveResult = move_uploaded_file($f['tmp_name'], $moveDest);
    }

    echo json_encode([
        'php_upload_error'   => $f['error'],
        'php_upload_meaning' => [0=>'OK',1=>'超php.ini限制',2=>'超表单限制',3=>'部分上传',4=>'无文件',6=>'无临时目录',7=>'写入失败'][$f['error']] ?? '未知',
        'original_name'      => $f['name'],
        'detected_ext'       => $origExt,
        'ext_allowed'        => $ext ? true : false,
        'size_bytes'         => $f['size'],
        'tmp_name'           => $f['tmp_name'],
        'tmp_exists'         => file_exists($f['tmp_name']),
        'img_dir'            => $imgDir,
        'img_dir_exists'     => is_dir($imgDir),
        'img_dir_writable'   => is_writable($imgDir),
        'fileinfo_ext'       => extension_loaded('fileinfo') ? '已启用' : '未启用（不影响上传）',
        'move_dest'          => $moveDest,
        'move_success'       => $moveResult,
        'conclusion'         => $moveResult ? '✅ 上传成功！说明上传功能本身正常。' : '❌ 上传失败，查看上方各项定位原因',
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}
?>
<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>上传测试 v2</title>
<style>
body{font-family:monospace;padding:24px;background:#111;color:#eee;max-width:700px;}
h2{color:#e8a930;margin-bottom:16px;}
input[type=file]{color:#eee;margin:10px 0;display:block;font-size:14px;}
button{padding:10px 24px;background:#c8392b;color:#fff;border:none;border-radius:8px;cursor:pointer;font-size:14px;margin-top:4px;}
button:hover{background:#9b2c1f;}
pre{background:#1a1a2e;padding:16px;border-radius:8px;margin-top:16px;color:#7ec8e3;white-space:pre-wrap;word-break:break-all;line-height:1.6;}
.warn{margin-top:16px;padding:12px;background:#2a1a0a;border:1px solid #c8392b;border-radius:8px;color:#f5a623;font-size:13px;}
</style></head><body>
<h2>📤 图片上传诊断 v2</h2>
<p style="color:#aaa;font-size:13px;margin-bottom:14px;">选择一张图片，点击测试，查看详细结果定位问题</p>
<input type="file" id="f" accept="image/*">
<button onclick="doTest()">开始测试上传</button>
<pre id="out">等待上传...</pre>
<div class="warn">⚠️ 测试完成后请立即删除此文件！（在服务器文件管理器里删除 upload_test.php）</div>
<script>
async function doTest(){
  const file = document.getElementById('f').files[0];
  if(!file){document.getElementById('out').textContent='请先选择文件';return;}
  document.getElementById('out').textContent='上传中，请稍候...';
  const fd = new FormData();
  fd.append('image', file);
  try {
    const r = await fetch('upload_test.php', {method:'POST', body:fd});
    const text = await r.text();
    try {
      const json = JSON.parse(text);
      document.getElementById('out').textContent = JSON.stringify(json, null, 2);
    } catch(e) {
      document.getElementById('out').textContent = '【服务器原始响应 HTTP ' + r.status + '】\n' + text;
    }
  } catch(e) {
    document.getElementById('out').textContent = '网络请求失败: ' + e.message;
  }
}
</script>
</body></html>
