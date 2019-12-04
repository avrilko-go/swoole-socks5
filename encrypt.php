<?php
/**
 * Created by hebing.
 * Date: 2019-11-28
 * Time: 15:48
 * Email 475721797@qq.com
 */

/**
 * 基于table表加密
 *
 * Class Encrypt
 */
class Encrypt
{
    /**
     * @var array 原始加密数组
     */
    private $data;
    /**
     * @var array key,value反转的加密数组
     */
    private $arr_reverse;

    private $str = "fRB+remQqTY/OlXNa6THL5Ij4yK3QlJM/t3uhv9dxgZGgvDa5mHPVgJf3nRtOE8eMyWah7F7QKG8zH81VBuo2HygGPF49r/DKdKvi3IMMAvRjW+0hWy4mbka7PQIZfzo1ABaXnX6R2STs9xmKEWj1z08wFhcH2eBmxJ5S8p64KsR1oynIZHly56yIIjtsFNQdrZNOwoWQTf9n3DVnarZFFssLURz8lGmF6Uc+3EVSUMOgOvh94ROrAVI37u6l/kZxQ0EbjGP0JYPJsIr28iJjjljSlk04r7BtYouosl3Ewcy7ycDKr0JJJVXlB1orvjzac7nYsQ+6phg5PWcAYNq0w==";

    private static $instance = null;

    public static function instance()
    {
        if (empty(self::$instance)) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public function __construct()
    {

        $strs = base64_decode($this->str);
        for ($i = 0; $i < strlen($strs); $i ++) {
            $arr[] = ord($strs[$i]);
        }

        $this->data = $arr;
        // 将随机数组的字符key,value倒转
        $arr_reverse = [];
        $str = "";
        foreach ($arr as $key => $value) {
            $arr_reverse[$value] = $key;
            $str .= $value;
        }
        $this->arr_reverse = $arr_reverse;
    }

    /**
     * 加密函数
     *
     * @param string
     * @return string
     */
    public function encryptData($data)
    {
        $data = $this->_getByte($data);
        $encrypt_data = [];
        foreach ($data as $value) {
            $encrypt_data[] = $this->data[$value];
        }
        return $this->_getStr($encrypt_data);
    }

    /**
     * 解密函数
     *
     * @param $data
     * @return array
     */
    public function decryptData($data)
    {
        $data = $this->_getByte($data);
        $decrypt_data = [];
        foreach ($data as $value) {
            $decrypt_data[] = $this->arr_reverse[$value];
        }
        return $decrypt_data;
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


