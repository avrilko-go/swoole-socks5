<?php
/**
 * Created by hebing.
 * Date: 2019-12-04
 * Time: 08:50
 * Email 475721797@qq.com
 */
use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;

require_once 'encrypt.php';

class Client
{
    public function __construct()
    {
        go(function () {
            $server = new Server('0.0.0.0', 9001, false);
            $server->handle(function (Connection $conn) use ($server) {
                $this->_handleConn($server, $conn);
            });
            $server->start();
        });
    }

    private function _handleConn(Server $server, Connection $conn)
    {
        $data = $this->_getByte($conn->recv());
        if ($data[0] != 0x05) {
            var_dump('不是socks5协议');
            return;
        }
        $result = $conn->send($this->_getStr([0x05,0x00]));
        if (empty($result)) {
            var_dump('消息发送失败');
            return;
        }

        $data = $this->_getByte($conn->recv());
        if ($data[0] != 0x05) {
            var_dump('不是socks5协议');
            return;
        }

        $type = $data[3];
        if ($type == 0x03) { // host的访问方式
            $host_len = $data[4];
            $host_arr = array_slice($data, 5, $host_len);
            $port = unpack('n', $this->_getStr(array_slice($data, -2)));
            $host_str = $this->_getStr($host_arr);
            $dis = new Swoole\Coroutine\Socket(AF_INET, SOCK_STREAM, 0);
            $result = $dis->connect("47.52.205.31", 9002);
            if (empty($result)) {
                var_dump("链接远程服务失败{$host_str}:{$port[1]}");
                $conn->close();
                return;
            }

            // 发送host和端口号给远程服务器
            $host_addr = "{$host_str}:{$port[1]}";
            $result = $dis->send(Encrypt::instance()->encryptData($host_addr));
            if (empty($result)) {
                var_dump("发送给远端服务器失败");
                $conn->close();
                return;
            }

            $data = $this->_getByte($dis->recv());
            if (empty($data) || $data[0] != 0x01) {
                var_dump("客户端与服务端鉴权失败");
                $conn->close();
                $dis->close();
                return;
            }

            // 回复成功
            $send_success = [0x05, 0x00, 0x00, 0x01, 0x00, 0x00, 0x00, 0x00, 0x00, 0x00];
            $conn->send($this->_getStr($send_success));
            $this->_ioCopyEn($dis, $conn);

            // 解密
            while (true) {
                $data = $this->_getByte($dis->recv());
                if (empty($data)) {
                    $conn->close();
                    $dis->close();
                    return;
                }

                $data = Encrypt::instance()->decryptData($this->_getStr($data));
                $result = $conn->send($this->_getStr($data));
                if (empty($result)) {
                    var_dump("转发到浏览器流量失败");
                    $conn->close();
                    $dis->close();
                    return;
                }
            }
        }
    }


    private function _ioCopyEn($dis,$conn)
    {
        go(function () use ($dis,$conn){
            while (true) {
                $data = $this->_getByte($conn->recv());
                if (empty($data)) {
                    $conn->close();
                    $dis->close();
                    return;
                }
                $data = Encrypt::instance()->encryptData($this->_getStr($data));
                $result = $dis->send($data);
                if (empty($result)) {
                    var_dump("客户端加密数据发送到服务端失败");
                    $conn->close();
                    $dis->close();
                    return;
                }
            }
        });
    }


    /**
     * 将字符创转为byte数组
     *
     * @param string $str
     *
     * @return array
     */
    private function _getByte(string $str) :array
    {
        $arr = [];
        for ($i = 0; $i < strlen($str); $i++) {
            $arr[] = ord($str[$i]);
        }

        return $arr;
    }

    /**
     * 将byte数组转为字符串
     *
     * @param array $data
     *
     * @return string
     */
    private function _getStr(array $data) :string
    {
        $str = "";
        foreach ($data as $value) {
            $str.= chr($value);
        }

        return  $str;
    }
}

(new Client());
