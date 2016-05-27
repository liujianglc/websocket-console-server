# WebSocket Console Server

WebSocket Console Server 是一个简单的WebSocket服务器，它可以在程序和浏览器之间传输数据，
PHP程序连接WebSocket服务器后，将约定格式的数据发送给服务器，再由服务器转发给**所有**已连接的[浏览器客户端](http://php.html.js.cn/console/) ，
就实现了程序数据、日志等信息的实时、集中化展示。

## 使用Composer安装：

```sh
composer create-project -s dev joy2fun/websocket-console-server myserver
```

## 命令行启动WebSocket服务：

```sh
# 进入目录
cd myserver
# 启动服务
./server
```

WebSocket默认服务地址为：`ws://你的IP:9028`，启动后可以访问 [http://php.html.js.cn/console/](http://php.html.js.cn/console/) 
测试连接。

Windows下建议使用Git Bash命令行。

## 更多命令行选项：

|参数|类型|默认值|说明|
|---|---|---|---|
|-d 或 --daemon| | |以守护进程方式运行(需要Swoole扩展)|
|-h 或 --host|String|0.0.0.0|绑定的Host/IP，默认任意IP|
|-p 或 --port|Int|9028|监听端口|
|-s 或 --swoole| | |强制使用Swoole扩展启动服务(需要Swoole扩展)|
|-t 或 --tcp-host|String| |tcp服务绑定的Host/IP|
|--tcp-port|Int|9030|tcp服务监听端口|

## 约定数据格式

PHP程序应该发送以下JSON格式的数据：

```php
json_encode(array(
    'cmd' => 'publish', // 固定值
    'content' => 'test', // 实际内容，可以是数组变量
    'time' => time(), // 可选，时间戳
    'channel' => 'default', // 发送的频道，便于浏览器按频道过滤
));
```

## 使用封装的PHP客户端

[WebSocket Console Client](https://github.com/joy2fun/websocket-console-client) 是一个封装好的PHP类，
可以方便的连接、发送数据到服务器，不需要关心数据格式。

## 启用TCP服务

为了提高性能，WebSocket 服务端还可以追加监听一个TCP服务端口(需要swoole扩展)，允许客户端使用TCP连接并发送数据：

```sh
./server -h 192.168.1.123 -p 9028 -t 192.168.1.123 --tcp-port 9030
```

TCP 发送数据格式为：固定包长(4个字节网络字节序)+包体(json格式数据)，示例如下：

```php
if ($fp = stream_socket_client("tcp://192.168.1.123:9030", $errno, $errstr)) {
    $data = json_encode(array(
        'cmd' => 'publish', // publish 表示广播给客户端
        'content' => 'test', // 内容
    ));
    fwrite($fp, pack("N", strlen($data)).$data);
} else {
    echo "$errstr ($errno)<br />\n";
}
```
