<?php

namespace app\common\model;

use Lcobucci\Clock\SystemClock;

use Lcobucci\JWT\Encoding\ChainedFormatter;
use Lcobucci\JWT\Encoding\JoseEncoder;
use Lcobucci\JWT\Signer\Key\InMemory;
use Lcobucci\JWT\Signer\Hmac\Sha256;
use Lcobucci\JWT\Token\Builder;
use Lcobucci\JWT\Validation\Constraint;
use Lcobucci\JWT\JwtFacade;
use think\model\relation\BelongsTo;

/**
 * @property int $id
 * @property int $reg_admin_id 创建人
 * @property string $reg_ip
 * @property string $reg_at
 * @property int $status
 * @property string $password
 * @property string $last_login_ip
 * @property string $last_login_at
 * @property string $username 账号
 * @property string $nickname 名称
 * @property string $phone 电话
 * @property int $admin_department_id 部门
 * @property int $is_leader 是否是部门主管
 */
class Admin extends Base
{
    const SHOW_FIELDS = 'id,nickname,username';

    protected $table = 'admin';
    protected $pk = 'id';
    protected $autoWriteTimestamp = false;
    protected $field = [
        'id',
        'reg_admin_id',
        'reg_ip',
        'reg_at',
        'status',
        'password',
        'last_login_ip',
        'last_login_at',
        'username',
        'nickname',
        'phone',
        'admin_department_id',
        'is_leader',
    ];
    protected $type = [];

    public function adminDepartment(): BelongsTo
    {
        return $this->belongsTo(AdminDepartment::class, 'admin_department_id', 'id');
    }

    /**
     * @param $username
     * @param $password
     * @return Admin|bool
     */
    public function login($username, $password)
    {
//        $tryIp = cache('admin_login_try_ip_' . request()->ip());
//        if ($tryIp && $tryIp >= 10) {
//            $this->error = '尝试次数过多，请稍后再试';
//            return false;
//        }
//        $try = cache('admin_login_try_' . $username);
//        if ($try && $try >= 5) {
//            $this->error = '尝试次数过多，请稍后再试';
//            return false;
//        }
        $admin = $this->where('username|phone|nickname', $username)->find();
        if ($admin) {
            if ($admin['status'] === 0) {
                $this->error = '用户被冻结，请联系管理员';
                return false;
            }
            if (password_verify($password, $admin->password)) {
                $admin->last_login_at = dateNow();
                $admin->last_login_ip = request()->ip();
                $admin->save();
                AdminLoginLog::create([
                    'ip' => $admin->last_login_ip,
                    'admin_id' => $admin->id,
                ]);
                return $admin->hidden(['password']);
            }
        }
        cache('admin_login_try_' . $username, ($try ?: 0) + 1, 300);
        cache('admin_login_try_ip_' . request()->ip(), ($tryIp ?: 0) + 1, 300);
        $this->error = '用户名密码错误';
        return false;
    }

    /**
     * 获取JWT Token
     *
     * @param int $adminId
     * @param string $name
     * @return string $token
     */
    public static function getToken(int $adminId, string $name) :string
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
            ->withClaim('admin_id', $adminId)
            ->withClaim('nickname', $name)
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
            $token = (new JwtFacade())->parse(
                $token,
                new Constraint\SignedWith(new Sha256(), $signingKey),
                new Constraint\StrictValidAt(
                    SystemClock::fromSystemTimezone()
                )
            );
            return [
                'admin_id' => intval($token->claims()->get('admin_id')),
                'nickname' => $token->claims()->get('nickname')
            ];
        } catch (\Exception $e) {
            return [];
        }
    }
}
