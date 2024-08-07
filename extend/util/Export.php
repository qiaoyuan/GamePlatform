<?php

namespace util;

use app\common\model\Attachment;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Worksheet\Drawing;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use think\Collection;
use think\helper\Str;

set_time_limit(0);
ini_set('memory_limit', '1024M');

/**
 * Class Export
 * 导出类
 * @package app\common\services
 *
 */
class Export
{

    private string $filename = '';

    private Spreadsheet $spreadsheet;

    private Worksheet $worksheet;

    private int $currentRow = 1;

    /**
     * @var array 合并单元格 eg: A1:A10
     */
    private array $merges = [];

    /**
     * @var array 哪些列是图片列，在写入数据时，如果是图片列，会解析图片
     */
    private array $imageColumns = [];

    private array $defaultStyle = [
        'borders' => [
            'allBorders' => [
                'borderStyle' => Border::BORDER_THIN,
            ],
        ],
        'alignment' => [
            'horizontal' => Alignment::HORIZONTAL_CENTER,
            'vertical' => Alignment::VERTICAL_CENTER,
            'wrapText' => true,
        ],
    ];

    public function __construct()
    {
        $this->spreadsheet = new Spreadsheet();
        $this->worksheet = $this->spreadsheet->getActiveSheet();
    }

    public function run($filename = null, $path = null)
    {
        if ($this->merges) {
            foreach ($this->merges as $value) {
                $this->worksheet->mergeCells($value);
            }
        }
        $this->filename = $filename ?: $this->filename;
        $this->filename = $this->filename ?: (date('Y-m-d') .  '.xlsx');
        if (!Str::endsWith($this->filename, '.xlsx')) {
            $this->filename .= '.xlsx';
        }
        $writer = IOFactory::createWriter($this->spreadsheet, 'Xlsx');
        if ($path) {
            $writer->save($path . $this->filename);
        } else {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment;filename="' . urlencode($this->filename) . '"');
            header('Cache-Control: max-age=0');
            header('Access-Control-Expose-Headers: Content-Disposition');
            $writer->save('php://output');
        }
    }

    /**
     * 下载excel文件
     *  $fields = [
     *     'name',
     *     'info.phone',
     *     'birth|date:Y-m-d,@',
     *     'info.code|strtoupper',
     *     ['info.address.addr_prefix', 'info.address.address', 'delimiter' => "\r\n"]
     *     [
     *                   'info.phone' => function ($val, $data) {
     *                       return $data['info']['phone'] === '13788888888' ? '99999999' : $val;
     *                   }
     *              ]
     *     ];
     * $data = [
     *     [
     *       'name' => 'Aaron', 'birth' => time(),
     *       'info' => ['phone' => '13788888888', 'code' => 'abc', 'address' => ['addr_prefix' => '成都', 'address' => '高新区天府三街']]
     *     ],
     * ];
     *
     * @param array $fields 需要下载数据字段及名字
     * @param mixed $data 数据
     * @param array $replaceList 需要替换的数据
     * @param int $fontSize
     * @return Export
     */
    public function writeData(array $fields, $data, array $replaceList = [], int $fontSize = 12) :self
    {
        if ($data instanceof Collection) {
            $data = $data->toArray();
        }
        $list = [];
        $fieldsConfig = [];
        foreach ($fields as $field) {
            $fieldRow = ['fields' => [], 'delimiter' => ','];
            is_array($field) || $field = [$field];
            foreach ($field as $i => $f) {
                if (is_numeric($i)) {
                    if (!empty($replaceList[$f])) {
                        $fieldRow['replaceList'] = $replaceList[$f];
                    }
                    $fieldRow['fields'] = array_merge($fieldRow['fields'], $this->getFieldConfig($f));
                } elseif ($i === 'delimiter') {
                    $fieldRow['delimiter'] = $f;
                } else {
                    $fieldRow['fields'] = array_merge($fieldRow['fields'], $this->getFieldConfig($i, $f));
                }
            }
            $fieldsConfig[] = $fieldRow;
            unset($fieldRow);
        }
        unset($field);
        unset($f);
        foreach ($data as $item) {
            $row = [];
            foreach ($fieldsConfig as $fieldConfig) {
                $val = [];
                foreach ($fieldConfig['fields'] as $f => $method) {
                    $val[] = $this->getFieldValue($f, $method, $item, $fieldConfig['replaceList'] ?? []);
                }
                $row[] = implode($fieldConfig['delimiter'], $val);
            }
            $list[] = $row;
            unset($row);
        }
        $rowStart = $this->currentRow;
        foreach ($list as $index => $r) {
            for ($i = 1; $i <= count($r); $i++) {
                $this->writeCell($i, $this->currentRow, $r[$i - 1], in_array($i, $this->imageColumns));
            }
            $this->currentRow++;
        }
        if ($list) {
            $styleSheet = $this->getStyleRange($rowStart, $this->currentRow - 1, count($list[0] ?? []));
            $style = $this->defaultStyle;
            $style['font'] = [
                'size' => $fontSize
            ];
            $this->worksheet
                ->getStyle($styleSheet)
                ->applyFromArray($style);
        }
        return $this;
    }

