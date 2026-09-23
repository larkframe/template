<?php

namespace App\Middleware;

use LarkFrame\Cache\Redis;
use LarkFrame\MiddlewareInterface;
use LarkFrame\Request;
use LarkFrame\Response;
use Throwable;

/**
 * 固定窗口限流中间件（按客户端 IP + 请求路径分桶）
 *
 * 用途：保护可被匿名高频调用的写接口（如队列投递、短信/邮件触发），
 * 防止被刷爆下游资源（Redis 内存、队列积压、第三方配额）。
 *
 * 设计取舍：
 *   - 用 Redis INCR 计数：单条命令原子完成「读 + 自增」，不存在读改写竞态
 *     （文件缓存需要 get 后再 set，高并发下会漏计）
 *   - Redis 不可用时放行：限流是保护性措施，不应成为业务可用性的单点故障
 *   - 固定窗口而非滑动窗口：INCR + EXPIRE 天然适配；代价是窗口边界处
 *     最多可放过 2 倍配额，对本场景足够
 *
 * 用法（路由中间件）：
 *   Route::get('/api/queue', [...])->middleware(ThrottleMiddleware::class);
 *
 * 配额通过构造函数默认值调整（当前 10 次 / 60 秒）；需要不同配额时
 * 复制本类或在容器中绑定带参实例。
 */
class ThrottleMiddleware implements MiddlewareInterface
{
    public function __construct(
        protected int $maxAttempts = 10,
        protected int $windowSeconds = 60,
    ) {
    }

    public function process(Request $request, callable $handler): Response
    {
        // 分桶键含路径：不同接口互不影响，同一接口按来源隔离
        $bucket = 'throttle:' . $request->path() . ':' . $request->getRemoteIp();

        try {
            $count = (int)Redis::incr($bucket);
            if ($count === 1) {
                // 仅在首次计数时设置过期，避免每次请求都刷新窗口导致永不释放
                Redis::expire($bucket, $this->windowSeconds);
            }
        } catch (Throwable) {
            // Redis 故障时放行，避免限流组件本身成为可用性瓶颈
            return $handler($request);
        }

        if ($count > $this->maxAttempts) {
            return json(['code' => 429, 'message' => 'Too Many Requests'])
                ->withStatus(429)
                ->withHeader('Retry-After', (string)$this->windowSeconds);
        }

        return $handler($request);
    }
}