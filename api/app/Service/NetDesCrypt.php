<?php
/**
 *  DES加密解密
 *@author   carson
 */
namespace Service;

class NetDesCrypt{
    private $key;
    private $iv; //偏移量
    private $type=MCRYPT_3DES;
    private $mode=MCRYPT_MODE_ECB;

    function setKey( $key, $iv=0 ) {
        //key长度8例如:1234abcd
        $this->key = $key;
        $this->iv=mcrypt_create_iv(mcrypt_get_iv_size($this->type,$this->mode),MCRYPT_RAND);
        /*if( $iv == 0 ) {
            $this->iv = $key; //默认以$key 作为 iv
        } else {
            $this->iv = $iv; //mcrypt_create_iv ( mcrypt_get_block_size ($this->type, MCRYPT_MODE_CBC), MCRYPT_DEV_RANDOM );
        }*/
    }

    function encrypt($str) {
        //加密，返回大写十六进制字符串
        $str = $this->pkcs5Pad($str);
        //return bin2hex( mcrypt_cbc($this->type, $this->key, $str, MCRYPT_ENCRYPT, $this->iv ) );
        $oneStr=bin2hex(mcrypt_encrypt($this->type, $this->key, $str, $this->mode, $this->iv));

        /*var_dump($oneStr);
        echo '<br>';
        $twoStr=$this->decrypt($oneStr);
        var_dump($twoStr);*/
        return $oneStr;
    }

    //解密
    function decrypt($str) {
        $strBin = $this->hex2bin( $str );
        $str = mcrypt_decrypt( $this->type, $this->key, $strBin, $this->mode, $this->iv );
        $str = $this->pkcs5Unpad( $str );
        $str = urldecode($str);
        return $str;
    }

    function hex2bin($hexData) {
        $binData = "";
        for($i = 0; $i < strlen ( $hexData ); $i += 2) {
            $binData .= chr ( hexdec ( substr ( $hexData, $i, 2 ) ) );
        }
        return $binData;
    }

    function pkcs5Pad($text) {
        $blocksize = mcrypt_get_block_size ( $this->type, $this->mode );
        $pad = $blocksize - (strlen ( $text ) % $blocksize);
        return $text . str_repeat ( chr ( $pad ), $pad );
    }

    function pkcs5Unpad($text) {
        $pad = ord ( $text {strlen ( $text ) - 1} );
        if ($pad > strlen ( $text ))
            return false;
        if (strspn ( $text, chr ( $pad ), strlen ( $text ) - $pad ) != $pad)
            return false;
        return substr ( $text, 0, - 1 * $pad );
    }

    function pkcs7Pad($source){
        $block = mcrypt_get_block_size($this->type,$this->mode);
        //var_dump($block);
        $dif=strlen($source)%$block;
        //var_dump($dif);
        $pad = $block - $dif;
        //var_dump($pad);
        if($pad <= $block) {
            $char = chr($pad);
            //echo ord($char).'<br>';
            //var_dump($char);
            $source .= str_repeat($char, $pad);
        }
        //var_dump($source);
        //die();
        return $source;
    }
}