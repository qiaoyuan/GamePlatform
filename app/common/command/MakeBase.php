<?php
declare (strict_types = 1);

namespace app\common\command;

use think\console\command\Make;
use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\helper\Str;

abstract class MakeBase extends Make
{
    use Ot;

    protected function configure()
    {
        parent::configure();
        $this->addOption('full', 'f', Option::VALUE_NONE, '模型，验证器，控制器，视图一起建立');
        $this->addOption('route', 'r', Option::VALUE_NONE, '独立编辑页面');
        $this->addOption('title', null, Option::VALUE_REQUIRED, '路由名称', 'TODO: 名称');
        $this->addOption('replace', null, Option::VALUE_NONE, '替换旧文件');
    }

    protected function getClassName(string $name): string
    {
        if (strpos($name, '\\') !== false) {
            return $name;
        }

        if (strpos($name, '@')) {
            [$app, $name] = explode('@', $name);
        } else {
            $app = '';
        }

        if (strpos($name, '/') !== false) {
            $name = str_replace('/', '\\', $name);
        }

        return $this->getNamespace($app) . '\\' . Str::studly($name);
    }

    protected function execute(Input $input, Output $output)
    {
        $name = trim($input->getArgument('name'));
        if ($input->getOption('full')) {
            if ($name == 'all') {
                $this->outputError($output, '一起建立不支持all!');
                return;
            }
            $classes = [Model::class, Validate::class, Controller::class, View::class];
            foreach ($classes as $class) {
                $c = new $class;
                $c->setConsole($this->getConsole());
                $c->setApp($this->getApp());
                $c->input = $input;
                $c->output = $output;
                $c->mMake($name, $output);
            }
        } else {
            if ($name === 'all') {
                $this->all($output);
            } else {
                $this->mMake($name, $output);
            }
        }

    }

    protected function tab(int $size, string $end = '')
    {
        return str_repeat('    ', $size) . $end;
    }
}
