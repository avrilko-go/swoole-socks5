<?php
/**
 * Created by hebing.
 * Date: 2019-12-04
 * Time: 09:08
 * Email 475721797@qq.com
 */

use Swoole\Coroutine\Server;
use Swoole\Coroutine\Server\Connection;
require_once 'encrypt.php';

class Servers
{
    public function __construct()
    {
        go(function () {
            $server = new \Swoole\Coroutine\Server('0.0.0.0', 9002, false);
            $server->handle(function (Connection $conn) use ($server) {
                $this->_handleConn($server, $conn);
            });
            $server->start();
        });
    }

    private function _handleConn(\Swoole\Coroutine\Server $server, Connection $conn)
    {
        $data = $conn->recv();
        $data = Encrypt::instance()->decryptData($data);
        if (empty($data)) {
            var_dump("从客户端读取host和端口失败");
            $conn->close();
            return;
        }
        $data = $this->_getStr($data);
        [$host,$port] = explode(':', $data);
        if (empty($host) || empty($port)) {
            var_dump("host port解析失败");
            $conn->close();
            return;
        }

        $dis = new Swoole\Coroutine\Socket(AF_INET, SOCK_STREAM, 0);
        $result = $dis->connect($host, intval($port));
        if (empty($result)) {
            var_dump("链接实际目标服务器失败");
            $conn->close();
            return;
        }
        $result = $conn->send($this->_getStr([0x01]));
        if (empty($result)) {
            var_dump("发送至客户端鉴权失败");
            $conn->close();
            return;
        }

        $this->_ioCopyDe($dis, $conn);


        // 加密
        while (true) {
            $data = $this->_getByte($dis->recv());
            if (empty($data)) {
                $conn->close();
                $dis->close();
                return;
            }

            $data = Encrypt::instance()->encryptData($this->_getStr($data));
            $result = $conn->send($data);
            if (empty($result)) {
                var_dump("服务端转发目标服务器流量到客户端失败");
                $conn->close();
                $dis->close();
                return;
            }
        }
    }

    private function _ioCopyDe($dis, $conn)
    {
        go(function () use ($dis,$conn){
            while (true) {
                $data = $this->_getByte($conn->recv());
                if (empty($data)) {
                    $conn->close();
                    $dis->close();
                    return;
                }
                $data = Encrypt::instance()->decryptData($this->_getStr($data));
                $result = $dis->send($this->_getStr($data));
                if (empty($result)) {
                    var_dump("服务端实际传输数据到目标服务器失败");
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

(new Servers());