<?php
namespace app\common\traits;

use app\common\model\Attachment;
use think\facade\Filesystem;

trait Upload {

    public function uploadHandle($adminId)
    {
        $scene = input('scene', $adminId ? 'admin' : 'user');
        $files = $this->request->file();
        trace($files);
        trace($this->request->param());
        $config = config('upload.' . $scene);
        if ($files) {
            $validate = validate(['file' => 'filesize:' . $config['img']['size']]);
            if (!$validate->check($files)) {
                $this->error($validate->getError());
            }
            $hash = $files['file']->hash('md5');
            $old = Attachment::where('md5', $hash)->find();
            if ($old) {
                $this->success('', [
                    'url' => $old->url,
                    'path' => $old->url,
                ]);
            }
            $type = input('type', 'image');
            $name = Filesystem::disk('public')
                ->putFile(  $type . '/' . date('Ymd'), $files['file'], function() {
                    return uploadFileRule();
                });
            $name = str_replace('\\', '/', $name);
            $fullName = config('filesystem.disks.public.root') . $name;
            $type == 'image' && Attachment::resize($fullName, '', $adminId);
            $ext = explode('.', $fullName);
            $ext = $ext[sizeof($ext) - 1];
            try {
                Attachment::create([
                    'path' => $name,
                    'ext' => substr($ext, 0, 10),
                    'type' => $this->getFileType($ext),
                    'size' => filesize($fullName),
                    'admin_id' => $adminId,
                    'user_id' => 0,
                    'filename' => mb_substr(input('filename', ''), 0, 120),
                    'md5' => $hash,
                ]);
            } catch (\Exception $e) {
                $this->error($e->getMessage());
            }
            $this->success('', [
                'url' => fullDomain($type, '/') . $name,
                'path' => fullDomain($type, '/') . $name,
            ]);
        }
        $this->error('请选择要上传文件');
    }

    protected function getFileType(string $ext): string
    {
        $type = 'file';
        $ext = strtolower($ext);
        switch ($ext) {
            case 'jpg':
            case 'png':
            case 'bmp':
            case 'gif':
            case 'ico':
            case 'jpeg':
                $type = 'image';
                break;
            case 'pdf':
                $type = 'pdf';
                break;
            case 'mp4':
                $type = 'video';
                break;
        }
        return $type;
    }
}
