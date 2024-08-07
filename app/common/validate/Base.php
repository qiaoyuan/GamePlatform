<?php

namespace app\common\validate;

use think\Validate;

class Base extends Validate
{
    /**
     * 默认规则提示
     * @var array
     */
    protected $typeMsg = [
        'require'     => ':attribute require',
        'must'        => ':attribute must',
        'number'      => ':attribute must be numeric',
        'integer'     => ':attribute must be integer',
        'float'       => ':attribute must be float',
        'boolean'     => '请选择:attribute',
        'email'       => ':attribute不是一个有效的邮箱',
        'mobile'      => ':attribute不是一个有效的手机号',
        'array'       => ':attribute must be a array',
        'accepted'    => ':attribute must be yes,on or 1',
        'date'        => ':attribute not a valid datetime',
        'file'        => ':attribute not a valid file',
        'image'       => ':attribute not a valid image',
        'alpha'       => ':attribute must be alpha',
        'alphaNum'    => ':attribute must be alpha-numeric',
        'alphaDash'   => ':attribute must be alpha-numeric, dash, underscore',
        'activeUrl'   => ':attribute not a valid domain or ip',
        'chs'         => ':attribute must be chinese',
        'chsAlpha'    => ':attribute must be chinese or alpha',
        'chsAlphaNum' => ':attribute must be chinese,alpha-numeric',
        'chsDash'     => ':attribute must be chinese,alpha-numeric,underscore, dash',
        'url'         => ':attribute not a valid url',
        'ip'          => ':attribute not a valid ip',
        'dateFormat'  => ':attribute must be dateFormat of :rule',
        'in'          => ':attribute must be in :rule',
        'notIn'       => ':attribute be notin :rule',
        'between'     => ':attribute must between :1 - :2',
        'notBetween'  => ':attribute not between :1 - :2',
        'length'      => 'size of :attribute must be :rule',
        'max'         => ':attribute的最大长度为:rule个字符',
        'min'         => ':attribute的最小长度为:rule个字符',
        'after'       => ':attribute cannot be less than :rule',
        'before'      => ':attribute cannot exceed :rule',
        'expire'      => ':attribute not within :rule',
        'allowIp'     => 'access IP is not allowed',
        'denyIp'      => 'access IP denied',
        'confirm'     => ':attribute out of accord with :2',
        'different'   => ':attribute cannot be same with :2',
        'egt'         => ':attribute must greater than or equal :rule',
        'gt'          => ':attribute must greater than :rule',
        'elt'         => ':attribute must less than or equal :rule',
        'lt'          => ':attribute must less than :rule',
        'eq'          => ':attribute must equal :rule',
        'unique'      => ':attribute has exists',
        'regex'       => ':attribute not conform to the rules',
        'method'      => 'invalid Request method',
        'token'       => 'invalid token',
        'fileSize'    => '文件太大',
        'fileExt'     => '文件格式错误',
        'fileMime'    => 'mimetype to upload is not allowed',
        'idCard'      => '无效身份证号码',
    ];

    public function idCard($value, $rule, $data=[])
    {
        if (!creditInfo($value)) {
            return false;
        }
        return true;
    }
}
