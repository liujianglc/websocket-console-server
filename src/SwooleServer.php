<?php

namespace WsConsoleServer;

class SwooleServer extends ServerAbstract
{
    /**
     * @var \swoole_websocket_server
     */
    protected $server;

    /**
     * @var \swoole_table
     */
    protected $subscribers;

    public function bind($host, $port, $options = array()) {
        $this->server = new \swoole_websocket_server($host, $port);
        $this->server->set(array(
            'task_worker_num' => 8,
            'daemonize' => $options['daemon'],
            'log_file' => $options['log-file'],
        ));
        $this->server->on('Close', array($this, 'onClose'));
        $this->server->on('Message', function ($server, $frame) {
            $this->process($frame->data, $frame->fd);
        });
        $this->server->on('Task', array($this, 'onTask'));
        $this->server->on('Finish', function ($server, $task_id, $data) {
            Cli::out("Task #$task_id finished.");
        });

        if (! empty($options['tcp-host'])) {
            $this->server->addlistener($options['tcp-host'], $options['tcp-port'], SWOOLE_SOCK_TCP)->set(
                array(
                    'open_length_check' => true,
                    'package_length_type' => 'N',
                    'package_length_offset' => 0,
                    'package_body_offset' => 4,
                    'package_max_length' => 102400,
                )
            );
            $this->server->on('Receive', function ($server, $fd, $from, $data) {
                $this->process(substr($data, 4), $fd);
            });
            Cli::out("Additionally listening on " . sprintf("tcp://%s:%d", $options['tcp-host'], $options['tcp-port']));
        }

        return $this;
    }

    public function run() {
        $this->_initTable();
        $this->server->start();
    }

    public function subscribe($data, $source) {
        $this->subscribers->set($source, array('channel' => $data['channel']));
        if ($data['channel']) {
            $this->response(sprintf("You have subscribed to [%s].", $data['channel']), $source);
        } else{
            $this->response("You have subscribed to ALL channel.", $source);
        }
        Cli::out(sprintf("%s subscribed to [%s]", $source, $data['channel']));
    }

    public function publish($data, $source, $raw = "") {
        Cli::out(sprintf("%s published to [%s]", $source, $data['channel']));
        $this->server->task($raw);
    }

    public function response($message, $source) {
        $this->server->push($source, $this->buildMessage($message));
    }

    public function onClose($server, $fd) {
        if ($this->subscribers->del($fd)) {
            Cli::out($fd . " unsubscribed");
        } else {
            Cli::out($fd . " disconnected");
        }
    }

    public function onTask($server, $task_id, $from, $data) {
        Cli::out("Task #$task_id started.");
        $json = json_decode($data, true);

        foreach ($this->subscribers as $fd => $row) {
            if (empty($json['channel'])
                or empty($row['channel'])
                or (false !== strpos(',' . $row['channel'] . ',', ',' . $json['channel'] . ','))
            ) {
                $this->server->push($fd, $data);
            }
        }

        return true;
    }

    private function _initTable() {
        $this->subscribers = new \swoole_table(4096);
        $this->subscribers->column('channel', \swoole_table::TYPE_STRING, 128);
        if (! $this->subscribers->create()) {
            Cli::error("Failed to call swoole_table::create().");
        }
    }
}