    public function writeLine(string $str, int $width = 26, int $fontSize = 16) :self
    {
        $this->worksheet->setCellValueByColumnAndRow(1, $this->currentRow, $str);
        $row = $this->getStyleRange($this->currentRow, $this->currentRow, $width);
        $this->merges[] = $row;

        $style = $this->defaultStyle;
        $style['font'] = [
            'bold' => true,
            'size' => $fontSize
        ];
        $this->worksheet->getStyle($row)->applyFromArray($style);

        $this->currentRow++;
        return $this;
    }

    /**
     * @param array $headers
     * eg:[
     *     ['label' => '姓名', 'width' => 100]
     * ]
     * 其中宽度是浏览器中表格显示的宽度
     * @param int $fontSize
     * @return $this
     */
    public function writeHeader(array $headers, int $fontSize = 14) :self
    {
        $i = 1;
        foreach ($headers as $header) {
            $this->worksheet->setCellValue($this->getCell($i, $this->getCurrentRow()), $header['label']);
            if (!empty($header['minWidth']) && empty($header['width'])) {
                $header['width'] = $header['minWidth'];
            }
            if (!empty($header['width'])) {
                $header['width'] = ceil(($header['width'] - 5) / 8);
            }
            if (str_contains($header['label'], '图片') && empty($header['width'])) {
                $header['width'] = 30;
            }
            if (!empty($header['headerTooltip'])){
                $this->worksheet->getComment($this->getCell($i, 1))->getText()->createTextRun($header['headerTooltip']);
            }
            if (!empty($header['width'])) {
                $this->worksheet->getColumnDimension($this->getCell($i, ''))->setWidth($header['width']);
            } else {
                $this->worksheet->getColumnDimension($this->getCell($i, ''))->setAutoSize(true);
            }
            $i++;
        }
        $style = $this->defaultStyle;
        $style['font'] = [
            'bold' => true,
            'size' => $fontSize
        ];
        $this->worksheet
            ->getStyle($this->getStyleRange($this->currentRow, $this->currentRow, $i - 1))
            ->applyFromArray($style);

        $this->currentRow++;
        return $this;
    }

    public function writeCell(int $c, int $r, string $value, bool $isImage = false)
    {
        if ($isImage && $value && Str::startsWith($value, 'http')) {
            $this->worksheet->getRowDimension($this->currentRow)->setRowHeight(120);
            $fileInfo = parse_url($value);
            $fileInfo = pathinfo($fileInfo['path'] ?? '');
            if ($fileInfo['basename']) {
                $downloadPath = Attachment::downloadImage($value, $fileInfo['basename']);
                if ($downloadPath) {
                    $cell = $this->worksheet->getCell($this->getCell($c, $r));
                    $drawing = new Drawing();
                    $drawing->setName('图片');
                    $drawing->setDescription('图片');
                    $drawing->setPath($downloadPath);
                    $drawing->setWidthAndHeight(150, 150);
                    $drawing->setResizeProportional(true);
                    $drawing->setCoordinates($cell->getColumn() . $this->currentRow);
                    $drawing->setOffsetX(2);
                    $drawing->setOffsetY(2);
                    $drawing->setWorksheet($this->worksheet);
                    return;
                }
            }
        }
        if (is_numeric($value) && strlen($value) > 10) {
            $value .= "\t";
        }
        if (0 === strpos($value, '=')) {
            $value = "'" . $value;
        }
        $this->worksheet->setCellValue($this->getCell($c, $r), $value);
    }

