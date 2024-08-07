<?php
/**
 *  模板解析缓存
 */

namespace template;

use think\Exception;

include_once "template_cache_include.php";

class TemplateCache
{

    /**
     * @param $template
     * @param $cache
     * @return bool|int
     */

    public function template_compile($template, $cache)
    {
        if (!file_exists($template)) {
            throw new Exception('模板不存在:' . $template);
        }
        $content = @file_get_contents($template);
        if (!is_dir(dirname($cache))) {
            mkdir(dirname($cache), 0777, true);
        }
        $content = $this->template_parse($content);
        // 优化生成的php代码
        $content = str_replace('?><?php', '', $content);
        // 模版编译过滤标签
        $content = strip_whitespace($content);
        //   echo $content;die();
        $strlen = file_put_contents($cache, $content);
        return $strlen;
    }

    /**
     * 更新模板缓存
     *
     * @param string $tplfile    模板原文件路径
     * @param string $compiledtplfile    编译完成后，写入文件名
     * @return $strlen 长度
     */
    public function template_refresh(string $tplfile, string $compiledtplfile)
    {
        $str = @file_get_contents($tplfile);
        $str = $this->template_parse($str);
        $strlen = file_put_contents($compiledtplfile, $str);
        chmod($compiledtplfile, 0777);
        return $strlen;
    }


    /**
     * 解析模板
     *
     * @param string $str    模板内容
     */
    public function template_parse(string $str)
    {
        // 去掉注释
        $str = preg_replace('/([ ]*\<\!\-\-.*?\-\-\>\s*)/s', '', $str);
        $str = preg_replace("/\{template\s+(.+)\}/", "<?php include template(\\1); ?>", $str);
        $str = preg_replace("/\{include\s+(.+)\}/", "<?php include \\1; ?>", $str);
        $str = preg_replace("/\{php\s+(.+)\}/", "<?php \\1?>", $str);
        $str = preg_replace("/\{if\s+(.+?)\}/", "<?php if(\\1) { ?>", $str);
        $str = preg_replace("/\{break\s+(.+?)\}/", "<?php if(\\1) { break; }?>", $str);
        $str = preg_replace("/\{continue\s+(.+?)\}/", "<?php if(\\1) { continue; }?>", $str);
        $str = preg_replace("/\{else\}/", "<?php } else { ?>", $str);
        $str = preg_replace("/\{elseif\s+(.+?)\}/", "<?php } elseif (\\1) { ?>", $str);
        $str = preg_replace("/\{\/if\}/", "<?php } ?>", $str);
        //echo 函数
        $str = preg_replace("/\{:(.+?)\}/", "<?php echo \\1; ?>", $str);
        //for 循环
        $str = preg_replace("/\{for\s+(.+?)\}/", "<?php for(\\1) { ?>", $str);
        $str = preg_replace("/\{\/for\}/", "<?php } ?>", $str);
        //++ --
        $str = preg_replace("/\{\+\+(.+?)\}/", "<?php ++\\1; ?>", $str);
        $str = preg_replace("/\{\-\-(.+?)\}/", "<?php ++\\1; ?>", $str);
        $str = preg_replace("/\{(.+?)\+\+\}/", "<?php \\1++; ?>", $str);
        $str = preg_replace("/\{(.+?)\-\-\}/", "<?php \\1--; ?>", $str);
        $str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\}/", "<?php \$n=1;foreach(\\1 AS \\2) { ?>", $str);
        $str = preg_replace("/\{loop\s+(\S+)\s+(\S+)\s+(\S+)\}/", "<?php \$n=1; if(is_array(\\1)) foreach(\\1 AS \\2 => \\3) { ?>", $str);
        $str = preg_replace("/\{\/loop\}/", "<?php \$n = \$n ?? 0;\$n++;}unset(\$n); ?>", $str);
        $str = preg_replace("/\{([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str);
        $str = preg_replace("/\{\\$([a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff:]*\(([^{}]*)\))\}/", "<?php echo \\1;?>", $str);
        $str = preg_replace("/\{(\\$[a-zA-Z_\x7f-\xff][a-zA-Z0-9_\x7f-\xff]*)\}/", "<?php echo \\1;?>", $str);
        $str = preg_replace_callback("/\{(\\$[a-zA-Z0-9_\[\]\'\"\$\x7f-\xff]+)\}/s", "addquote", $str);
        $str = preg_replace("/\{([A-Z_\x7f-\xff][A-Z0-9_\x7f-\xff]*)\}/s", "<?php echo \\1;?>", $str);
        //常用路径转换
        $tmpl_parse_string = config('template.tmp_parse_string');
        $str = preg_replace_callback("/__([a-zA-Z_]+)__/", function ($matches) use ($tmpl_parse_string) {
            return $tmpl_parse_string[$matches[0]];
        }, $str);

        return preg_replace_callback("/\{\\$([a-zA-Z0-9_-])*(\.([a-zA-Z0-9_-])*)*\}/", function ($matches) {
            $cstr = trim($matches[0], '{');
            $cstr = trim($cstr, '}');
            $cstr = explode('.', $cstr);
            $rstr = $cstr[0];
            unset($cstr[0]);
            foreach ($cstr as $v) {
                $rstr .= "['" . $v . "']";
            }
            return '<?php echo ' . $rstr . ' ?>';
        }, $str);
    }
}

?>
