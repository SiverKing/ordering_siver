# 家庭点餐系统 - 部署说明

## 目录结构

```
./
├── index.html            ← 前台点餐页面（入口）
├── .htaccess             ← Apache 安全配置
├── data/
│   ├── menu.json         ← 菜单数据（46道菜）
│   └── order/            ← 订单数据（按用户分目录）
│       └── admin1/       ← 用户 admin1 的订单目录
│           └── 20260309.json
├── img/                  ← 菜品图片目录（自行上传）
├── api/                  ← 前台 PHP API
│   ├── auth.php          ← 认证公共函数
│   ├── login.php         ← 用户登录
│   ← logout.php          ← 退出
│   ├── session.php       ← 会话检查
│   ├── menu.php          ← 获取菜单
│   └── order.php         ← 订单管理
└── admin/                ← 后台管理
    ├── index.html        ← 后台管理页面
    ├── api.php           ← 后台 CRUD API
    ├── user.php          ← 用户账号数据（受保护）
    ├── admin.php         ← 管理员账号数据（受保护）
    └── .htaccess         ← 保护 user.php 和 admin.php
```

## 部署要求

- PHP 7.4+（需开启 sessions）
- Apache 或 Nginx Web 服务器
- 需要对以下目录有写权限：
  - `./data/` 及其子目录
  - `./admin/` （更新用户数据时）
  - `./img/` （上传图片时）

## 快速开始

1. **部署文件**：将整个目录上传至 Web 服务器根目录
2. **设置权限**：
   ```bash
   chmod 755 data/ data/order/ admin/ img/
   chmod 644 data/menu.json admin/user.php admin/admin.php
   ```
3. **访问前台**：打开 `http://你的域名/`
4. **默认用户账号**：`admin1` / `admin123`
5. **访问后台**：打开 `http://你的域名/admin`
6. **默认管理员账号**：`admin` / `admin123`

## ⚠️ 安全注意事项

- **立即修改默认密码**！在后台用户管理和管理员设置中修改。
- `admin/user.php` 和 `admin/admin.php` 已通过 `.htaccess` 阻止直接 HTTP 访问
- 密码使用 SHA256 + 固定盐值哈希存储（可在 `api/auth.php` 中修改 `PASS_SALT` 常量）

## 功能说明

### 前台（index.html）
- **登录**：多用户支持，各自独立的订单数据
- **点菜**：按分类浏览，支持搜索，一键添加/删除购物车
- **随机出菜**：可指定数量和排除历史天数，自动按荤素汤配比
- **结算**：选择早/中/晚餐，生成订单
- **历史订单**：分页加载，点击查看详情

### 后台（/admin）
- **菜品管理**：增删改查，支持上传图片
- **订单管理**：查看所有用户订单，可按用户过滤，支持删除
- **用户管理**：添加/修改密码/删除用户

### 用户名/密码规则
- 用户名：英文字母开头，只含字母和数字，2-16位
- 密码：只含字母和数字，6-16位

## Nginx 配置参考

```nginx
server {
    root /var/www/restaurant;
    index index.html;
    
    # 保护敏感 PHP 文件
    location ~* /admin/(user|admin)\.php$ {
        deny all;
        return 403;
    }
    
    # 保护 data 目录
    location /data/ {
        deny all;
        return 403;
    }
    
    location / {
        try_files $uri $uri/ =404;
    }
    
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```
