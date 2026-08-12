<?php
namespace app\common\traits;

use app\common\model\User;

trait UserColumn {
    public function getUserColumns(): array
    {
        return [
            [
                'v' => 'user_id',
                'label' => '用户ID',
                'sort' => 'user_id',
                'searchType' => 'like',
            ],
            [
                'v' => 'user.phone',
                'label' => '电话',
                'sort' => false,
                'search' => 'phone',
                'searchType' => 'like',
            ],
        ];
    }

    protected function getCondition(array $fields, array $kFields = []): array
    {
        $where = parent::getCondition($fields, $kFields);
        if (in_array('user_id', $fields)) {
            if (input('phone_like')) {
                $userIds = User::whereLike('phone', '%' . input('phone_like') . '%')->limit(50)->column('id');
                $where[] = ['user_id', 'IN', $userIds];
            }
        }
        return $where;
    }
}
