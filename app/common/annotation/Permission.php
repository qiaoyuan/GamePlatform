<?php
declare (strict_types = 1);

namespace app\common\annotation;

use app\common\model\AdminPermission;
use Attribute;
use think\helper\Arr;

#[Attribute(Attribute::TARGET_METHOD | Attribute::TARGET_CLASS)]
class Permission
{
    public int $parentId = 0;
    public int $level = 1;
    public int $id = 0;

    public function __construct(
        public string $title,
        public int $isMenu = 0,
        public int $isHide = 0,
        public string $parentUrl = '',
        public int $isHideSub = 0,
        public int $sort = 0,
        public string $url = '',
    )
    {
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function setParentUrl(string $parentUrl): static
    {
        $this->parentUrl = $parentUrl;
        return $this;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getData(): array
    {
        if (!$this->parentId && $this->parentUrl) {
            $parent = AdminPermission::where('url', $this->parentUrl)->field('id,level')->find();
            if ($parent) {
                $this->parentId = $parent->id;
                $this->level = $parent->level + 1;
            }
        }
        return [
            'title' => $this->title,
            'parent_id' => $this->parentId,
            'sort' => $this->sort,
            'url' => $this->url,
            'is_hide' => $this->isHide,
            'is_menu' => $this->isMenu,
            'is_hide_sub' => $this->isHideSub,
            'level' => $this->level,
            'status' => 1,
        ];
    }

    public function save(): AdminPermission | null
    {
        if ($this->url) {
            $p = AdminPermission::where('url', $this->url)->find();
            if ($p) {
                $p->save(Arr::only($this->getData(), ['title', 'parent_id', 'is_hide', 'is_menu', 'is_hide_sub', 'level', 'status']));
            } else {
                 $p = AdminPermission::create($this->getData());
            }
            $this->id = $p->id;
            return $p;
        }
        return null;
    }

    public function getPermission(): AdminPermission | null
    {
        if ($this->url) {
            return AdminPermission::where('url', $this->url)->find();
        }
        return null;
    }

    public function isUpdate(): bool
    {
        return !!$this->id;
    }
}