    public function getCell($c, $r): string
    {
        $cell = chr(ord('A') + ($c - 1) % 26) . $r;
        if ($c > 26) {
            $cell = chr(ord('A') + floor(($c - 1) / 26) - 1) . $cell;
        }
        return $cell;
    }

    public function addMerge(string $merge)
    {
        $this->merges[] = $merge;
    }

    public function getCurrentRow(): int
    {
        return $this->currentRow;
    }

    public function nextLine()
    {
        $this->currentRow++;
    }

    public function setImageColumns(array $columns)
    {
        $this->imageColumns = $columns;
    }

    public function columnWidth($setting, int $width = null)
    {
        if (is_array($setting)) {
            foreach ($setting as $k => $v) {
                $this->worksheet->getColumnDimensionByColumn($k)->setWidth($v);
            }
        } else {
            $this->worksheet->getColumnDimensionByColumn($setting)->setWidth($width);
        }
    }

    public function getStyleRange(int $rowStart, int $rowEnd, int $width = 26, string $columnStart = 'A'): string
    {
        return sprintf('%s%d:%s%d', $columnStart, $rowStart, $this->getCell($width, ''), $rowEnd);
    }

    private function getFieldValue(string $field, array $methods = null, array $row = [], array $replaceList = [])
    {
        if (!$field) {
            return '';
        }
        $k = explode('.', $field);
        $val = $row;
        foreach ($k as $f) {
            if (isset($val[$f])) {
                $val = $val[$f];
            } else {
                $val = '';
                break;
            }
        }
        // 存在需要进行替换的数据
        if (!empty($replaceList)) {
            is_array($val) || $val = [$val];
            $join = [];
            foreach ($val as $values) {
                $join[] = $this->getFieldReplace($values, $replaceList);
            }
            $val = join(',', $join);
        }
        if ($methods) {
            foreach ($methods as $method) {
                $params = explode(',', $method[1]);
                foreach ($params as &$p) {
                    $p = $p === '@' ? $val : ($p === '@@' ? $row : $p);
                }
                if (is_callable($method[0])) {
                    $val = call_user_func_array($method[0], $params);
                }
            }
        }
        if (is_array($val)){
            $val = json_encode($val);
        }
        return $val;
    }

    /**
     * 递归查找数据
     * @param $val
     * @param array $replaceData
     * @param string $value
     * @param string $result
     * @return string
     */
    private function getFieldReplace($val, array $replaceData = [], string $value = 'value', string $result = 'label') : string
    {
        if (empty($replaceData)) {
            return '\\';
        }
        foreach ($replaceData as $v) {
            if (isset($v[$value]) && $v[$value] == $val) {
                return $v[$result];
            }
            $resVal = $this->getFieldReplace($val, $v['children'] ?? [], $value, $result);
            if ($resVal != '\\') {
                return $resVal;
            }
        }
        return '\\';
    }

    private function getFieldConfig(string $field, $fn = null): array
    {
        $field = explode('|', $field);
        $method = $field[1] ?? null;
        $result = [$field[0] => null];
        if ($method) {
            $params = '@';
            if (strpos($method, ':') !== false) {
                [$method, $param] = explode(':', $method);
                if (stripos($param, '@') === false) {
                    $params = '@,' . $param;
                }
            }
            if (is_callable($method)) {
                $result[$field[0]] = [[$method, $params]];
            }
        }
        if ($fn && is_callable($fn)) {
            $result[$field[0]][] = [$fn, '@,@@'];
        }
        return $result;
    }
}
