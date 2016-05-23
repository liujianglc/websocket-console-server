<?php


namespace WsConsoleServer;


abstract class ServerAbstract
{
    /**
     * @var array cmd handler mappings
     */
    protected $handlers;

    public function __construct() {
        $this->registerCmd("ping", array($this, "ping"));
        $this->registerCmd("subscribe", array($this, "subscribe"));
        $this->registerCmd("publish", array($this, "publish"));
    }

    /**
     * @param string $host
     * @param int $port
     * @param array $options
     * @return $this
     */
    abstract public function bind($host, $port, $options = array());

    abstract public function run();

    /**
     * @param array $data
     * @param mixed $source
     */
    abstract public function subscribe($data, $source);

    /**
     * @param array $data
     * @param mixed $source
     */
    abstract public function publish($data, $source);

    /**
     * @param string $message
     * @param mixed $source
     */
    abstract public function response($message, $source);

    /**
     * @param string $cmd
     * @param callable $handler
     */
    protected function registerCmd($cmd, callable $handler) {
        $this->handlers[$cmd] = $handler;
    }

    protected function process($data, $source) {
        if (false === $array = $this->parseMessage($data)) {
            Cli::out("Invalid data.");
            return;
        }

        if (array_key_exists($array['cmd'], $this->handlers)) {
            call_user_func($this->handlers[$array['cmd']], $array, $source, $data);
        } else {
            Cli::out(sprintf("Unknown cmd [%s].", $array['cmd']));
        }
    }

    protected function ping($data, $source) {
        $this->response("Pong!", $source);
    }

    /**
     * @param mixed $content
     * @return string
     */
    protected function buildMessage($content) {
        return json_encode([
            "time" => time(),
            "content" => $content
        ]);
    }

    /**
     * @param $content
     * @return array|false
     */
    protected function parseMessage($content) {
        if (empty($content)) {
            return false;
        }

        $json = json_decode($content, true);

        if ($json === null or json_last_error()) {
            return false;
        }

        return array_merge(array(
            "cmd" => "",
            "channel" => "",
        ), $json);
    }
}