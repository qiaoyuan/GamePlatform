<?php
// 应用公共文件
use app\common\model\AdminConfig;
use GuzzleHttp\Client;
use think\facade\App;
use think\facade\Cache;
use think\helper\Arr;
use think\helper\Str;
use util\Data;

/**
 * @param string|null $template  // 模板
 * @param string|null $module   //   模块
 * @param string|null $domain  // 域名(模板目录)
 * @param string $parent // 布局
 * @return string
 */
function template(string $template, string $parent = 'base', string $module = null, string $domain = null): string
{
    $module = $module ?: app('http')->getName();
    $template = $template ? explode('/', $template) : [];
    count($template) < 2 && array_unshift($template, Str::snake(request()->controller()));
    count($template) < 3 && array_unshift($template, $module);
    $domain = $domain ?: (isMobileEnd() ? 'm' : 'www');
    $template_path_root = App::getRootPath() . 'app/view/' . $domain . '/';
    $cache_path_root = App::getRuntimePath() . 'view/' . $domain . '/';
    $template_path = $template_path_root;
    $cache_path = $cache_path_root;
    foreach ($template as $index => $tmp) {
        if ($index < count($template) - 1) {
            $template_path .= $tmp . '/';
            $cache_path .= $tmp . '/';
        }
    }
    $template_file = $template[count($template) - 1] . '.html';
    $cache_file = $template[count($template) - 1] . '.php';
    $template_name = $template_path . $template_file;

    $cache_name = $cache_path . $cache_file;
    $is_cache = true;
    if (file_exists($cache_name)) {
        if (@filemtime($template_name) > @filemtime($cache_name)) {
            $is_cache = false;
        }
    } else {
        $is_cache = false;
    }
    if ($parent) {
        $parent_template = $template_path_root . $parent . '.html';
        $parent_cache = $cache_path_root . $parent . '.php';
        if (!file_exists($parent_cache) || @filemtime($parent_template) > @filemtime($parent_cache)) {
            $is_cache = false;
            app('\template\TemplateCache')->template_compile($parent_template, $parent_cache);
        }
        if ($is_cache && @filemtime($parent_cache) > @filemtime($cache_name)) {
            $is_cache = false;
        }
    }
    if (!$is_cache) {
        app('\template\TemplateCache')->template_compile($template_name, $cache_path . $template[2] . $parent . '.php');
        if ($parent) {
            $parent_content = file_get_contents($parent_cache);
            file_put_contents($cache_name, str_replace(
                    '<child-template/>',
                    '<?php include \'' . ($template[2] . $parent . '.php') . '\' ?>',
                    $parent_content)
            );
        }
    }
    return $cache_name;
}

function generateCode(int $id) :string
{
    return date('ymdHis') . str_pad($id % 1000, 3, 0, STR_PAD_LEFT);
}

function fullDomain($domain = 'www', $end = '') :string
{
    return config('domain.schema') . config('domain.' . $domain) . config('domain.root') . $end;
}

function getDomain($domain, $schema = 'http://')
{
    return $schema . config('domain.' . $domain);
}

function setPassWord($value)
{
    return $value ? password_hash($value, PASSWORD_DEFAULT) : '';
}

function fastCache(string $key, \Closure $fn = null, $options = null, $tag = null, $context = null)
{
    $data = mCache($key);
    if (!$data && $fn) {
        $context && $fn->bindTo($context);
        $data = $fn();
        mCache($key, $data, $options, $tag);
    }
    return $data;
}

function stableCache(string $key, \Closure $fn, int $expire, $tag = null, $context = null)
{
    $data = cache($key);
    $result = $data['data'] ?? [];
    if (!$result || !isset($data['expire']) || $data['expire'] < time()) {
        $context && $fn->bindTo($context);
        $r = $fn();
        if ($r) {
            cache($key, [
                'data' => $r,
                'expire' => time() + $expire
            ], null, $tag);
            $result = $r;
        }
    }
    return $result;
}

