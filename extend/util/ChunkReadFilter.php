<?php
declare (strict_types=1);

namespace util;

use PhpOffice\PhpSpreadsheet\Reader\IReadFilter;

class ChunkReadFilter implements IReadFilter
{
    private int $startRow = 0;
    private int $endRow = 0;

    public function setRows(int $startRow, int $chunkSize)
    {
        $this->startRow = $startRow;
        $this->endRow = $startRow + $chunkSize;
    }

    public function readCell($columnAddress, $row, $worksheetName = '')
    {
        return $row >= $this->startRow && $row < $this->endRow;
    }
}