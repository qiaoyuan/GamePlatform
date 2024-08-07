<?php
declare (strict_types = 1);

namespace app\common\command;

use think\console\Output;
use think\facade\App;
use think\helper\Str;

class Validate extends MakeBase
{
    protected $type = 'Validate';

    private array $notRequiredField = [
        'sort', 'desc', 'content', 'description', 'parent_id', 'tips'
    ];

    protected function configure()
    {
        parent::configure();
        $this->setName('validate')
            ->setDescription('Create a new validate class');
    }

    protected function getStub()
    {
        return __DIR__ . '/stubs/mValidate.stub';
    }

    protected function getNamespace(string $app): string
    {
        return parent::getNamespace($app ?: 'common') . '\\validate';
    }

    protected function buildClass(string $name)
    {
        $stub = file_get_contents($this->getStub());
        $namespaceArr = explode('\\', $name);
        $module = $namespaceArr[count($namespaceArr) - 3];
        $class = $namespaceArr[count($namespaceArr) - 1];
        $tableName = Str::snake($class);
        $fieldsInfo = $this->app->db->query('show full columns from `' . $tableName . '`');

        return str_replace(['{%className%}', '{%module%}', '{%columns%}', '{%addField%}', '{%editField%}'], [
            Str::studly($class),
            $module,
            $this->getColumns($fieldsInfo),
            $this->getFields($fieldsInfo, false),
            $this->getFields($fieldsInfo, true),
        ], $stub);
    }

    public function mMake(string $name, Output $output)
    {
        $classname = $this->getClassName($name);

        $pathname = $this->getPathName($classname);
        if($this->input->getOption('replace') && is_file($pathname)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' backup.');
            rename($pathname, str_replace('.php', '1.php', $pathname));
        }
        if (is_file($pathname)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' already exists.');
            return false;
        }

        if (!is_dir(dirname($pathname))) {
            mkdir(dirname($pathname), 0755, true);
        }

        file_put_contents($pathname, $this->buildClass($classname));

        $this->outputSuccess($output, $name . ' ' . $this->type . ' created successfully.');
    }

    public function all(Output $output)
    {
        $tables = $this->app->db->getTables();
        foreach ($tables as $table) {
            $this->mMake($table, $output);
        }
    }

    protected function getColumns(array $fields): string
    {
        $r = [];
        foreach ($fields as $row) {
            if (in_array($row['Field'], ['deleted_at', 'updated_at', 'created_at']) || !$row['Comment']) {
                continue;
            }
            if (Str::contains($row['Comment'], ['（', '('])) {
                $row['Comment'] = mb_substr(
                    mb_substr($row['Comment'], 0, mb_strpos($row['Comment'], '(') ?: null),
                    0,
                    mb_strpos($row['Comment'], '（') ?: null
                );
            }
            $r[] = sprintf(
                '\'%s|%s\' => [%s],%s',
                $row['Field'],
                $row['Comment'],
                in_array($row['Field'], $this->notRequiredField) ? '' : "'require'",
                PHP_EOL . $this->tab(2)
            );
        }
        return trim(implode('', $r));
    }

    protected function getFields(array $fields, bool $edit = false): string
    {
        $r = [];
        $pk = '';
        foreach ($fields as $row) {
            if (!$pk && $row['Key'] === 'PRI') {
                $pk = $row['Field'];
            }
            if (in_array($row['Field'], ['deleted_at', 'updated_at', 'created_at']) || !$row['Comment']) {
                continue;
            }
            $r[] = '\'' . $row['Field'] . '\'';
        }
        if ($edit && $pk) {
            $r[] = '\'' . $pk . '\'';
        }
        $str = implode(', ', $r);
        if (strlen($str) > 100) {
            return PHP_EOL . $this->tab(3) . implode(',' . PHP_EOL . $this->tab(3), $r) . PHP_EOL . $this->tab(2);
        }
        return $str;
    }
}
