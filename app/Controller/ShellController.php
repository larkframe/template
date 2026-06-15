<?php

namespace App\Controller;

use LarkFrame\Request;
use LarkFrame\Db;
use LarkFrame\Cache\Cache;

/**
 * Shell 控制器
 *
 * 命令行任务，通过 php shell.php <route> 执行
 */
class ShellController
{
    /**
     * 数据库迁移示例
     *
     * php shell.php migrate
     */
    public function migrateAction(Request $request)
    {
        echo "[Migrate] Running migrations...\n";

        // TODO: 实现数据库迁移逻辑
        // 例如：读取 migration 文件，执行 DDL 语句

        echo "[Migrate] Done.\n";
        return 0;
    }

    /**
     * 清理缓存
     *
     * php shell.php cache/clear
     */
    public function cacheClearAction(Request $request)
    {
        $key = $request->input('key', '');

        if ($key) {
            Cache::delete($key);
            echo "[CacheClear] Deleted: {$key}\n";
        } else {
            Cache::clear();
            echo "[CacheClear] All cache cleared\n";
        }

        return 0;
    }
}
