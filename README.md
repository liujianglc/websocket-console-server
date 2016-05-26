# WebSocket Console Server

WebSocket Console Server 可以用来快速搭建 WebSocket 服务器，
方便将应用程序数据、日志等信息转发到指定客户端(如： [Web 浏览器](http://php.html.js.cn/console/) )。


## 使用Composer安装：

```sh
composer create-project -s dev joy2fun/websocket-console-server myserver
```

## 使用命令行启动WebSocket服务：

```sh
# 进入目录
cd myserver
# 启动服务
./server
```

WebSocket默认服务地址为：`ws://你的IP:9028`，启动后可以访问 [http://php.html.js.cn/console/](http://php.html.js.cn/console/) 
测试连接。

## 更多命令行选项：

|参数|类型|默认值|说明|
|---|---|---|---|
|-d 或 --daemon| | |以守护进程来运行(需要Swoole扩展)|
|-h 或 --host|String|0.0.0.0|绑定的Host/IP，默认不限制|
|-p 或 --port|Int|9028|监听端口|
|-s 或 --swoole| | |强制使用Swoole扩展启动服务(需要Swoole扩展)|
|-t 或 --tcp-host|String| |tcp服务绑定的Host/IP|
|--tcp-port|Int|9030|tcp 服务监听端口|

## 启用TCP服务

启用TPC需要swoole扩展。

```sh
./server -h 192.168.1.123 -p 9028 -t 192.168.1.123 --tcp-port 9030
```

为了提高性能，WebSocket 服务端还可以追加监听一个TCP服务端口，支持客户端使用TCP连接并发送数据。
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

## 使用封装的PHP客户端

https://github.com/joy2fun/websocket-console-client
