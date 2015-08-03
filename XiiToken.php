<?php
/*
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiToken
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: Api接口令牌生成验证类
 * 说明: 此类的设计目的为确保Api被合法的请求所使用
 *      尤其是在POST,PUT和DELETE操作，操作目标表应设置Token
 *      一项，在插入或者编辑数据时，验证提交的Token是否已经操作过。
 * 定制: 合理修改私钥值
 * 说明: 
 *      1. 返回值为TRUE(验证通过) or FALSE(验证失败或不合法) or 0(验证码超时,未验证)
 *      2. 待加密内容可以为字符串或数组
 *      3. 待验证内容必须为数组，否则返回验证失败
 *      4. 公共静态变量$_Private_Key用于设置私钥，确保安全性
 *      5. 公共静态变量$_Token_Index用于设置待验证数组中Token存储下标，默认token
 *      6. 可以修改的常量建议只修改口令有效秒数，建议不要太大
 *      7. 令牌长度常量建议不要修改，如需修改，请修改对应源码
 * 
 * What's new ?
 * Ver0.2 Build 20150803
 * -  增加加密方式，由原有MD5增加为MD5,SHA256和SHA512，默认SHA256
 *
 * Ver0.1 Build 20150730
 * -  实现验证码生成和验证码验证
 *
 * 示例:
 *      use app\xii;
 *      
 *      $test = ['a','b'];
 *      $test[\app\xii\XiiToken::$_Token_Index] = \app\xii\XiiToken::get($test);
 *      print_r($test);
 *      $a = \app\xii\XiiToken::verify($test);
 *      $test[0] = 'c';
 *      $b = \app\xii\XiiToken::verify($test);
 *      print_r($test);
 *      var_dump($a); //true
 *      var_dump($b); //false
 */

namespace app\xii;
use Yii;

class XiiToken
{
    public static $_EncryptMethod = 'sha256';
    public static $_Private_Key = ''; //私钥值（随意设置，基本无限制）
    public static $_Token_Index = 'token'; //Token存储的下标名（默认为token）

    private static $_MethodAllow = ['sha256', 'sha512', 'md5'];

    //可以动
    const WHERE_START = 1 ; //截取开始值（0-10）
    const TIMELIMIT = 10; //口令有效秒数

    //不要动
    const TOKEN_LENGTH = 22 ; //令牌长度
    const DEFAULT_ENCRYPT = 'sha256';

    public static function Get($para)
    {
        $_time = time();
        if(is_array($para))
        {
            $para[] = $_time;
            return self::GenerateToken($para) . $_time;
        }
        else
        {
            return self::GenerateToken([$para, $_time]) . $_time;
        }
    }

    public static function Verify($para)
    {
        if(is_array($para))
        {
            if(isset($para[self::$_Token_Index]))
            {
                $_tmp = $para[self::$_Token_Index];
                unset($para[self::$_Token_Index]);
            }
            else
            {
                return false;
            }

            $_token = substr($_tmp, 0, self::TOKEN_LENGTH);
            $_time = substr($_tmp, self::TOKEN_LENGTH, 10);
            $_difference  = time() - intval($_time);

            if($_difference > self::TIMELIMIT)
            {
                return 0;
            }
            else
            {
                $para[] = $_time;
            }

            return ($_token == self::GenerateToken($para)) ? true : false;
        }
        else
        {
            return false;
        }
    }

    private static function GenerateToken($para)
    {
        $_tmp = implode('', $para);
        $_token = self::DoEncrypt($_tmp . self::$_Private_Key);
        return substr($_token, self::WHERE_START, self::TOKEN_LENGTH);
    }

    private staTic function DoEncrypt($para)
    {
        self::$_EncryptMethod = strtolower(self::$_EncryptMethod);

        if(in_array(self::$_EncryptMethod, self::$_MethodAllow))
        {
            return hash(self::$_EncryptMethod, $para);
        }
        else
        {
            return hash(self::DEFAULT_ENCRYPT, $para);
        }
    }
}
?>