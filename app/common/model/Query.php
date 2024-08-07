<?php
declare (strict_types=1);

namespace app\common\model;

use think\Collection;
use think\db\Query as BaseQuery;
use think\Paginator;

class Query extends BaseQuery
{
    /**
     * @param int|null $limit
     * @return Paginator | Collection | int
     */
    public function selectData(int $limit = null, $simple = null)
    {
        $limit = input('limit', $limit);
        if ($limit === 0 || input('_export')) {
            return $this->select();
        } else {
            if (input('_summary') === 0) {
                return $this->paginate($limit ?: 50, $simple ?: false);
            }
            if (input('_summary') === 1) {
                return $this->count();
            }
            return $this->paginate($limit ?: 50, $simple ?: false);
        }
    }

    /**
     * 批量更新多行不同值，每行数据必须带主键
     * eg:$data = [
     *     ['id' => 1, 'price' => 0.3],
     *     ['id' => 2, 'price' => 0.4],
     *     ['id' => 3, 'status' => 1],
     * ]
     *
     * @param array $data
     * @return mixed
     */
    public function batchUpdate(array $data)
    {
        $sql = sprintf('UPDATE `%s` SET ', $this->getTable());
        $pk = $this->getPk();
        $update = $sqlArr = [];
        foreach ($data as $item) {
            if (!isset($item[$pk])) {
                continue;
            }
            foreach ($item as $k => $v) {
                if ($k == $pk) {
                    continue;
                }
                $update[$k][] = sprintf('WHEN `%s` = %d THEN \'%s\'', $pk, $item[$pk], (string)$v);
            }
        }

        foreach ($update as $field => $updateItem) {
            $sqlArr[] = sprintf('`%s` = CASE %s ELSE `%s` END', $field, implode(' ', $updateItem), $field);
        }
        $sql .= implode(',', $sqlArr);
        $sql .= ' WHERE ' . $this->connection->getBuilder()->buildWhere($this, $this->getOptions('where'));
        return $this->connection->execute($sql, $this->getBind());
    }
}