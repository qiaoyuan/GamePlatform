<?php
declare (strict_types=1);

use app\common\middleware\AllowCrossDomain;
use Symfony\Component\VarDumper\VarDumper;
use think\db\exception\DataNotFoundException;
use think\db\exception\ModelNotFoundException;
use think\exception\HttpException;
use think\exception\HttpResponseException;
use think\exception\ValidateException;
use think\facade\Db;
use think\helper\Str;
use think\Response;

/**
 * 通过回调函数找到数组元素的KEY
 * @param array $array
 * @param Closure $fn
 * @return bool|int|string
 */
function array_find_index(array $array, \Closure $fn)
{
    foreach ($array as $index => $item) {
        if ($fn($item)) {
            return $index;
        }
    }
    return false;
}

/**
 * 字符串截取，如果字符串超长就添加...
 * @param string $value
 * @param int $limit
 * @param string $end
 * @return string
 */
function strLimit(string $value, int $limit = 100, string $end = '...'): string
{
    return Str::substr($value, 0, $limit) . (Str::length($value) > $limit ? $end : '');
}

/**
 * 递归去掉数组中所有值头尾的空白字符
 * @param $data
 * @param string $charlist
 */
function fullTrim(&$data, string $charlist = " \t\n\r\0\x0B")
{
    if (is_array($data)) {
        foreach ($data as &$row) {
            fullTrim($row);
        }
    } else {
        $data = $data && is_string($data) ? trim($data, $charlist) : $data;
    }
}

/**
 * 加密函数
 *
 * @param string $string
 * @param int $expire 有效期，单位秒
 * @param string $key 加密秘钥
 * @return string
 */
function mEncrypt(string $string, int $expire = 0, string $key = ''): string
{
    $key = md5($key ?: '!Q@W#E');
    $string = json_encode([
        'v' => $string,
        'e' => $expire ? ($expire + time()) : (time() + 86400000), // 如果不设置有效期，就给一个超大的有效期
    ]);
    return trim(base64_encode(openssl_encrypt($string, 'des-ede3', $key)), '=');
}

/**
 * 解密函数
 *
 * @param string $string
 * @param string $key 加密秘钥
 * @return string
 */
function mDecrypt(string $string, string $key = ''): string
{
    $key = md5($key ?: '!Q@W#E');
    $result = openssl_decrypt(base64_decode($string), 'des-ede3', $key);
    if ($result) {
        $result = json_decode($result, true);
    }
    if (!$result) {
        return '';
    }
    if (isset($result['e']) && $result['e'] < time()) {
        return '';
    }
    return $result['v'] ?? '';
}

/**
 * 解析身份证
 * @param string $vStr
 * @return bool|array
 */
function creditInfo(string $vStr)
{
    if (strlen($vStr) != 18) {
        return false;
    }
    $vStr = strtoupper($vStr);
    $vBirthday = substr($vStr, 6, 4) . '-' . substr($vStr, 10, 2) . '-' . substr($vStr, 12, 2);
    $data['birth'] = $vBirthday;
    $arr_int = [7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2];
    $arr_ch = ['1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2'];
    $sign = 0;
    for ($i = 0; $i < 17; $i++) {
        $b = (int)$vStr[$i];
        $w = $arr_int[$i];
        $sign += $b * $w;
    }
    $n = $sign % 11;
    $val_num = $arr_ch[$n];
    if ($val_num != substr($vStr, 17, 1)) {
        return false;
    }
    $gender = substr($vStr, 16, 1);
    $data['gender'] = $gender % 2;
    return $data;
}

/**
 * 简写html字符串处理
 * @param string $str
 * @return string
 */
function hs(string $str): string
{
    return htmlspecialchars($str);
}

/**
 * 删除目录下所有文件和目录
 * @param string $path
 */
function clearDir(string $path)
{
    if (is_dir($path)) {
        $p = scandir($path);
        foreach ($p as $val) {
            if ($val != "." && $val != "..") {
                if (is_dir($path . $val)) {
                    clearDir($path . $val . '/');
                    @rmdir($path . $val . '/');
                } else {
                    unlink($path . $val);
                }
            }
        }
    }
}

/**
 * @param array $arr
 * @param string $pk
 * @param string $parentField
 * @param string $child
 * @param Closure|null $filter
 * @return array
 */
function toTree(array $arr, string $pk = 'id', string $parentField = 'parent_id', string $child = 'children', Closure $filter = null): array
{
    $res = $map = [];
    $map = array_column($arr, null, $pk);
    foreach ($arr as $v) {
        if (isset($v[$parentField]) && isset($map[$v[$parentField]])) {
            $map[$v[$parentField]][$child][] = &$map[$v[$pk]];
        } else {
            $shouldContinue = true;
            $filter && $shouldContinue = $filter($v);
            $shouldContinue && $res[] = &$map[$v[$pk]];
        }
    }
    unset($map);
    return $res;
}

function array_has(array $arr, $key): bool
{
    is_array($key) || $key = [$key];
    foreach ($key as $item) {
        if (!isset($arr[$item])) {
            return false;
        }
    }
    return true;
}

function hideStr($str)
{
    if (strlen($str) == 11) {
        return substr($str, 0, 3) . '****' . substr($str, -4);
    }
    if (strlen($str) > 12) {
        return substr($str, 0, 4) . '****' . substr($str, -4);
    } elseif (strlen($str) > 8) {
        return substr($str, 0, 3) . '****' . substr($str, -3);
    } elseif (strlen($str) > 5) {
        return substr($str, 0, 1) . '****' . substr($str, -1);
    } elseif ($str) {
        return substr($str, 0, 1) . '*';
    } else {
        return '';
    }
}

function transaction(Closure $operateFn, $context = null, $connect = null): ?string
{
    $context && $operateFn->bindTo($context);
    Db::connect($connect)->startTrans();
    try {
        $operateFn();
        Db::connect($connect)->commit();
    } catch (Exception $e) {
        if ($e instanceof HttpResponseException) {
            if ($e->getResponse()->getData()['code'] === 0) {
                Db::connect($connect)->commit();
            } else {
                Db::connect($connect)->rollback();
            }
        } else {
            Db::connect($connect)->rollback();
        }
        if (in_array(get_class($e), [
            HttpException::class,
            HttpResponseException::class,
            ModelNotFoundException::class,
            DataNotFoundException::class,
            ValidateException::class,
        ])) {
            throw $e;
        }
        trace($e->__toString(), 'trans_error');
        return $e->getMessage();
    }
    return null;
}

function array_group(array $arr, $index): array
{
    $result = [];
    foreach ($arr as $row) {
        $result[$row[$index]][] = $row;
    }
    return $result;
}

function array_get_value(array $arr, $key, $default = null)
{
    return isset($arr[$key]) && $arr[$key] ? $arr[$key] : $default;
}

function dt(...$vars)
{
    throw new HttpResponseException((new AllowCrossDomain())->handle(request(), function ($request) use ($vars) {
        ob_start();
        foreach ($vars as $v) {
            VarDumper::dump($v);
        }
        $content = ob_get_clean();
        return Response::create($content);
    }));
}
