#!/usr/bin/env php
<?php
ini_set('display_errors', 'on');
require 'vendor/autoload.php';

use WsConsoleServer\Cli;
use GetOptionKit\OptionCollection;
use GetOptionKit\OptionParser;
use GetOptionKit\OptionPrinter\ConsoleOptionPrinter;

$specs = new OptionCollection;
$specs->add('d|daemon', 'Run server as a daemon (require ext-swoole).');
$specs->add('h|host?', 'WebSocket server host.')->isa('string')->defaultValue('0.0.0.0');
$specs->add('l|log-file?', 'Log file to write to when running as a daemon.');
$specs->add('p|port?', 'WebSocket server port.')->isa('number')->defaultValue(9028);
$specs->add('s|swoole', 'Use swoole to run server.');
$specs->add('t|tcp-host?', 'TCP server host (require ext-swoole).')->isa('string');
$specs->add('tcp-port?', 'TCP server port (require ext-swoole).')->isa('number')->defaultValue(9030);
$specs->add('help', 'Print this help.' );

try {
    $option = (new OptionParser($specs))->parse($argv);
} catch (\Exception $ex) {
    Cli::error($ex->getMessage(), 1, false);
}

if ($option->help) {
    Cli::out(
        "Usage: php server.php [options]" . PHP_EOL . PHP_EOL .
        "Options: " . PHP_EOL .
        (new ConsoleOptionPrinter)->render($specs)
    );
} else {
    Cli::out("WebSocket server is running on " . sprintf("ws://%s:%d", $option->host, $option->port));
    if ($option->daemon or $option->swoole or $option->{"tcp-host"}) {
        $server = new WsConsoleServer\SwooleServer;
        $extras = array(
            'tcp-host' => $option->{"tcp-host"},
            'tcp-port' => $option->{"tcp-port"},
            'daemon' => $option->daemon ? 1 : 0,
            'log-file' => $option->{"log-file"},
        );
    } else {
        $server = new WsConsoleServer\HoaServer;
        $extras = array();
    }
    $server->bind($option->host, $option->port, $extras)->run();
}
