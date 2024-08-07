<?php

declare (strict_types = 1);

namespace app\common\command;

use think\console\Output;
use think\helper\Str;

class Model extends MakeBase
{
    private array $floatFieldType = [
        'decimal', 'float', 'double'
    ];

    protected $type = 'Model';

    protected function configure()
    {
        parent::configure();
        $this->setName('model')
            ->setDescription('Create a new model class');
    }

    protected function getStub()
    {
        return __DIR__ . '/stubs/mModel.stub';
    }

    protected function getNamespace(string $app): string
    {
        return parent::getNamespace($app ?: 'common') . '\\model';
    }

    protected function buildClass(string $name)
    {
        $stub = file_get_contents($this->getStub());
        $namespaceArr = explode('\\', $name);
        $namespace = trim(implode('\\', array_slice($namespaceArr, 0, -1)), '\\');
        $class = str_replace($namespace . '\\', '', $name);
        $tableName = Str::snake($class);
        $fieldsInfo = $this->app->db->query('show full columns from `' . $tableName . '`');
        $pk = '';
        $timeFields = $jsonFields = $typeFields = [];
        foreach ($fieldsInfo as $key => $row) {
            $isTime = false;
            if (in_array($row['Field'], ['created_at', 'updated_at'])) {
                $timeFields[] = $row['Field'];
                $isTime = true;
            }
            if (Str::endsWith($row['Field'], ['_at']) && stripos($row['Type'], 'int ') !== false && !$isTime) {
                $typeFields[$row['Field']] = 'timestamp';
            }
            if (!$pk && $row['Key'] === 'PRI') {
                $pk = $row['Field'];
            }
            if (stripos($row['Type'], 'json') !== false) {
                $jsonFields[] = $row['Field'];
            } else {
                foreach ($this->floatFieldType as $ff) {
                    if (stripos($row['Type'], $ff) !== false) {
                        $typeFields[$row['Field']] = 'float';
                        break;
                    }
                }
            }
        }
        return str_replace([
            '{%className%}',
            '{%namespace%}',
            '{%autoTime%}',
            '{%tableName%}',
            '{%field%}',
            '{%pk%}',
            '{%type%}',
            '{%comment%}',
            '{%json%}',
            '{%traits%}'
        ], [
            $class,
            $namespace,
            $this->getAutoTimeStr($timeFields),
            $tableName,
            $this->getFieldsStr($fieldsInfo),
            $pk,
            $this->getTypeStr($typeFields),
            $this->getCommentStr($fieldsInfo),
            $this->getJsonStr($jsonFields),
            $this->getTraits($fieldsInfo),
        ], $stub);
    }

    public function mMake(string $name, Output $output)
    {
        $classname = $this->getClassName($name);

        $pathname = $this->getPathName($classname);

        $modelStr = $this->buildClass($classname);

        if($this->input->getOption('replace') && is_file($pathname)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' backup.');
            rename($pathname, str_replace('.php', '1.php', $pathname));
        }

        if (is_file($pathname)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' already exists.');
            $old = file_get_contents($pathname);
            preg_match(
                "/(\/\*\*\s+\*\s+@property.*@property\s+(int|string|array|float)\s+.*?\n)/is",
                $modelStr,
                $propertyStr
            );
            if ($propertyStr) {
                $old = preg_replace("/(\/\*\*\s+\*\s+@property.*@property\s+(int|string|array|float)\s+.*?\n)/is", $propertyStr[1], $old);
            }
            preg_match('/(protected\s+\$field\s+=\s+\[.*?\];)/is', $modelStr, $fieldsStr);
            if ($fieldsStr) {
                $old = preg_replace('/(protected\s+\$field\s+=\s+\[.*?\];)/is', $fieldsStr[1], $old);
            }
            file_put_contents($pathname, $old);
            $this->outputUpdate($output, $name . ' ' . $this->type . ' updated successfully.');
        } else {
            if (!is_dir(dirname($pathname))) {
                mkdir(dirname($pathname), 0755, true);
            }

            file_put_contents($pathname, $modelStr);

            $this->outputSuccess($output, $name . ' ' . $this->type . ' created successfully.');
        }
    }

    private function getTypeStr(array $typeFields) :string
    {
        $typeStr = '[';
        foreach ($typeFields as $f => $t) {
            $typeStr .= PHP_EOL . $this->tab(2, '\'') . $f . '\' => \'' . $t . '\',';
        }
        $typeStr .= $typeFields ? (PHP_EOL . $this->tab(1, ']')) : ']';
        return $typeStr;
    }
    
    private function getJsonStr(array $jsonFields) :string
    {
        $jsonStr = '';
        if ($jsonFields) {
            $jsonStr= 'protected $json = [\''. implode('\',\'', $jsonFields) .'\'];';
        }
        return $jsonStr ? (PHP_EOL . $this->tab(1) . $jsonStr) : '';
    }
    
    private function getAutoTimeStr(array $timeFields) :string
    {
        $autoTimeStr = '';
        if (count($timeFields) == 1) {
            if ($timeFields[0] == 'created_at') {
                $autoTimeStr = 'protected $updateTime = false;';
            } else {
                $autoTimeStr = 'protected $createTime = false;';
            }
        } elseif (empty($timeFields)) {
            $autoTimeStr = 'protected $autoWriteTimestamp = false;';
        }
        return $autoTimeStr ? (PHP_EOL . $this->tab(1) . $autoTimeStr) : '';
    }
    
    private function getCommentStr(array $fields) :string
    {
        $commentFields = [];
        foreach ($fields as $field) {
            if (stripos($field['Type'], 'int') !== false && !Str::endsWith($field['Field'], ['_at'])) {
                $fieldStr = 'int $' . $field['Field'];
            } elseif (stripos($field['Type'], 'json') !== false) {
                $fieldStr = 'array $' . $field['Field'];
            } else {
                $isFloat = false;
                foreach ($this->floatFieldType as $ff) {
                    if (stripos($field['Type'], $ff) !== false) {
                        $fieldStr = 'float $' . $field['Field'];
                        $isFloat = true;
                        break;
                    }
                }
                $isFloat || $fieldStr = 'string $' . $field['Field'];
            }
            $commentFields[] = $fieldStr . ($field['Comment'] ? ( ' ' . $field['Comment'] ) : '');
        }
        return implode(PHP_EOL . ' * @property ', $commentFields);
    }
    
    private function getFieldsStr(array $fields) :string
    {
        $str = '';
        foreach ($fields as $field) {
            $str .= sprintf(
                '\'%s\',%s',
                $field['Field'],
                PHP_EOL . $this->tab(2));
        }
        return trim($str);
    }

    protected function getTraits(array $fieldsInfo): string
    {
        if (in_array('user_id', array_column($fieldsInfo, 'Field'))) {
            return 'use \app\common\traits\UserWith;';
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
