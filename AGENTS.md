# AGENTS.md — AI 编程指南

本文档为 AI 编程助手提供项目上下文，确保生成的代码符合项目规范。

## 项目概述

LarkFrame 是一个高性能 PHP 框架，支持 Server（常驻内存）、Web（PHP-FPM）、Shell（命令行）和 Task（自定义任务）四种运行模式。
核心包命名空间：`LarkFrame\`，应用命名空间：`App\`。

### 运行模式

框架通过入口文件显式指定运行模式（`App::run(LarkFrame\Consts::RUN_TYPE_*)`）：

| 模式 | 启动方式 | 入口文件 |
|------|---------|---------|
| Server | `php server.php` | `server.php` |
| Shell | `php shell.php route "key=value"` | `shell.php` |
| Task | `php task.php taskname [args]` | `task.php` |
| Web | 浏览器访问 | `public/index.php` |

- **Server 模式**：`php server.php` 启动常驻内存 Worker 进程，监听 HTTP 请求，支持多进程、事件驱动、连接池
- **Shell 模式**：`php shell.php migrate` 执行单次命令行任务，路由匹配 `Route::shell()` 定义的路由，执行后退出（路由前缀 `/` 可省略）
- **Task 模式**：`php task.php taskname` 启动常驻内存 Worker 进程执行自定义任务（`App::runAsTask()`），taskname 对应 `config/task.php` 中定义的任务，支持多进程、守护进程。命令格式：`php task.php <taskname> [start|stop|restart|reload|status] [args]`，默认 action 为 `start`
- **Web 模式**：传统 PHP-FPM，每次请求独立进程，通过 Nginx + php-fpm 访问 `public/index.php`

## 技术栈

- PHP >= 8.1（使用类型声明、命名参数、match 表达式等现代语法）
- 框架核心：`larkframe/core`（命名空间 `LarkFrame\`）
- 数据库：Illuminate Database（Eloquent ORM + 查询构建器）
- 缓存：Symfony Cache（PSR-16）+ Illuminate Redis
- 队列：基于 Redis List/Sorted Set 的自研队列
- 模板：原生 PHP / Twig 双引擎
- 日志：Monolog

## 项目结构

```
app/
├── Base/              # 基类（Controller、Model、ErrorHandler）
├── Controller/        # 控制器（方法名以 Action 后缀结尾，如 indexAction）
├── Job/               # 队列任务（实现 handle(Job $job, mixed $data) 方法）
├── Library/           # 业务类库（继承 LarkFrame\Library，配置自动加载）
├── Middleware/        # 中间件（实现 LarkFrame\MiddlewareInterface）
├── Model/             # Eloquent 模型（继承 App\Base\Model）
└── Task/              # 常驻任务（实现 static run(array $options, array $args) 方法）
config/
├── config.php         # 主配置（server/database/redis/cache/queue/log/view/error）
├── {name}.php         # 类库配置（自动映射到 app/Library/{Name}.php 的 $this->config）
├── config.{env}.php   # 环境覆盖配置
├── route.php          # 路由定义
└── task.php           # 任务配置
public/
└── index.php          # Web/FPM 入口
template/              # 视图模板
server.php             # Server 模式入口
shell.php              # Shell 模式入口
task.php               # Task 模式入口
```

## 编码规范

### 命名空间

- 框架核心：`LarkFrame\`（如 `LarkFrame\Db`、`LarkFrame\Cache\Redis`）
- 应用代码：`App\`（如 `App\Controller\DefaultController`）

### 控制器

```php
namespace App\Controller;

use LarkFrame\Request;

class UserController
{
    // 方法名必须带 Action 后缀
    public function indexAction(Request $request)
    {
        return json(['code' => 0, 'data' => []]);
    }
}
```

### 路由

在 `config/route.php` 中定义：

```php
use LarkFrame\Route;

Route::get('/path', [Controller::class, 'methodAction']);
Route::post('/path', [Controller::class, 'methodAction']);
Route::any('/path', [Controller::class, 'methodAction']);
Route::shell('/path', [Controller::class, 'methodAction']);  // 命令行
```

### 中间件

```php
namespace App\Middleware;

use LarkFrame\MiddlewareInterface;
use LarkFrame\Request;
use LarkFrame\Response;

class ExampleMiddleware implements MiddlewareInterface
{
    public function process(Request $request, callable $handler): Response
    {
        // 前置逻辑
        $response = $handler($request);
        // 后置逻辑
        return $response;
    }
}
```

注册方式：
- 全局：`config/config.php` → `server.middleware`
- 路由：`Route::get(...)->middleware(XxxMiddleware::class)`
- 控制器属性：`protected array $middleware = [...]`

### 模型

```php
namespace App\Model;

use App\Base\Model;

class UserModel extends Model
{
    protected $table = 'users';
    protected $primaryKey = 'id';
    public $timestamps = false;
}
```

### 队列任务

```php
namespace App\Job;

use LarkFrame\Queue\Job;

class ProcessJob
{
    public function handle(Job $job, mixed $data): void
    {
        // 处理逻辑
        $job->ack();   // 成功
        // $job->fail($e);  // 失败
        // $job->release(30);  // 延迟重试
    }
}
```

推送任务：

```php
use LarkFrame\Queue;

Queue::push('queue_name', \App\Job\ProcessJob::class, $data);
Queue::later('queue_name', 60, \App\Job\ProcessJob::class, $data);  // 延迟 60 秒
```

### 类库（Library）

继承 `LarkFrame\Library`，配置文件自动加载：

```php
namespace App\Library;

use LarkFrame\Library;

