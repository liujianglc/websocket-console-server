<?php

namespace WsConsoleServer;

use Hoa\Websocket\Server as WsServer;
use Hoa\Socket\Server as SocketServer;
use Hoa\Event\Bucket;

class HoaServer extends ServerAbstract
{
    protected $server;
    protected $subscribers = array();

    public function bind($host, $port, $options = array()) {
        $this->server = new WsServer(new SocketServer(sprintf("ws://%s:%d", $host, $port)));
        $this->server->on('message', function (Bucket $bucket) {
            $data = $bucket->getData();
            $this->process($data['message'], $bucket->getSource());
        });
        $this->server->on("close", function (Bucket $bucket) {
            Cli::out($bucket->getSource()->getConnection()->getCurrentNode()->getId() . " disconnected.");
        });
        return $this;
    }

    public function run() {
        $this->server->run();
    }

    public function subscribe($data, $source) {
        $id = $source->getConnection()->getCurrentNode()->getId();
        $this->subscribers[$id]['channel'] = array_filter(explode(",", $data['channel']));
        $this->response("Welcome!", $source);
        Cli::out(sprintf("%s subscribed to [%s]", $id, $data['channel']));
    }

    public function publish($data, $source, $raw = '') {
        $id = $source->getConnection()->getCurrentNode()->getId();
        $nodes = $source->getConnection()->getNodes();
        foreach ($this->subscribers as $id => $client) {
            if (!isset($nodes[$id])) continue;
            if (empty($data['channel'])
                or !array_key_exists('channel', $client)
                or empty($client['channel'])
                or in_array($data['channel'], $client['channel'])
            ) {
                $source->send($raw, $nodes[$id]);
            }
        }
        Cli::out(sprintf("%s published to [%s]", $id, $data['channel']));
    }

    public function response($message, $source) {
        $source->send($this->buildMessage($message));
    }
}