function clearCache($key, $tag = '')
{
    if ($key) {
        Cache::delete($key);
    }
    if ($tag) {
        if ($tag === 'all') {
            Cache::clear();
        } else {
            Cache::tag($tag)->clear();
        }
    }
}
/**
 * 缓存管理
 * @param mixed     $name 缓存名称，如果为数组表示进行缓存设置
 * @param mixed     $value 缓存值
 * @param mixed     $options 缓存参数
 * @param string|null $tag 缓存标签
 * @return mixed
 */
function mCache($name, $value = '', $options = null, string $tag = null)
{
    if (env('app_status') === 'local') {
        return false;
    }
    return cache($name, $value, $options, $tag);
}

/**
 * 数组的封装
 *
 * @param string $name
 * @param mixed $value
 * @return mixed
 */
function mData(string $name, $value = null)
{
    if (is_null($value)) {
        $r = Data::getInstance()->get($name);
        if (!$r && $name == 'siteConfig') {
            $r = AdminConfig::getConfigValue('site_info');
            Data::getInstance()->set('siteConfig', $r);
        }
        return $r;
    } else {
        return Data::getInstance()->set($name, $value);
    }
}

function checkCaptcha($code): bool
{
    if (env('app_status')) {
        return true;
    }
    return Captcha::check($code);
}

/**
 * @param integer $type
 *
 * @return \util\Redis
 */
function redisCache(int $type = 1): \util\Redis
{
    return app('\util\Redis')->select($type);
}

function uploadFileRule(): string
{
    $date = new \DateTime();
    return $date->getTimestamp() . $date->format('u') . rand(10000, 99999);
}

function arrayToSelect(array $array, string $value = 'id', string $label = 'title'): array
{
    $r = [];
    foreach ($array as $item) {
        $r[] = array_merge(['value' => $item[$value], 'label' => $item[$label]], Arr::except($item, [$value, $label]));
    }
    return $r;
}

function mapToSelect(array $map, string $labelK = 'label', $valueK = 'value'): array
{
    $r = [];
    foreach ($map as $k => $v) {
        $r[] = [$valueK => $k, $labelK => $v];
    }
    return $r;
}

function seoTitle(string $title = ''): string
{
    return $title ?: mData('siteConfig')['title'];
}

function decodeUrlQuery($query_str): array
{
    $query_pairs = explode('&', $query_str);
    $params = [];
    foreach ($query_pairs as $query_pair) {
        $item = explode('=', $query_pair);
        $params[$item[0]] = $item[1];
    }
    return $params;
}

function dateNow(): string
{
    return date('Y-m-d H:i:s');
}

function isMobileEnd(): bool
{
    return false;
}

function clearApiCache(string $path, array $query = [], string $domain = 'ad_api') {
    try {
        if (Str::startsWith(request()->subDomain(), ['t'])) {
            $r = (new Client())->get(config('domain.t' . $domain) . 'v1/' . $path, [
                'query' => $query
            ]);
        } else {
            $r = (new Client())->get(config('domain.' . $domain) . ($domain == 'ext_api' ? 'ext/' : 'v1/') . $path, [
                'query' => $query
            ]);
        }
        trace(sprintf('clear %s, query: %s, return: %s', $path, json_encode($query), $r->getBody()));
    } catch (\Exception $e) {
        trace(sprintf('clear %s, query: %s, error: %s', $path, json_encode($query), $e->getMessage()), 'clear_error');
    }
}

function isLocal($env = null): bool
{
    return $env === 'local' || env('APP_STATUS') == 'local';
}

function isTest($env = null): bool
{
    return $env === 'test' || env('APP_STATUS') == 'test';
}

function isBeta($env = null): bool
{
    return $env === 'beta' || env('APP_STATUS') == 'beta';
}

function isPro($env = null): bool
{
    return $env === 'pro' || !env('APP_STATUS');
}

function isOnline($env = null): bool
{
    return isPro($env) || isBeta($env);
}
