# WebSocket Console Server

[![Latest Stable Version](https://img.shields.io/packagist/v/joy2fun/websocket-console-server.svg)](https://packagist.org/packages/joy2fun/websocket-console-server)

WebSocket Console Server 是一个简单的WebSocket服务器，它可以在程序和浏览器之间传输数据，
PHP程序连接WebSocket服务器后，将约定格式的数据发送给服务器，再由服务器转发给**所有**已连接的[浏览器客户端](http://php.html.js.cn/console/) ，
实现程序数据、日志等信息的实时、集中化展示。

## 使用Composer安装

```sh
composer create-project joy2fun/websocket-console-server myserver
# 进入目录
cd myserver
# 启动服务
./server
# Windows环境可以使用PHP完整路径来运行，例如 E:\php\php.exe server
```

到此，一个WebSocket服务器就搭建完成了，默认地址为：`ws://你的IP:9028` 。

接下来可以访问[Web客户端](http://php.html.js.cn/console/)进行连接，接收消息。

## 更多命令行选项

|参数|类型|默认值|说明|
|---|---|---|---|
|-d 或 --daemon| | |以守护进程方式运行(需要Swoole扩展)|
|-h 或 --host|String|0.0.0.0|绑定的Host/IP，默认任意IP|
|-p 或 --port|Int|9028|监听端口|
|-s 或 --swoole| | |强制使用Swoole扩展启动服务|
|-t 或 --tcp-host|String| |tcp服务绑定的Host/IP|
|--tcp-port|Int|9030|tcp服务监听端口|

## 约定数据格式

服务器接受JSON格式的数据如下：

```javascript
{
    "cmd":"publish", /* 固定值，表示推送给客户端 */
    "content": "test data" , /* 推送内容 */
    "time":"", /* 可选，unix时间戳 */ 
    "channel":"" /* 频道，便于客户端过滤展示 */
}
```

## 使用封装的PHP客户端

[WebSocket Console Client](https://github.com/joy2fun/websocket-console-client) 是一个封装好的PHP类，
可以方便的连接、发送数据，用户不需要关心数据格式。

## 启用TCP服务

为了提高性能，WebSocket 服务端还可以追加监听一个TCP服务端口(需要[Swoole](https://github.com/swoole/swoole-src)扩展)，允许客户端使用TCP连接并发送数据：

```sh
./server -h 192.168.1.123 -p 9028 -t 192.168.1.123 --tcp-port 9030
```

TCP 发送数据格式为：固定包长(4个字节网络字节序)+包体(json格式数据)，PHP示例如下：

```php
if ($fp = stream_socket_client("tcp://192.168.1.123:9030", $errno, $errstr)) {
    $data = json_encode(array(
        'cmd' => 'publish',
        'content' => 'test',
    ));
    fwrite($fp, pack("N", strlen($data)).$data);
} else {
    echo "$errstr ($errno)<br />\n";
}
```
