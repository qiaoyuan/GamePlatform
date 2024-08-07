<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2016 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: 刘志淳 <chun@engineer.com>
// +----------------------------------------------------------------------

namespace app\common\command;

use think\console\Command;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\facade\Db;

class Base extends Command
{
    use Ot;

    protected string $threadFile = '';

    protected string $logKey = 'command';

    const COLOR_SUCCESS = 2;
    const COLOR_WARNING = 3;
    const COLOR_INFO = 7;
    const COLOR_ERROR = 1;
    const COLOR_UPDATE = 6;

    protected function setConfigure(string $cmd, string $desc = '')
    {
        $this->setName($cmd)
            ->addArgument('action', Argument::OPTIONAL, 'Action', '')
            ->addArgument('param', Argument::OPTIONAL, 'Param', '')
            ->setDescription($desc ?: $cmd);
    }

    protected function threadRun($fun)
    {
        $content = $this->threadFile && file_exists($this->threadFile) ? file_get_contents($this->threadFile) : '';
        if ($content) {
            $this->output->writeln('重复执行');
            return;
        }
        if ($this->threadFile && !file_exists(dirname($this->threadFile))) {
            mkdir(dirname($this->threadFile), 0755, true);
        }
        $this->threadFile && file_put_contents($this->threadFile, 1);
        $start = microtime(true);
        try {
            $fun();
        } catch (\Throwable $e) {
            $this->mLog($e->getMessage());
            $this->mLog($e->getTraceAsString());
        }
        $this->mLog(
            sprintf('耗时:%s，峰值内存：%s',
                round(microtime(true) - $start, 3),
                round(memory_get_peak_usage() / 1024 / 1024, 2) . 'M')
        );
        $this->threadFile && file_put_contents($this->threadFile, '');
    }

    protected function execute(Input $input, Output $output)
    {
        $action = trim($input->getArgument('action'));
        $param = trim($input->getArgument('param') ?: '');
        $action = $action ?: 'index';
        $a = microtime(true);
        if (method_exists($this, $action)) {
            try {
                if ($this->$action($param)) {
                    $output->writeln('Success');
                } else {
                    $output->writeln('');
                    $output->writeln('Fail');
                    $output->writeln('');
                }
            } catch (\Exception $e) {
                $this->output->writeln($e);
                trace($e->getMessage());
                trace($e->getTraceAsString());
            }
        } else {
            $output->writeln('');
            $output->writeln('Invalid Command. See help activity');
            $output->writeln('');
            $output->writeln($this->getHelp());
        }
        $b = microtime(true);
        $output->writeln('  End:' . $b);
        $output->writeln('  Use:' . ($b - $a));
    }

    protected function loop(\app\common\model\Base $model, int $step, \Closure $fn, array $where = [], $context = null)
    {
        $pk = $model->getPk();
        $context && $fn->bindTo($context);
        $min = $model->min($pk);
        $max = $model->max($pk);
        $loop = ceil(($max - $min) / $step);
        for ($i = 0; $i < $loop; $i++) {
            $start = $min + $i * $step;
            $end = $min + $i * $step + $step - 1;
            $list = $model->where($pk, 'BETWEEN', [$start, $end])->where($where)->select();
            foreach ($list as $item) {
                $fn($item);
                unset($item);
            }
            unset($list);
            $this->output->writeln('DONE ' . $start . '/' . $max . ' ' . microtime(true));
        }
    }

    protected function loopDb($table, $pk, $step, \Closure $fn, $where = [], $context = null, $connect = null)
    {
        $context && $fn->bindTo($context);
        $min = Db::connect($connect)->table($table)->min($pk);
        $max = Db::connect($connect)->table($table)->max($pk);
        $loop = ceil(($max - $min) / $step);
        for ($i = 0; $i < $loop; $i++) {
            $start = $min + $i * $step;
            $end = $min + $i * $step + $step - 1;
            $list = Db::connect($connect)->table($table)->where($pk, 'BETWEEN', [$start, $end])->where($where)->select();
            foreach ($list as $item) {
                $fn($item);
                unset($item);
            }
            unset($list);
            $this->output->writeln('DONE ' . $table . ' ' . $start . '/' . $max . ' ' . microtime(true));
        }
    }

    protected function chunk(\app\common\model\Base $model, int $step, \Closure $fn, array $where = [])
    {
        $pk = $model->getPk();
        $last = $model->min($pk) - 1;
        $max = $model->max($pk);
        while (true) {
            $list = $model->where($where)->where($pk, '>', $last)->limit($step)->order($pk, 'ASC')->select();
            if ($list->isEmpty()) {
                break;
            }
            foreach ($list as $item) {
                if ($fn($item) === false) {
                    break 2;
                }
                $last = max($last, $item[$pk]);
                unset($item);
            }
            unset($list);
            $this->output->writeln('DONE ' . $last . '/' . $max . ' ' . microtime(true));
        }
    }

    public function mLog(string $msg, string $key = '')
    {
        $this->output->writeln(sprintf('[%s]%s', date('Y-m-d H:i:s'), $msg));
        trace($msg, $key ?: $this->logKey);
    }
}
