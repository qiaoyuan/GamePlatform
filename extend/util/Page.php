<?php

namespace util;

use think\paginator\driver\Bootstrap;

class Page extends Bootstrap
{
    public function render()
    {
        if ($this->hasPages()) {
            if ($this->simple) {
                return sprintf(
                    '<ul class="pager">%s %s</ul>',
                    $this->getPreviousButton('上一页'),
                    $this->getNextButton('下一页')
                );
            } else {
                return sprintf(
                    '%s %s %s',
                    $this->getPreviousButton('上一页'),
                    $this->getLinks(),
                    $this->getNextButton('下一页')
                );
            }
        }
        return  '';
    }

    protected function getAvailablePageWrapper(string $url, string $page): string
    {
        return '<a href="' . htmlentities($url) . '">' . $page . '</a>';
    }

    protected function getDisabledTextWrapper(string $text): string
    {
        return '<a href="javascript:;">' . $text . '</a>';
    }

    protected function getActivePageWrapper(string $text): string
    {
        return '<span class="w-active">' . $text . '</span>';
    }

    protected function getPreviousButton(string $text = "&laquo;"): string
    {
        if ($this->currentPage() <= 1) {
            return '';
        }
        return parent::getPreviousButton($text);
    }

    protected function getNextButton(string $text = '&raquo;'): string
    {
        if (!$this->hasMore) {
            return '';
        }
        return parent::getNextButton($text);
    }
}
