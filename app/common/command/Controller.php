<?php

declare (strict_types = 1);

namespace app\common\command;

use think\console\Output;
use think\helper\Str;

class Controller extends MakeBase
{
    private array $numberField = [
        'decimal', 'float', 'double', 'int'
    ];

    protected $type = 'Controller';

    protected function configure()
    {
        parent::configure();
        $this->setName('controller')
            ->setDescription('Create a new controller class');
    }

    protected function getStub()
    {
        return __DIR__ . '/stubs/mController.stub';
    }

    protected function getNamespace(string $app): string
    {
        return parent::getNamespace($app ?: 'admin') . '\\controller';
    }

    protected function buildClass(string $name)
    {
        $stub = file_get_contents($this->getStub());
        $namespaceArr = explode('\\', $name);
        $module = $namespaceArr[count($namespaceArr) - 3];
        $class = $namespaceArr[count($namespaceArr) - 1];
        $tableName = Str::snake($class);
        $fieldsInfo = $this->app->db->query('show full columns from `' . $tableName . '`');
        return str_replace([
            '{%pk%}',
            '{%kField%}',
            '{%module%}',
            '{%className%}',
            '{%columns%}',
            '{%title%}',
            '{%traits%}',
            '{%use%}',
            '{%addRoute%}',
            '{%create%}'
        ], [
            $this->getPk($fieldsInfo),
            $this->getKFields($fieldsInfo),
            $module,
            $class,
            $this->getColumns(Str::camel($class), $fieldsInfo),
            $this->input->getOption('title'),
            $this->getTraits($fieldsInfo),
            $this->getUse($fieldsInfo),
            $this->input->getOption('route') ? ', isMenu: 1, isHide: 1' : '',
            $this->getCreate($fieldsInfo),
        ], $stub);
    }

