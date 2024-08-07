<?php

namespace app\common\traits;

use app\common\model\InstallBase;
use think\facade\Db;
use think\helper\Arr;

trait UuidTable
{
    public static function findOne(string $uuid, $field = '*'): array
    {
        return countTb(self::getTableName($uuid))
            ->where('uuid', $uuid)
            ->field($field)
            ->find();
    }

    public static function findAll(array $uuids, $field = '*'): array
    {
        $tables = [];
        foreach ($uuids as $uuid) {
            $table = self::getTableName($uuid);
            if (isset($tables[$table])) {
                $tables[$table][] = $uuid;
            } else {
                $tables[$table] = [$uuid];
            }
        }
        $result = [];
        foreach ($tables as $t => $ids) {
            $result = array_merge($result, countTb($t)->where('uuid', 'IN', $ids)->column($field, 'uuid'));
        }
        return $result;
    }

    public static function delAll(array $uuids)
    {
        $tables = [];
        foreach ($uuids as $uuid) {
            $table = self::getTableName($uuid);
            if (isset($tables[$table])) {
                $tables[$table][] = $uuid;
            } else {
                $tables[$table] = [$uuid];
            }
        }
        foreach ($tables as $t => $ids) {
            countTb($t)->where('uuid', 'IN', $ids)->delete();
        }
    }

    public static function addOne(array $data)
    {
        if (!isset($data['uuid']) || !$data['uuid']) {
            return false;
        }
        $fields = (new self())->getDbFields();
        return countTb(self::getTableName($data['uuid']))
            ->insert(Arr::only($data, $fields), true);
    }

    public static function addAll(array $data): bool
    {
        $fields = (new self())->getDbFields();
        $tables = [];
        foreach ($data as &$row) {
            $row = Arr::only($row, $fields);
            if (!isset($row['uuid']) || !$row['uuid']) {
                return false;
            }
            $table = self::getTableName($row['uuid']);
            if (isset($tables[$table])) {
                $tables[$table][] = $row;
            } else {
                $tables[$table] = [$row];
            }
        }
        foreach ($tables as $t => $list) {
            try {
                $r = countTb($t)->fetchSql()->insertAll($list);
                Db::connect(InstallBase::BNT_COUNT_CON)->execute(str_replace('INSERT INTO', 'REPLACE INTO', $r));
            } catch (\Exception $e) {
                var_dump($e->getMessage());
            }
        }
        unset($data);
        unset($tables);
        return true;
    }
}
