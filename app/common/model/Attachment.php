<?php

namespace app\common\model;

use GuzzleHttp\Client;
use think\facade\App;
use think\helper\Str;
use think\Image;

/**
 * @property int $id
 * @property string $path 路径
 * @property string $created_at 上传时间
 * @property string $ext 文件名后缀
 * @property string $type 类型
 * @property int $size 大小
 * @property int $admin_id 上传管理员
 * @property string $filename 文件名
 * @property string $md5 HASH
 * @property int $user_id 上传用户
 */
class Attachment extends Base
{
    protected $table = 'attachment';
    protected $pk = 'id';
    protected $updateTime = false;
    protected $field = [
        'id',
        'path',
        'created_at',
        'ext',
        'type',
        'size',
        'admin_id',
        'filename',
        'md5',
        'user_id',
    ];
    protected $type = [];

    public function getUrlAttr($value, $data): string
    {
        return fullDomain($data['type'], '/') . $data['path'];
    }

    public static function deleteById($ids)
    {
        is_array($ids) || $ids = [$ids];
        $objArr = self::where('id', 'in', $ids)->column('path');
        return self::deleteByName($objArr);
    }

    public static function deleteByName($names)
    {
        if (!$names) {
            return false;
        }
        is_array($names) || $names = [$names];
        self::startTrans();
        try {
            self::where('path', 'IN', $names)->delete();
            $root = config('filesystem.disks.public.root');
            foreach ($names as $name) {
                if (file_exists($root . $name)) {
                    unlink($root . $name);
                }
            }
            self::commit();
        } catch (\Exception $e) {
            self::rollback();
            trace($e->__toString(), 'attachment_delete');
            return $e->getMessage();
        }
        return true;
    }

//    public static function getRemoteImage($url, $filename)
//    {
//        $downloadPath = self::downloadImage($url, $filename);
//        if ($downloadPath) {
//            app('upload\oss\File')->upload(config('upload.bucket'), $filename, $downloadPath);
//            Attachment::create([
//                'path' => $filename,
//                'ext' => substr($url, strrpos($url, '.') + 1),
//                'type' => 'img',
//                'size' => filesize($downloadPath),
//                'name' => substr($url, strrpos($url, '/') + 1),
//                'admin_id' => 0,
//                'user_id' => 0,
//                'prefix' => substr($url, 0, strrpos($filename, '_')),
//            ]);
//            unlink($downloadPath);
//            return fullDomain('') . $filename;
//        }
//        return '';
//    }

    public static function downloadImage($url, $filename): string
    {
        $path = '';
        if (!Str::startsWith($filename, '/')) {
            $path = App::getRuntimePath() . 'temp/remote/';
            if (!file_exists($path)) {
                mkdir($path, 0777, true);
            }
        } else {
            if (!file_exists(dirname($filename))) {
                mkdir(dirname($filename), 0777, true);
            }
        }
        $resource = fopen($path . $filename, 'w+');
        (new Client())->request('get', $url, ['sink' => $resource]);
        if (file_exists($path . $filename)) {
            return $path . $filename;
        }
        return '';
    }

    public static function resize($file, string $type = '', bool $isAdmin = false)
    {
        $img = Image::open($file);
        $size = filesize($file);
        $quantityConfig = [
            50 => 100,
            100 => 98,
            200 => 95,
            600 => 90,
            1000 => 85,
            3000 => 80,
            5000 => 75,
        ];
        $quantity = 70;
        foreach ($quantityConfig as $s => $q) {
            if ($size < $s * 1024) {
                $quantity = $q;
                break;
            }
        }
        if ($isAdmin) {
            $q = request()->param('q');
            $quantity = $q ?: $quantity;
        }
        if ($type === 'avatar') {
            $img->thumb(300, 300, Image::THUMB_CENTER);
            $img->save($file, 'jpg');
        } else if ($quantity < 100) {
            //$img->thumb(1920, 4000, Image::THUMB_SCALING);
            $img->save($file, 'jpg', $quantity);
        }
        return $file;
    }
}
