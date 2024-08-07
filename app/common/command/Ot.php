<?php
namespace app\common\command;

use think\console\Output;

trait Ot
{
    /**
     * @param string $text
     * @param int $color 1红色，2绿色，3黄色，4蓝色，5紫色
     * @return string
     */
    protected function textColor(string $text, int $color): string
    {
        if ($color == Base::COLOR_INFO) {
            return $text;
        }
        if (str_contains(PHP_OS, 'WIN')) {
            return "\033[38;5;" . $color . ";4m" . $text . "\033[0m";
        } else {
            return "\033[40;3" . $color . "m" . $text . "\033[0m";
        }
    }

    protected function outputSuccess(Output $output, string $msg)
    {
        $output->writeln($this->textColor($msg, Base::COLOR_SUCCESS));
    }

    protected function outputWarning(Output $output, string $msg)
    {
        $output->writeln($this->textColor($msg, Base::COLOR_WARNING));
    }

    protected function outputUpdate(Output $output, string $msg)
    {
        $output->writeln($this->textColor($msg, Base::COLOR_UPDATE));
    }

    protected function outputError(Output $output, string $msg)
    {
        $output->writeln($this->textColor($msg, Base::COLOR_ERROR));
    }
}
