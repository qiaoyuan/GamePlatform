<?php

namespace app\common\command;
use app\common\model\Admin;
use app\common\model\AdminPermission;
use think\console\input\Option;
use think\facade\Db;
use util\Redis;
ini_set('memory_limit', '3000M');
class Tool extends Base
{

    protected function configure()
    {
        $this->setConfigure('tool');
        $this->addOption('reset', 'r', Option::VALUE_NONE, '重置');
    }

    public function redis($param)
    {
        $param = explode('__', $param);
        $fun = $param[0];
        $param = array_slice($param, 1);
        $redis = redisCache(Redis::DB_CHROME_EXT);
        if (!$param) {
            var_dump($redis->$fun());
        } else {
            var_dump($redis->$fun(...$param));
        }
    }

    public function adminLog()
    {
        Db::execute('UPDATE admin_log a, admin_permission p SET a.title = p.title WHERE a.api = p.url AND a.title = ');
    }

    public function log()
    {
        trace('init', 'init');
        $this->cleanLog($this->app->getRuntimePath());
    }

    private function cleanLog(string $dir)
    {
        $files = scandir($dir);
        $count = 0;
        foreach ($files as $file) {
            if (!in_array($file, ['.', '..'])) {
                $count++;
                $path = $dir . $file;
                if(is_dir($path)) {
                    $this->cleanLog($path . '/');
                } else {
                    $time = filemtime($path);
                    if ($time < time() - 86400 * 30) {
                        unlink($path);
                        $this->mLog('清除日志：' . $path);
                    }
                }
            }
        }
        if (!$count) {
            rmdir($dir);
            $this->mLog('删除目录：' . $dir);
        }
    }

    public function test()
    {
        $r = AdminPermission::getPermissionByAdmin(5);
        var_dump(AdminPermission::getAllPermissions(), $r);
    }
}
