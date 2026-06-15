# LarkFrame 项目模板

基于 [LarkFrame Core](https://github.com/larkframe/core) 框架的项目模板，开箱即用。

## 环境要求

- PHP >= 8.1
- ext-json, ext-pdo, ext-gd
- 推荐：ext-redis, ext-event
- Composer

## 快速开始

```bash
# 1. 创建项目
composer create-project larkframe/template myapp
cd myapp

# 2. 启动开发服务器
php server.php
```

默认监听 `http://0.0.0.0:8080`

> 首次启动时，框架会自动在项目根目录创建 `.env` 文件（包含 `APP_NAME`、`TIME_ZONE`、`RUN_MODE`），无需手动配置。

## 项目结构

```
myapp/
├── app/                        # 应用代码
│   ├── Base/                   # 基类
│   │   ├── Controller.php      # 控制器基类
│   │   ├── ErrorHandler.php    # 错误处理器
│   │   └── Model.php           # 模型基类
│   ├── Controller/             # 控制器
│   │   ├── DefaultController.php
│   │   └── ShellController.php
│   ├── Job/                    # 队列任务
│   │   └── SendEmailJob.php
│   ├── Library/                # 业务类库
│   │   └── Notify.php
│   ├── Middleware/             # 中间件
│   │   └── CorsMiddleware.php
│   ├── Model/                  # 数据模型
│   │   └── UserModel.php
│   └── Task/                   # 常驻任务
│       ├── ConsumeTask.php
│       └── CronTask.php
├── config/                     # 配置文件
│   ├── config.php              # 主配置
│   ├── notify.php              # 通知配置（自动加载）
│   ├── route.php               # 路由定义
│   └── task.php                # 任务配置
├── public/                     # Web 入口
│   ├── index.php               # FPM 入口
│   └── favicon.ico
├── template/                   # 视图模板
│   ├── default/
│   └── user/
├── .gitignore
├── composer.json
├── server.php             # Server 模式入口
├── shell.php              # Shell 模式入口
├── task.php               # Task 模式入口
└── README.md
```

## 核心功能示例

### 路由

路由定义在 `config/route.php`：

```php
use LarkFrame\Route;

// 基础路由
Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'store']);
Route::put('/users/{id}', [UserController::class, 'update']);
Route::delete('/users/{id}', [UserController::class, 'destroy']);

// 带参数的路由
Route::get('/users/{id:\d+}', function ($request) {
    $id = $request->route()->param('id');
    return json(['user_id' => $id]);
});

// 命令行路由
Route::shell('/migrate', [MigrateController::class, 'run']);
```

### 控制器

```php
namespace App\Controller;

use LarkFrame\Request;

class UserController
{
    public function indexAction(Request $request)
    {
        $page = $request->input('page', 1);
        $users = \LarkFrame\Db::table('users')->paginate(15, ['*'], 'page', $page);
        return json(['code' => 0, 'data' => $users]);
    }

    public function showAction(Request $request)
    {
        $id = $request->route()->param('id');
        $user = \LarkFrame\Db::table('users')->where('id', $id)->first();
        return json(['code' => 0, 'data' => $user]);
    }
}
```

### 数据库

```php
use LarkFrame\Db;

// 查询构建器
$users = Db::table('users')->where('status', 'active')->get();

// 多库切换
$orders = Db::use('order_db')->table('orders')->get();

// Eloquent Model
$user = \App\Model\UserModel::find(1);
$user = \App\Model\UserModel::create(['name' => 'John', 'email' => 'john@example.com']);
```

### 缓存

```php
use LarkFrame\Cache\Cache;

Cache::set('key', 'value', 3600);
$value = Cache::get('key');
Cache::delete('key');
```

### Redis

```php
use LarkFrame\Cache\Redis;

Redis::set('key', 'value');
Redis::get('key');
Redis::use('cache', 1)->set('key', 'value');  // 切换连接和数据库
```

### 队列

```php
use LarkFrame\Queue;

// 推送任务
Queue::push('emails', \App\Job\SendEmailJob::class, ['to' => 'user@example.com']);

// 延迟推送
Queue::later('emails', 60, \App\Job\SendEmailJob::class, ['to' => 'user@example.com']);

// 队列大小
$size = Queue::size('emails');
```

队列任务类：

```php
namespace App\Job;

use LarkFrame\Queue\Job;

class SendEmailJob
{
    public function handle(Job $job, mixed $data): void
    {
        // 处理逻辑
        $job->ack();
    }
}
```

### 中间件

```php
use LarkFrame\MiddlewareInterface;
use LarkFrame\Request;
use LarkFrame\Response;

class AuthMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        if (!$request->header('authorization')) {
            return json(['error' => 'Unauthorized'], 401);
        }
        return $handler($request);
    }
}
```

注册中间件：

```php
// 全局中间件 — config/config.php 的 server.middleware
'middleware' => [
    \App\Middleware\CorsMiddleware::class,
],

// 路由中间件
Route::get('/admin', [AdminController::class, 'index'])
    ->middleware(AuthMiddleware::class);
```

### 工具集

```php
use LarkFrame\Util;

Util::str()->mask('13800138000');           // 138****8000
Util::rand()->uuid();                       // UUID v4
Util::base64()->urlEncode($data);           // URL 安全 Base64
Util::str()->formatBytes(1048576);          // 1 MB
Util::file()->ensureDir($dir);              // 确保目录存在
```

### 视图

```php
// 原生 PHP 模板
return raw_view('default/index', ['username' => 'world'], 'php');

// Twig 模板
return twig_view('default/index', ['username' => 'world'], 'twig');
```

## 配置说明

### 环境变量 (.env)

`.env` 文件仅用于定义应用启动参数，框架首次启动时会自动创建。它**不会**注入到 `config()` 配置系统中。

```ini
APP_NAME=myapp        # 应用名称，定义为 APP_NAME 常量
TIME_ZONE=Asia/Shanghai  # 时区
RUN_MODE=dev          # 运行模式：dev/test/stage/prod，定义为 RUN_MODE 常量
```

> 敏感配置（数据库密码、Redis 密码等）应通过 `config/config.{env}.php` 环境覆盖文件管理，而非 `.env`。

### 多环境配置

```
config/
  config.php           # 主配置
  config.dev.php       # 开发环境覆盖
  config.prod.php      # 生产环境覆盖
```

### 自定义类库配置

`app/Library/Notify.php` 对应 `config/notify.php`，配置自动加载到 `$this->config`：

```php
// config/notify.php
return [
    'mail' => ['host' => 'smtp.example.com', ...],
];

// app/Library/Notify.php
class Notify extends \LarkFrame\Library
{
    public function send()
    {
        $config = $this->config['mail'];  // 自动加载
    }
}
```

## 运行模式

框架通过入口文件显式指定运行模式（`App::run(LarkFrame\Consts::RUN_TYPE_*)`）：

| 模式 | 启动方式 | 入口文件 |
|------|---------|---------|
| Server | `php server.php` | `server.php` |
| Shell | `php shell.php route "key=value"` | `shell.php` |
| Task | `php task.php taskname [args]` | `task.php` |
| Web | 浏览器访问 | `public/index.php` |

### Server 模式（推荐）

常驻内存，事件驱动，高性能，支持多 Worker 进程。

```bash
# 启动 Server（前台运行，开发调试）
php server.php

# 后台守护运行
# 在 config/config.php 中设置 'daemonize' => true，然后启动
php server.php

# 停止服务（通过 PID）
kill $(cat runtime/server.pid)
```

Server 模式启动后监听 `config.server.socketName` 指定的地址（默认 `http://0.0.0.0:8080`），通过 HTTP 协议接收请求，路由匹配和请求处理在 Worker 进程中完成。

配置项（`config/config.php`）：

```php
'server' => [
    'socketName' => 'http://0.0.0.0:8080',  // 监听地址
    'daemonize' => false,                    // 是否守护进程
    'worker' => [
        'count' => 4,                        // Worker 进程数
        'reusePort' => true,                 // 端口复用
    ],
    'middleware' => [...],                    // 全局中间件
],
```

### Shell 模式

命令行执行单次任务，路由匹配 `Route::shell()` 定义的路由。

```bash
# 执行 shell 路由
php shell.php migrate
php shell.php queue/work

# 路由参数
php shell.php users/import "file=data.csv"
```

Shell 模式下：
- HTTP 方法自动设为 `SHELL`
- 路由取 `$_SERVER['argv'][1]`（自动补 `/` 前缀）
- 参数取 `$_SERVER['argv'][2]`，格式为 `"key=value&key2=value2"`（通过 `parse_str` 解析为 GET/POST 参数）
- 请求源为 `LarkFrame\Request\ShellSource`
- 执行完毕后进程退出

### Task 模式

常驻内存 Worker 进程执行自定义任务，适合队列消费、定时任务、后台处理等场景。taskname 对应 `config/task.php` 中定义的任务配置。

```bash
# 前台运行（默认 start）
php task.php cron

# 停止任务
php task.php cron stop

# 重启任务
php task.php cron restart

# 查看状态
php task.php cron status

# 带参数启动
php task.php cleanup "days=30"
```

配置项（`config/task.php`）：

```php
return [
    'cron' => [
        'handler'   => \App\Task\CronTask::class,  // 任务处理类（必须）
        'options'   => ['interval' => 60],          // 传递给 run() 的选项
        'daemonize' => false,                       // 是否守护进程
        'worker'    => ['count' => 1],              // Worker 进程数
        'pidFile'   => 'task-cron.pid',             // PID 文件（runtime/ 下）
        'logFile'   => 'task-cron.log',             // 日志文件
    ],
];
```

任务类需实现 `run` 静态方法：

```php
namespace App\Task;

use LarkFrame\Worker;

class CronTask
{
    public static function run(array $options, array $args): void
    {
        $interval = $options['interval'] ?? 60;

        Worker::log("[CronTask] Starting, interval: {$interval}s");

        // 注册定时器
        Worker::$globalEvent->repeat($interval, function () {
            // 定时执行的逻辑
            Worker::log("[CronTask] tick");
        });
    }
}
```

- `$options`：来自配置的 `options` 键
- `$args`：来自命令行参数（`parse_str` 解析后的关联数组）
- 使用 `Worker::log()` 输出日志（同时写入终端和日志文件），不要使用 `echo`

运行时文件在 `runtime/` 目录下：`task-{name}.pid`、`task-{name}.log`、`task-{name}.stdout.log`

### Web 模式（PHP-FPM）

传统 PHP-FPM 部署，每次请求独立进程。**站点根目录必须指向 `public/`**，不要指向项目根目录。

```bash
# Nginx 配置 — root 指向 public 目录
server {
    root /var/www/myapp/public;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }
}
```

访问示例：`http://example.com/api`

Web 模式下：
- 请求源为 `LarkFrame\Request\WebSource`（从 `$_GET/$_POST/$_SERVER` 读取）
- 无需连接池（每次请求独立进程）
- 适合低流量或无法运行常驻进程的环境

## License

MIT
