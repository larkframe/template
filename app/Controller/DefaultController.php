<?php

namespace App\Controller;

use App\Base\Controller;
use LarkFrame\Request;
use LarkFrame\Db;
use LarkFrame\Cache\Cache;
use LarkFrame\Queue;
use LarkFrame\Util;

class DefaultController extends Controller
{
    /**
     * 首页（原生 PHP 模板）
     */
    public function indexAction(Request $request)
    {
        return raw_view('default/index', ['username' => 'world'], 'php');
    }

    /**
     * Twig 模板示例（双引擎并存：html 后缀走 Twig 渲染）
     */
    public function twigAction(Request $request)
    {
        return twig_view('user/test', [
            'name' => $request->input('name', 'larkframe'),
            'email' => $request->input('email', ''),
        ], 'html');
    }

    /**
     * JSON API 示例
     */
    public function apiAction(Request $request)
    {
        return json([
            'code' => 0,
            'message' => 'ok',
            'data' => [
                'request_id' => $request->requestId(),
                'method' => $request->method(),
                'path' => $request->path(),
                'ip' => $request->getRemoteIp(),
            ],
        ]);
    }

    /**
     * 数据库查询示例
     */
    public function dbAction(Request $request)
    {
        $id = $request->input('id', 1);

        // 使用查询构建器
        $user = Db::table('users')->where('id', $id)->first();

        // 使用 Eloquent Model
        // $user = \App\Model\UserModel::find($id);

        return json(['code' => 0, 'data' => $user]);
    }

    /**
     * 缓存示例
     */
    public function cacheAction(Request $request)
    {
        $key = 'demo.cache.' . date('Ymd');

        $value = Cache::get($key);
        if ($value === null) {
            $value = ['time' => date('Y-m-d H:i:s'), 'hits' => 1];
            Cache::set($key, $value, 3600);
        } else {
            $value['hits']++;
            Cache::set($key, $value, 3600);
        }

        return json(['code' => 0, 'data' => $value]);
    }

    /**
     * 队列推送示例
     */
    public function queueAction(Request $request)
    {
        $jobId = Queue::push('emails', \App\Job\SendEmailJob::class, [
            'to' => $request->input('to', 'user@example.com'),
            'subject' => 'Welcome',
        ]);

        return json(['code' => 0, 'message' => 'Job pushed', 'job_id' => $jobId]);
    }

    /**
     * 工具集示例
     */
    public function utilAction(Request $request)
    {
        return json([
            'code' => 0,
            'data' => [
                'uuid' => Util::rand()->uuid(),
                'mask_phone' => Util::str()->mask('13800138000'),
                'base64' => Util::base64()->urlEncode('hello world'),
                'format_bytes' => Util::str()->formatBytes(1048576),
            ],
        ]);
    }
}