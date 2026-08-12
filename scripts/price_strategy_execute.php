<?php

declare(strict_types=1);

/**
 * 消费 crawl_notify 待处理通知，并执行所有允许自动执行的改价策略。
 *
 * 规则由 PriceStrategyService 统一处理：
 * - 策略 status=启用；
 * - 策略 auto_run=1；
 * - 策略绑定当前 crawl_notify 的 crawl_target_id。
 *
 * 本脚本不接收也不默认执行某个策略 ID，适合由宝塔计划任务每 30 秒调用一次。
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "该脚本只能通过 PHP CLI 执行\n");
    exit(2);
}

$projectDir = getenv('GAME_PLATFORM_PROJECT') ?: dirname(__DIR__);
$phpBinary = getenv('GAME_PLATFORM_PHP') ?: PHP_BINARY;
$lockFile = getenv('GAME_PLATFORM_EXECUTE_LOCK_FILE')
    ?: '/tmp/game_platform_price_strategy_consume.lock';
$thinkFile = $projectDir . '/think';

if (!is_file($thinkFile)) {
    fwrite(STDERR, "ThinkPHP 命令入口不存在: {$thinkFile}\n");
    exit(2);
}

$lockHandle = @fopen($lockFile, 'c');
if ($lockHandle === false) {
    fwrite(STDERR, "无法打开锁文件: {$lockFile}\n");
    exit(2);
}

if (!flock($lockHandle, LOCK_EX | LOCK_NB)) {
    fwrite(STDOUT, '[' . date('Y-m-d H:i:s') . "] 上一轮仍在执行，跳过本轮\n");
    exit(0);
}

$command = escapeshellarg($phpBinary) . ' ' . escapeshellarg($thinkFile) . ' price:strategy:consume';
$descriptors = [
    0 => ['file', '/dev/null', 'r'],
    1 => ['pipe', 'w'],
    2 => ['pipe', 'w'],
];

$process = proc_open($command, $descriptors, $pipes, $projectDir);
if (!is_resource($process)) {
    fwrite(STDERR, "无法启动 price:strategy:consume\n");
    exit(1);
}

$stdout = stream_get_contents($pipes[1]);
$stderr = stream_get_contents($pipes[2]);
fclose($pipes[1]);
fclose($pipes[2]);
$exitCode = proc_close($process);

if ($stdout !== '') {
    fwrite(STDOUT, $stdout);
}
if ($stderr !== '') {
    fwrite(STDERR, $stderr);
}

exit($exitCode);
