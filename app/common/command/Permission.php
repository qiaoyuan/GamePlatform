<?php


namespace app\common\command;


use think\facade\App;
use think\console\Input;
use think\console\input\Argument;
use think\console\Output;
use think\helper\Str;

/**
 * Notes: 自动同步函数注释到权限表中
 */
class Permission extends Base
{

    protected function configure()
    {
        $this->setName('permission')
            ->addArgument('controller', Argument::REQUIRED, '控制器')
            ->setDescription('根据类注释生成权限')
            ->setHelp(
                <<<help
eg: permission system             // 遍历system 控制器下所有方法
    permission all                // 遍历所有控制器
help
            );
    }

    protected function execute(Input $input, Output $output): void
    {
        try {
            $name = trim($input->getArgument('controller'));

            if ($name == 'all') {
                $this->all();
            } else {
                //调用部分扫描写入
                $this->part($name);
            }
        } catch (\Exception $e) {
            $output->writeln($e->getMessage());
            $output->writeln($e->getTraceAsString());
            $output->writeln('Fail');
        }
    }

    protected function part(string $name): void
    {
        $classname = 'app\\admin\\controller\\' . Str::studly($name);
        if (!class_exists($classname)) {
            return;
        }
        $reflection = new \ReflectionClass($classname);
        $classAttributes = $reflection->getAttributes(\app\common\annotation\Permission::class);
        if ($classAttributes) {
            $p = $classAttributes[0]->newInstance();
            $permission = $p->save();
            if ($permission) {
                if ($p->isUpdate()) {
                    $this->outputUpdate($this->output, $classname . '更新权限' . json_encode($p->getData(), 320));
                } else {
                    $this->outputSuccess($this->output, $classname . '添加权限' . json_encode($p->getData(), 320));
                }
            } else {
                $this->outputWarning($this->output, $classname . '类注解无效，跳过');
            }
        }
        $methods = $reflection->getMethods(\ReflectionProperty::IS_PUBLIC);
        foreach ($methods as $method) {
            if ($method->getName() == 'index') {
                $indexAttributes = $method->getAttributes(\app\common\annotation\Permission::class);
                if ($indexAttributes) {
                    $p = $indexAttributes[0]->newInstance();
                    $p->setUrl(Str::camel($name) . '/' . $method->getName());
                    if (empty($p->getData()['parent_id'])) {
                        $this->outputError($this->output, $classname . 'index注解无效，没有父级权限，跳过');
                        continue;
                    }
                    $permission = $p->save();
                    if ($permission) {
                        if ($p->isUpdate()) {
                            $this->outputUpdate($this->output, $classname . '更新权限' . json_encode($p->getData(), 320));
                        } else {
                            $this->outputSuccess($this->output, $classname . '添加权限' . json_encode($p->getData(), 320));
                        }
                    } else {
                        $this->outputWarning($this->output, $classname . 'index注解无效');
                    }
                }
            }
        }
        foreach ($methods as $method) {
            if ($method->getName() == 'index') {
                continue;
            }
            $addAttributes = $method->getAttributes(\app\common\annotation\Permission::class);
            if ($addAttributes) {
                $p = $addAttributes[0]->newInstance();
                $p->setUrl(Str::camel($name) . '/' . $method->getName());
                if (!$p->parentUrl) {
                    $p->setParentUrl(Str::camel($name) . '/index');
                }
                if (empty($p->getData()['parent_id'])) {
                    $this->outputError($this->output, $classname . $method->getName() . '注解无效，没有父级权限，跳过');
                    continue;
                }
                $permission = $p->save();
                if ($permission) {
                    if ($p->isUpdate()) {
                        $this->outputUpdate($this->output, $classname . '更新权限' . json_encode($p->getData(), 320));
                    } else {
                        $this->outputSuccess($this->output, $classname . '添加权限' . json_encode($p->getData(), 320));
                    }
                } else {
                    $this->outputWarning($this->output, $classname . $method->getName() . '注解无效');
                }
            }
        }
    }

    protected function all()
    {
        $path = App::getAppPath() . 'admin/controller';
        $files = scandir($path);
        foreach ($files as $file) {
            if ($file != '.' && $file != '..') {
                $controller = str_replace('.php', '', $file);
                $this->part($controller);
            }
        }
    }
}