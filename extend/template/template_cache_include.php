<?php
/**
 * 转义 // 为 /
 *
 * @param array $var	转义的字符
 */
function addquote(array $var) :string {
	return str_replace ( "\\\"", "\"", preg_replace ( "/\[([a-zA-Z0-9_\-\.\x7f-\xff]+)\]/s", "['\\1']", '<?php echo ' . $var[1] . ';?>'));
}

function strip_whitespace($content)
{
	$stripStr   = '';
	//分析php源码
	$tokens     = token_get_all($content);
	$last_space = false;
	for ($i = 0, $j = count($tokens); $i < $j; $i++) {
		if (is_string($tokens[$i])) {
			$last_space = false;
			$stripStr  .= $tokens[$i];
		} else {
			switch ($tokens[$i][0]) {
				//过滤各种PHP注释
				case T_COMMENT:
				case T_DOC_COMMENT:
					break;
				//过滤空格
				case T_WHITESPACE:
					if (!$last_space) {
						$stripStr  .= ' ';
						$last_space = true;
					}
					break;
				case T_START_HEREDOC:
					$stripStr .= "<<<THINK\n";
					break;
				case T_END_HEREDOC:
					$stripStr .= "THINK;\n";
					for($k = $i+1; $k < $j; $k++) {
						if(is_string($tokens[$k]) && $tokens[$k] == ';') {
							$i = $k;
							break;
						} else if($tokens[$k][0] == T_CLOSE_TAG) {
							break;
						}
					}
					break;
				default:
					$last_space = false;
					$stripStr  .= $tokens[$i][1];
			}
		}
	}
	return $stripStr;
}
