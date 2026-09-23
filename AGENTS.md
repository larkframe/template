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

## 框架文档索引（AI 必读）

框架 `larkframe/core` 的完整文档随包分发，**不在本模板目录内**。按以下顺序定位：

1. **本文件**（AGENTS.md）— 应用层规范与 API 速查，优先遵循
2. **框架专题文档** — 各组件的深入文档（含行为约定、边界、陷阱）：

| 主题 | 文件（相对项目根目录） |
|------|------|
| 应用入口/四模式 | `vendor/larkframe/core/docs/app.md` |
| 路由 | `vendor/larkframe/core/docs/route.md` |
| 请求 | `vendor/larkframe/core/docs/request.md` |
| 响应 | `vendor/larkframe/core/docs/response.md` |
| 中间件 | `vendor/larkframe/core/docs/middleware.md` |
| 容器（DI） | `vendor/larkframe/core/docs/container.md` |
| 配置 | `vendor/larkframe/core/docs/config.md` |
| 数据库 | `vendor/larkframe/core/docs/database.md` |
| 缓存 | `vendor/larkframe/core/docs/cache.md` |
| Redis | `vendor/larkframe/core/docs/redis.md` |
| 队列 | `vendor/larkframe/core/docs/queue.md` |
| 日志 | `vendor/larkframe/core/docs/log.md` |
| 视图 | `vendor/larkframe/core/docs/view.md` |
| 任务（Task） | `vendor/larkframe/core/docs/task.md` |
| 协程/连接池 | `vendor/larkframe/core/docs/coroutine.md` |
| 工具集 | `vendor/larkframe/core/docs/util.md` |

> **路径说明**：composer 安装态如上；若 vendor 中不存在该路径（本地 path 仓库
> symlink 开发态），framework 源码与文档位于仓库同级 `../core/`，即
> `../core/docs/*.md` 与 `../core/src/`。文档与代码同版本发布，行为冲突时以
> `core/src/` 源码为准并向用户报告文档过期。

3. **框架源码** — `vendor/larkframe/core/src/`（最终事实来源；`LarkFrame\` 命名空间 PSR-4 映射到 `src/`）

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

Queue::push('queue', JobClass::class, $data);           // 推送（仅任务类名，闭包不可序列化）
Queue::later('queue', 60, JobClass::class, $data);      // 延迟推送
Queue::pop();                                            // 弹出（省略队列名时用 config queue.default）
Queue::size();                                           // 大小
Queue::clear('queue');                                   // 清空
Queue::getFailedJobs('queue');                           // 失败任务
Queue::retryFailed('queue', 0);                          // 重试失败（Lua 原子完成）
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

Util::str()->mask('13800138000');              // 脱敏（138****8000）
Util::str()->camelToUnderscore('userName');    // 驼峰转下划线
Util::str()->formatBytes(1048576);             // 字节格式化（默认 IEC → 1 MiB）
Util::rand()->uuid();                          // UUID v4
Util::rand()->str(16);                         // 随机字符串（CSPRNG）
Util::base64()->urlEncode($data);              // URL 安全 Base64
Util::base64()->authcode($str, 'ENCODE', $key); // 可逆混淆编码（$key 必填，敏感数据禁用）
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
6. **错误处理**：`config/config.php` 的 `error.catch` 控制所有模式（Server/Task/Web/Shell）是否注册自定义错误处理器（`error.handler` + `error.options`）；Server/Task 模式 throwOnError=true 将错误转 ErrorException 由 onMessage try-catch 记录，Web/Shell 模式直接记录日志并抑制
7. **环境变量**：`.env` 仅用于 `APP_NAME`/`TIME_ZONE`/`RUN_MODE` 三个启动参数（框架自动创建），敏感配置应通过 `config/config.{env}.php` 环境覆盖文件管理，不要硬编码
8. **类库配置**：`app/Library/Xxx.php` 对应 `config/xxx.php`，继承 `LarkFrame\Library` 后通过 `$this->config` 访问
9. **CORS 白名单**：`CorsMiddleware` 仅回显 `config('cors.allow_origins')` 白名单中的 Origin（配置在 `config/config.php` 的 `cors` 键），不要无条件反射请求 Origin
10. **authcode 密钥**：`Util::base64()->authcode()` 必须显式传入 `$key`（推荐从配置读取），空密钥会抛 `InvalidArgumentException`
11. **队列默认名**：`Queue::pop()/size()/clear()` 等不传队列名时使用 `config('queue.default')`，门面不再硬编码 `'default'`
12. **中间件 fail-fast**：全局/路由/控制器/注解中间件的类不存在或缺少 `process()` 时抛 `RuntimeException`（不静默跳过）；404/405/400 兜底响应也穿全局中间件管道，Web（FPM）模式中间件行为与 Server 模式一致
13. **环境覆盖浅合并**：`config.{env}.php` 的顶层键（如 `server`）整体替换主配置对应键，覆盖时必须携带完整子结构（如覆盖 socketName 需带上 middleware），否则子键会被清空
14. **配置内路径用闭包**：log/cache 配置中的 `runtime_path()` 必须写成 `fn() => runtime_path(...)` 延迟求值，直接调用因配置加载时序只会取默认目录
15. **静态文件**：`public/` 下文件自动支持 Range 断点续传（206+Content-Range）、304 协商缓存与 `Cache-Control: public, max-age=86400`；PHP 请求禁用静态缓存（每次重验）
16. **视图模板**：模板名相对 `template/` 目录解析，含 `../` 的穿越请求被拒绝抛异常；模板缺失两引擎均抛 `RuntimeException`（不返回 200 错误文案）
17. **Shell 模式中间件**：Shell 请求同样走完整中间件管道与 `getActionSuffix()` 后缀补全，与 Server 模式行为一致；Shell 下 404 输出纯文本
18. **Twig extension 配置**：`view.extension` 必须为 callable（接收 `Twig\Environment`），仅在启用 Twig 引擎时配置
