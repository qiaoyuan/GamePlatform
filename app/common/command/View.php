<?php

declare (strict_types = 1);

namespace app\common\command;

use think\console\Input;
use think\console\input\Option;
use think\console\Output;
use think\helper\Str;

class View extends MakeBase
{
    private array $numberField = [
        'decimal', 'float', 'double', 'int'
    ];

    private array $notRequiredField = [
        'sort', 'desc', 'content', 'description', 'parent_id', 'tips'
    ];

    protected $type = 'View';

    private bool $dialog = true;

    protected function configure()
    {
        parent::configure();
        $this->setName('view')
            ->setDescription('Create a new vue view');
        $this->addOption('lazy', null, Option::VALUE_NONE, '懒加载表格');
    }

    protected function getStub()
    {
        return __DIR__ . '/stubs/mView.stub';
    }

    protected function getNamespace(string $app): string
    {
        return parent::getNamespace($app ?: 'admin') . '\\controller';
    }

    protected function buildClass(string $name)
    {
        return '';
    }

    public function mMake(string $name, Output $output)
    {
        if ($this->input->getOption('route')) {
            $this->dialog = false;
        }
        $classname = $this->getClassName($name);
        $namespaceArr = explode('\\', $name);
        $path = $this->app->getRootPath() . 'admin/src/views/' . Str::camel($namespaceArr[count($namespaceArr) - 1]);
        $index = $path . '/index.vue';
        $str = $this->buildIndex($classname);
        if($this->input->getOption('replace') && is_file($index)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' index backup.');
            rename($index, str_replace('.vue', '1.vue', $index));
        }
        if (is_file($index)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' index already exists.');
        } else {
            if (!is_dir(dirname($index))) {
                mkdir(dirname($index), 0755, true);
            }
            file_put_contents($index, $str);
            $this->outputSuccess($output, $name . ' ' . $this->type . ' index created successfully.');
        }

            $str = $this->buildAdd($classname);
        if (!$this->dialog) {
            $add = $path . '/add.vue';
        } else {
            $add = $path . '/dialog/' . Str::camel($namespaceArr[count($namespaceArr) - 1]) . 'AddDialog.vue';
        }
        if($this->input->getOption('replace') && is_file($add)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' add backup.');
            rename($add, str_replace('.vue', '1.vue', $add));
        }
        if (is_file($add)) {
            $this->outputWarning($output, $name . ' ' . $this->type . ' add already exists.');
        } else {
            if (!is_dir(dirname($add))) {
                mkdir(dirname($add), 0755, true);
            }
            file_put_contents($add, $str);
            $this->outputSuccess($output, $name . ' ' . $this->type . ' add created successfully.');
        }
    }

    private function buildIndex(string $name): string
    {
        if ($this->dialog) {
            $stub = file_get_contents(__DIR__ . '/stubs/mViewDialog.stub');
        } else {
            $stub = file_get_contents(__DIR__ . '/stubs/mView.stub');
        }
        return $this->buildStr($stub, $name);
    }

    private function buildAdd(string $name): string
    {
        if ($this->dialog) {
            $stub = file_get_contents(__DIR__ . '/stubs/mViewAddDialog.stub');
        } else {
            $stub = file_get_contents(__DIR__ . '/stubs/mViewAdd.stub');
        }
        return $this->buildStr($stub, $name);
    }

    private function buildStr(string $stub, string $name): string
    {
        $lazy = $this->input->getOption('lazy');
        $namespaceArr = explode('\\', $name);
        $module = $namespaceArr[count($namespaceArr) - 1];
        $table = Str::snake($module);
        $fieldsInfo = $this->app->db->query('show full columns from `' . $table . '`');
        return str_replace([
            '{%module%}',
            '{%moduleUrl%}',
            '{%pk%}',
            '{%fields%}',
            '{%form%}',
            '{%lazyProps%}',
            '{%lazyData%}',
            '{%lazyGetList%}'
        ], [
            $module,
            Str::camel($module),
            $this->getPk($fieldsInfo),
            $this->getFields($fieldsInfo),
            $this->getForm(Str::camel($module), $fieldsInfo),
            $lazy
                ? str_replace(
                    ['{%pk%}'],
                    [$this->getPk($fieldsInfo)],
                    file_get_contents(__DIR__ . '/stubs/mViewLazyProps.stub')
                )
                : '',
            $lazy ? file_get_contents(__DIR__ . '/stubs/mViewLazyData.stub') : '',
            $lazy
                ? file_get_contents(__DIR__ . '/stubs/mViewLazyGetList.stub')
                : file_get_contents(__DIR__ . '/stubs/mViewGetList.stub'),
        ], $stub);
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

    private function getForm(string $module, array $fieldsInfo) :string
    {
        $fields = PHP_EOL;
        foreach ($fieldsInfo as $row) {
            if (!in_array($row['Field'], ['created_at', 'updated_at', 'deleted_at'])) {
                if ($row['Key'] == 'PRI') {
                    continue;
                }
                $formType = '';
                $option = '';
                if (Str::contains(strtolower($row['Type']), $this->numberField)) {
                    $formType = 'number';
                }
                if (Str::startsWith($row['Field'], 'is_')) {
                    $formType = 'boolean';
                }
                if ($row['Field'] == 'status') {
                    $formType = 'status';
                }
                if (Str::endsWith($row['Field'], ['_id'])) {
                    $formType = 'select';
                    $option = sprintf("'/%s/select'", Str::camel(str_replace('_id', '', $row['Field'])));
                }
                if (Str::endsWith($row['Field'], ['_type'])) {
                    $formType = 'select';
                    $option = sprintf("{ url: '/%s/create', key: '%s' }", $module, Str::camel($row['Field']));
                }
                if(Str::contains($row['Comment'], 'upload')) {
                    $formType = 'upload';
                }
                $fields .= $this->getFormItem($row['Field'], trim($row['Comment'] ?: ''), $formType, $option);
            }
        }
        return $fields . $this->tab(3);
    }

    private function getFormItem(string $filed, string $comment, string $formType, string $option): string
    {
        if (Str::contains($comment, ['（', '('])) {
            $comment = mb_substr(mb_substr($comment, 0, mb_strpos($comment, '(') ?: null ), 0, mb_strpos($comment, '（') ?: null);
        }
        if ($formType == 'select' || $formType == 'radio') {
            $attrs = [
                sprintf("label: '%s'", $comment),
                sprintf("value: %s", $filed),
                sprintf("formType: '%s'", $formType),
                sprintf("options: %s", $option),
            ];
            if (in_array($filed, $this->notRequiredField)) {
                $attrs[] = 'required: false';
            }
            return sprintf(
                "%s%s: {%s%s%s},%s",
                $this->tab(4),
                $filed,
                PHP_EOL . $this->tab(5),
                implode(',' . PHP_EOL . $this->tab(5), $attrs),
                PHP_EOL . $this->tab(4),
                PHP_EOL
            );
        }
        if ($formType == 'upload') {
            return sprintf(
                "%s%s: {%s%s%s},%s",
                $this->tab(4),
                $filed,
                PHP_EOL . $this->tab(5),
                implode(',' . PHP_EOL . $this->tab(5), [
                    sprintf("label: '%s'", $comment),
                    sprintf("value: [{ path: %s, url: %s }]", $filed, $filed),
                    sprintf("formType: '%s'", $formType),
                    sprintf("attrs: {%s  accept: '.jpg,.png,.jpeg'%s}", PHP_EOL . $this->tab(5), PHP_EOL . $this->tab(5)),
                    'type: \'array\''
                ]),
                PHP_EOL . $this->tab(4),
                PHP_EOL
            );
        }
        if ($formType) {
            return sprintf(
                "%s%s: { label: '%s', value: %s, formType: '%s'%s },%s",
                $this->tab(4),
                $filed,
                $comment,
                $filed,
                $formType,
                in_array($filed, $this->notRequiredField) ? ', required: false' : '',
                PHP_EOL
            );
        }
        return sprintf(
            "%s%s: { label: '%s', value: %s%s },%s",
            $this->tab(4),
            $filed,
            $comment,
            $filed,
            in_array($filed, $this->notRequiredField) ? ', required: false' : '',
            PHP_EOL
        );
    }

    private function getFields(array $fieldsInfo) :string
    {
        return implode(', ', array_column(array_filter($fieldsInfo, function ($row) {
            return !in_array($row['Field'], ['created_at', 'updated_at', 'deleted_at']);
        }), 'Field'));
    }

    public function all(Output $output, bool $route = false): void
    {
        $tables = $this->app->db->getTables();
        foreach ($tables as $table) {
            $this->mMake($table, $output, $route);
        }
    }

    protected function tab(int $size, string $end = ''): string
    {
        return str_repeat('  ', $size) . $end;
    }
}
