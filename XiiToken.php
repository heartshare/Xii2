<?php
/*
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiToken (No Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 版本: Version 1.0 (2015)
 * 功能: Api接口令牌生成验证类
 * 说明: 此类的设计目的为确保Api被合法的请求所使用
 *      尤其是在POST,PUT和DELETE操作，操作目标表应设置Token
 *      一项，在插入或者编辑数据时，验证提交的Token是否已经操作过。
 * 定制: 合理修改私钥值
 * 提示: 
 *      1. 返回值为TRUE(验证通过) or FALSE(验证失败或不合法) or 0(验证码超时,未验证)
 *      2. 待加密内容可以为字符串或数组
 *      3. 待验证内容必须为数组，否则返回验证失败
 *      4. 公共静态变量$privateKey用于设置私钥，确保安全性
 *      5. 公共静态变量$tokenIndex用于设置待验证数组中Token存储下标，默认token
 *      6. 可以修改的常量建议只修改口令有效秒数，建议不要太大
 *      7. 令牌长度常量建议不要修改，如需修改，请修改对应源码
 * 
 * What's new ?
 * Build 20150806
 * -  增加函数getConfig,通过设置params中的参数来自定义
 * -  格式要求：'XiiToken' => ['encryptMethod' => 'sha256', 
 *                              'privateKey' => '888888', 
 *                              'tokenIndex'=> 'token', 
 *                              'whereStart' => 1, 
 *                              'timeLimit' => 5],
 * -  使用说明：所有操作前使用 XiiToken::init();
 *
 * Build 20150803
 * -  增加加密方式，由原有MD5增加为MD5,SHA256和SHA512，默认SHA256
 *
 * Build 20150730
 * -  实现验证码生成和验证码验证
 *
 * 示例:
 *      use app\xii;
 *      
 *      $test = ['a','b'];
 *      $test[\app\xii\XiiToken::$tokenIndex] = \app\xii\XiiToken::get($test);
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
use app\xii\XiiVersion;

class XiiToken
{
    const XII_VERSION = 'Xii Token/1.0.0806';

    protected static $_encryptMethod = 'sha256';
    protected static $_privateKey = ''; //私钥值（随意设置，基本无限制）
    protected static $_tokenIndex = 'XII_API_TOKEN'; //Token存储的下标名（默认为token）
    protected static $_whereStart = 1; //截取开始值（0-10）
    protected static $_timeLimit = 10; //口令有效秒数

    //不要动
    private static $_methodAllow = ['sha256', 'sha512', 'md5'];
    const TOKEN_LENGTH = 22 ; //令牌长度
    const DEFAULT_ENCRYPT = 'sha256';

    private static $_init = true;
    private static $_getConfigYiiParams = 'XiiToken';
    private static $_getConfigFields = ['_encryptMethod',
                                        '_privateKey',
                                        '_tokenIndex',
                                        '_whereStart',
                                        '_timeLimit'];

    public static function init()
    {
        XiiVersion::run(self::XII_VERSION);
        if(self::$_init)
        {
            self::getConfig();
        }
    }

    public static function accessApi()
    {
        self::init();
        return [ self::$_tokenIndex => self::get([])];
    }

    public static function getIndex()
    {
        return self::$_tokenIndex;
    }

    public static function get($para)
    {
        self::init();

        $_time = time();
        if(is_array($para))
        {
            $para[] = $_time;
            return self::generateToken($para) . $_time;
        }
        else
        {
            return self::generateToken([$para, $_time]) . $_time;
        }
    }

    public static function verify($para)
    {
        self::init();

        if(is_array($para))
        {
            if(isset($para[self::$_tokenIndex]))
            {
                $_tmp = $para[self::$_tokenIndex];
                unset($para[self::$_tokenIndex]);
            }
            else
            {
                return false;
            }

            $_token = substr($_tmp, 0, self::TOKEN_LENGTH);
            $_time = substr($_tmp, self::TOKEN_LENGTH, 10);
            $_difference  = time() - intval($_time);

            if($_difference > (int)self::$_timeLimit)
            {
                return 0;
            }
            else
            {
                $para[] = $_time;
            }

            return ($_token == self::generateToken($para)) ? true : false;
        }
        else
        {
            return false;
        }
    }

    public static function blockConfig()
    {
        self::$_init = false;
    }

    public static function lodaConfigThenBlock()
    {
        self::init();
        self::blockConfig();
    }

    private static function getConfig()
    {
        if(isset(Yii::$app->params[self::$_getConfigYiiParams]))
        {
            $params = Yii::$app->params[self::$_getConfigYiiParams];

            foreach (self::$_getConfigFields as $v) 
            {
                if(isset($params[$v]))
                {
                    self::$$v = $params[$v];
                }
            }
        }
    }

    private static function generateToken($para)
    {
        self::$_whereStart = (self::$_whereStart > 10) || (self::$_whereStart < 0) ? 1 : (int)self::$_whereStart;

        $_tmp = implode('', $para);
        $_token = self::doEncrypt($_tmp . self::$_privateKey);
        return substr($_token, self::$_whereStart, self::TOKEN_LENGTH);
    }

    private static function doEncrypt($para)
    {
        self::$_encryptMethod = strtolower(self::$_encryptMethod);

        if(in_array(self::$_encryptMethod, self::$_methodAllow))
        {
            return hash(self::$_encryptMethod, $para);
        }
        else
        {
            return hash(self::DEFAULT_ENCRYPT, $para);
        }
    }
}
?>