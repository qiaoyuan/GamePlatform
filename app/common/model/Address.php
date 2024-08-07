<?php

namespace app\common\model;

/**
 * @property int $id
 * @property string $name
 * @property string $code
 * @property int $pid
 * @property int $level
 * @property int $sort
 */
class Address extends Base
{
    protected $table = 'address';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $field = [
        'id',
        'name',
        'code',
        'pid',
        'level',
        'sort',
    ];
    protected $type = [];

    /**
     * @param mixed $id
     */
    public static function remove($id)
    {
        is_array($id) || $id = [$id];
        (new Address())->where('id', 'IN', $id)->delete();
        $sub_ids = Address::where('pid', 'IN', $id)->column('id');
        if ($sub_ids) {
            self::remove($sub_ids);
        }
    }

    public function parent()
    {
        return $this->belongsTo(Address::class, 'pid', 'id');
    }

    public function getHasChildrenAttr($value, $data)
    {
        return $data['level'] < 3;
    }
}
