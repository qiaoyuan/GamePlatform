<?php

namespace app\common\model;

use Lcobucci\Clock\SystemClock;
use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\JwtFacade;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Validation\Constraint\SignedWith;
use Lcobucci\JWT\Validation\Constraint\StrictValidAt;

/**
 * @property int $id
 * @property int $status
 * @property string $password
 * @property string $last_login_ip
 * @property string $last_login_at
 * @property string $username 账号
 * @property string $nickname 名称
 * @property string $phone 电话
 * @property int $channel_id 渠道id
 * @property string $open_id 对于微信商家唯一标
 * @property string $avatar_url 头像
 * @property string $content 注册请求数据
 * @property string $token token
 * @property string $created_at
 * @property string $updated_at
 */
class User extends Base
{
    protected $autoWriteTimestamp = true;

    protected $table = 'user';
    protected $pk = 'id';
    protected $field = [
        'id',
        'status',
        'password',
        'last_login_ip',
        'last_login_at',
        'username',
        'nickname',
        'phone',
        'channel_id',
        'open_id',
        'avatar_url',
        'content',
        'token',
        'created_at',
        'updated_at',
    ];
    protected $type = [
        'amount' => 'float',
        'frozen_amount' => 'float',
    ];

    public function info()
    {
        return $this->hasOne(UserInfo::class, 'user_id', 'id');
    }

    public function userThirds()
    {
        return $this->hasMany(UserThird::class, 'user_id', 'id');
    }

    public function userDevices()
    {
        return $this->hasMany(UserDevice::class, 'user_id', 'id');
    }

    /**
     * 获取JWT Token
     *
     * @param int $adminId
     * @param string $name
     * @return string $token
     */
    public static function getToken($code, $uid) :string
    {
        $tokenBuilder = (new Builder(new JoseEncoder(), ChainedFormatter::default()));
        $algorithm    = new Sha256();
        $signingKey   = InMemory::plainText(config('jwt.key'));
        $now   = new \DateTimeImmutable();
        $token = $tokenBuilder
            // Configures the subject of the token (sub claim)
            ->relatedTo('admin')
            // Configures the time that the token was issue (iat claim)
            ->issuedAt($now)
            ->canOnlyBeUsedAfter($now)
            // Configures the expiration time of the token (exp claim)
            ->expiresAt($now->modify(config('jwt.expire')))
            // Configures a new claim, called "uid"
            ->withClaim('code', $code)
            ->withClaim('uid', $uid)
            // Builds a new token
            ->getToken($algorithm, $signingKey);
        return $token->toString();
    }
    /**
     * @param string $token
     * @return array
     */
    public static function verifyToken(string $token) :array
    {
        $signingKey   = InMemory::plainText(config('jwt.key'));

        try {
            $token = (new JwtFacade())->parse($token, new SignedWith(new Sha256(), $signingKey), new StrictValidAt(SystemClock::fromSystemTimezone()));
            return [
                'code' => $token->claims()->get('code'),
                'uid' => $token->claims()->get('uid'),
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