class Payment extends Library
{
    public function pay(array $params)
    {
        $config = $this->config['gateway'];  // 来自 config/payment.php
    }
}
```

对应配置文件 `config/payment.php`：

```php
return [
    'gateway' => ['url' => 'https://...'],
];
```

## 框架核心 API 速查

### 请求与响应

```php
use LarkFrame\Request;

$request->method();                    // HTTP 方法
$request->path();                      // 请求路径
$request->input('key', $default);      // GET/POST 参数
$request->get('key', $default);        // GET 参数
$request->post('key', $default);       // POST 参数
$request->header('key', $default);     // 请求头
$request->route()->param('name');      // 路由参数
$request->getRemoteIp();               // 客户端 IP
$request->requestId();                 // 请求 ID

// 响应辅助函数
json($data, $options);                 // JSON 响应（$options 为 JSON 编码标志，状态码固定 200；需自定义状态码用 ->withStatus()）
redirect($url, $status);               // 重定向（默认 302）
response($body, $status);              // 通用响应（默认 200）
raw_view($template, $vars, $suffix);   // PHP 模板
twig_view($template, $vars, $suffix);  // Twig 模板
```

### 数据库

```php
use LarkFrame\Db;

Db::table('users')->where('id', 1)->first();
Db::table('users')->insert(['name' => 'John']);
Db::table('users')->where('id', 1)->update(['name' => 'Jane']);
Db::table('users')->where('id', 1)->delete();
Db::use('other_db')->table('orders')->get();  // 多库切换
Db::transaction(function () { ... });          // 事务
```

### 缓存

```php
use LarkFrame\Cache\Cache;

Cache::set('key', 'value', $ttl);
Cache::get('key', $default);
Cache::delete('key');
Cache::has('key');
Cache::store('redis')->get('key');  // 切换存储
```

### Redis

```php
use LarkFrame\Cache\Redis;

Redis::set('key', 'value');
Redis::get('key');
Redis::use('cache', 1)->set('key', 'value');  // 切换连接+数据库
Redis::rPush('list', 'value');
Redis::lPop('list');
Redis::hSet('hash', 'field', 'value');
Redis::hGetAll('hash');
```

### 队列

```php
use LarkFrame\Queue;

Queue::push('queue', JobClass::class, $data);           // 推送
Queue::later('queue', 60, JobClass::class, $data);      // 延迟推送
Queue::pop('queue');                                      // 弹出
Queue::size('queue');                                     // 大小
Queue::clear('queue');                                    // 清空
Queue::getFailedJobs('queue');                            // 失败任务
Queue::retryFailed('queue', 0);                           // 重试失败
```

### 任务（Task）

```php
use LarkFrame\Worker;

// 日志输出（同时写入终端和日志文件，不要用 echo）
Worker::log("[MyTask] message");

// 事件循环定时器
Worker::$globalEvent->repeat($interval, function () { ... });  // 周期执行
Worker::$globalEvent->delay($seconds, function () { ... });    // 延迟执行
Worker::$globalEvent->offRepeat($timerId);                     // 取消周期定时器
Worker::$globalEvent->offDelay($timerId);                      // 取消延迟定时器
```

### 工具集

```php
use LarkFrame\Util;

Util::str()->mask('13800138000');              // 脱敏
Util::str()->camelToUnderscore('userName');    // 驼峰转下划线
Util::str()->formatBytes(1048576);             // 字节格式化
Util::rand()->uuid();                          // UUID v4
Util::rand()->str(16);                         // 随机字符串
Util::base64()->urlEncode($data);              // URL 安全 Base64
Util::base64()->authcode($str, 'ENCODE', $key); // 可逆加密
Util::file()->ensureDir($dir);                 // 确保目录
Util::img()->resize($src, $dst, $w, $h);       // 缩放图片
Util::mock()->list($template, 10);             // 模拟数据
```

### 配置

```php
config('app.name');                  // 读取配置
config('database.connections.mysql'); // 嵌套配置
config('app.debug', false);          // 带默认值
```

### 日志

```php
use LarkFrame\Log;

Log::debug('message', ['context' => $data]);
Log::info('message');
Log::warning('message');
Log::error('message');
```

### 协程上下文

```php
use LarkFrame\Context;

Context::set('key', $value);
Context::get('key', $default);
Context::has('key');
Context::destroy();  // 请求结束时自动调用
```

## 注意事项

1. **Action 后缀**：控制器方法必须带 `Action` 后缀（如 `indexAction`），由 `config.route.action_suffix` 控制
2. **请求隔离**：Server 模式下使用 `Context` 实现请求隔离，不要用 `static`/`global` 存储请求级数据
3. **连接池**：Server 模式下数据库和 Redis 自动使用连接池，不要手动 `new PDO` 或 `new Redis`
4. **响应类型**：控制器必须返回 `Response` 对象或使用辅助函数（`json()`/`redirect()`/`raw_view()` 等）；`json()` 状态码固定 200，需自定义状态码链式调用 `->withStatus(401)`
5. **调试模式**：`config/config.php` 中 `app.debug` 默认 `false`（生产安全），开发时创建 `config/config.dev.php` 覆盖为 `true`；`RUN_MODE=dev` 时框架自动加载该覆盖文件
6. **错误处理**：`config/config.php` 的 `error.catch` 控制 Web/Shell 模式下是否注册自定义错误处理器（`error.handler` + `error.options`）；Server 模式在 `onWorkerStart` 内单独注册
7. **环境变量**：`.env` 仅用于 `APP_NAME`/`TIME_ZONE`/`RUN_MODE` 三个启动参数（框架自动创建），敏感配置应通过 `config/config.{env}.php` 环境覆盖文件管理，不要硬编码
8. **类库配置**：`app/Library/Xxx.php` 对应 `config/xxx.php`，继承 `LarkFrame\Library` 后通过 `$this->config` 访问