    public function mMake(string $name, Output $output)
    {
        $classname = $this->getClassName($name);
        $pathname = $this->getPathName($classname);
        $str = $this->buildClass($classname);
        if($this->input->getOption('replace') && is_file($pathname)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' backup.');
            rename($pathname, str_replace('.php', '1.php', $pathname));
        }
        if (is_file($pathname)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' already exists.');
            $old = file_get_contents($pathname);
            preg_match(
                "/public function columns\(\)\: array/is",
                $old,
                $columnsStr
            );
            if (!$columnsStr) {
                preg_match(
                    "/(\s+public function columns.*?}\s+)/is",
                    $str,
                    $columnsStr
                );
                if ($columnsStr) {
                    $old = trim($old);
                    $old = trim(substr($old, 0, -1)) . $columnsStr[1] . '}' . PHP_EOL;
                    file_put_contents($pathname, $old);
                }
            }
            $this->outputUpdate($output, $name . ' ' . $this->type . ' updated successfully.');
        } else {

            if (!is_dir(dirname($pathname))) {
                mkdir(dirname($pathname), 0755, true);
            }

            file_put_contents($pathname, $str);

            $this->outputSuccess($output, $name . ' ' . $this->type . ' created successfully.');
        }
    }

    private function getPk(array $fieldsInfo) :string
    {
        foreach ($fieldsInfo as $key => $row) {
            if ($row['Key'] == 'PRI') {
                return $row['Field'];
            }
        }
        return '';
    }

    private function getKFields(array $fieldsInfo) :string
    {
        $fields = array_column(array_filter($fieldsInfo, function ($row) {
            return Str::contains(strtolower($row['Type']), ['char', 'text']);
        }), 'Field');
        return $fields ? ("'" . implode('\', \'', $fields) . "'") : '';
    }

    private function getColumns(string $module, array $fieldsInfo) :string
    {
        $typeStr = PHP_EOL;
        foreach ($fieldsInfo as $row) {
            if (in_array($row['Field'], ['deleted_at', 'updated_at'])) {
                continue;
            }
            if ($row['Field'] == 'created_at' && !$row['Comment']) {
                $row['Comment'] = '日期';
            }
            $searchType = $option = '';
            if ($row['Key'] === 'PRI') {
                $row['Comment'] = 'ID';
                $searchType = 'match';
            } else {
                if (Str::contains(strtolower($row['Type']), $this->numberField)) {
                    $searchType = 'number';
                }
                if (Str::endsWith($row['Field'], ['_at']) && stripos($row['Type'], 'date') !== false) {
                    $searchType = 'daterange';
                }
                if (Str::startsWith($row['Field'], ['is_']) && stripos($row['Type'], 'tinyint') !== false) {
                    $searchType = 'boolean';
                }
                if (Str::endsWith($row['Field'], ['_id'])) {
                    $searchType = 'multiple';
                    $option = sprintf("'/%s/select'", Str::camel(str_replace('_id', '', $row['Field'])));
                }
                if (Str::endsWith($row['Field'], ['_type'])) {
                    $searchType = 'multiple';
                    $option = sprintf("[ 'url' => '/%s/create', 'key' => '%s' ]", $module, Str::camel($row['Field']));
                }
                if ($row['Field'] == 'status') {
                    $searchType = 'status';
                }
                if(Str::contains($row['Comment'], 'upload')) {
                    $searchType = '';
                    $option = 'image';
                }
            }
            $typeStr .= $this->getColumnItem($row['Field'], $row['Comment'], $searchType, $option);
        }
        return $typeStr . $this->tab(2);
    }

    private function getColumnItem(string $filed, string $comment, string $searchType, string $option): string
    {
        if (Str::contains($comment, ['（', '('])) {
            $comment = mb_substr(mb_substr($comment, 0, mb_strpos($comment, '(') ?: null ), 0, mb_strpos($comment, '（') ?: null);
        }
        if ($searchType == 'multiple') {
            return sprintf(
                "%s[%s%s%s],%s",
                $this->tab(3),
                PHP_EOL . $this->tab(4),
                implode(',' . PHP_EOL . $this->tab(4), [
                    sprintf("'v' => '%s'", $filed),
                    sprintf("'label' => '%s'", $comment),
                    sprintf("'sort' => '%s'", $filed),
                    sprintf("'searchType' => '%s'", $searchType),
                    sprintf("'searchList' => %s", $option),
                    "'replace' => true,",
                ]),
                PHP_EOL . $this->tab(3),
                PHP_EOL
            );
        }
        if ($searchType == 'boolean' || $searchType == 'status') {
            return sprintf(
                "%s['v' => '%s', 'label' => '%s', 'render' => '%s', 'sort' => '%s'],%s",
                $this->tab(3),
                $filed,
                $comment,
                $searchType,
                $filed,
                PHP_EOL
            );
        }
        if ($searchType) {
            return sprintf(
                "%s['v' => '%s', 'label' => '%s', 'searchType' => '%s', 'sort' => '%s'],%s",
                $this->tab(3),
                $filed,
                $comment,
                $searchType,
                $filed,
                PHP_EOL
            );
        }
        if ($option == 'image') {
            return sprintf(
                "%s['v' => '%s', 'label' => '%s', 'search' => false, 'render' => 'image'],%s",
                $this->tab(3),
                $filed,
                $comment,
                PHP_EOL
            );
        }
        return sprintf(
            "%s['v' => '%s', 'label' => '%s'],%s",
            $this->tab(3),
            $filed,
            $comment,
            PHP_EOL
        );
    }

    protected function getTraits(array $fieldsInfo): string
    {
        if (in_array('user_id', array_column($fieldsInfo, 'Field'))) {
            //return 'use UserColumn;';
        }
        return '';
    }

    protected function getUse(array $fieldsInfo): string
    {
        $use = [];
        return implode(PHP_EOL, $use) . PHP_EOL;
    }

    protected function getCreate(array $fieldsInfo)
    {
        $create = [];
        foreach ($fieldsInfo as $row) {
            if (Str::endsWith($row['Field'], ['_type'])) {
                $create[] = sprintf("'%s' => [],", Str::camel($row['Field']));
            }
        }
        if ($create) {
            return str_replace(
                ['{%create%}'],
                [implode($this->tab(3) . PHP_EOL, $create)],
                file_get_contents(__DIR__ . '/stubs/mControllerCreate.stub')
            );
        }
        return '';
    }

    public function all(Output $output)
    {
        $tables = $this->app->db->getTables();
        foreach ($tables as $table) {
            $this->mMake($table, $output);
        }
    }
}
