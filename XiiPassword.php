<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiPassword (No Print)
 * 
 * 作者: EricXie | 邮箱: keigonec@126.com | 版本: Version 1.0 (2015)
 *
 * 说明: 密码服务类, 基于PHP5.5+版本支持的密码服务封装
 *
 * Public方法结果返回: 
 * 类型: 
 *      String, Boolean
 * 格式: 
 *      Password After Hash
 *      False | 0 
 *
 * What's new ?
 * Build 20150821
 * -  密码服务实现
 *
 * 示例：
 *      $pwd = '123456';
 *      $hash = XiiPassword::hash($pwd);
 *      $verify = XiiPassword::verify($pwd, $hash);
 *      echo 'verify:'; var_dump($verify); //true
 *
 *      XiiPassword::blockConfig();
 *      XiiPassword::$cost = 12;
 *      $hash2 = XiiPassword::rehash($pwd,$hash);
 *      $verify = XiiPassword::verify($pwd, $hash2);
 *      echo 'verify:'; var_dump($verify); //true
 */
namespace app\xii;
use Yii;
use app\xii\XiiVersion;

class XiiPassword
{
    const XII_VERSION = 'Xii Password/1.0.0821';

    protected static $_algo = PASSWORD_DEFAULT;
    protected static $_salt;
    protected static $_cost;

    private static $_init = true;
    private static $_getConfigYiiParams = 'XiiPassword';
    private static $_getConfigFields = ['_algo',
                                        '_salt',
                                        '_cost'];

    public static function init()
    {
        XiiVersion::run(self::XII_VERSION);

        if(strnatcasecmp(phpversion(), '5.5.0') < 0)
        {
            XiiError::sendError(0,'This Class need PHP 5.5 +');
            Yii::$app->end();
        }
        
        if(self::$_init)
        {
            self::getConfig();    
        }
    }

    public static function hash($password)
    {
        self::init();

        return self::runHash($password);
    }

    public static function rehash($password, $hash)
    {
        self::init();
        
        if(self::runVerify($password, $hash))
        {
            if(self::runHash($hash, true))
            {
                return self::runHash($password);
            }

            return false;
        }

        return false;
    }

    public static function verify($password, $hash)
    {
        self::init();

        return self::runVerify($password, $hash);
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

    /*
        $rehash = false; $para will be store password
        $rehash = true; $para will be store hash
    */
    private static function runHash($para, $rehash = false)
    {
        if(empty($para))
        {
            return false; 
        }

        $option = [];

        if(!empty(self::$_salt))
        {
            $option['salt'] = self::$_salt; 
        }

        if(!empty(self::$_cost))
        {
            $option['cost'] = self::$_cost; 
        }

        if(!empty($option))
        {
            if($rehash)
            {
                return password_needs_rehash($para, self::$_algo, $option);
            }
            else
            {
                return password_hash($para, self::$_algo, $option);
            }
        }
        else
        {
            if($rehash)
            {
                echo $para;
                return password_needs_rehash($para, self::$_algo);
            }
            else
            {
                return password_hash($para, self::$_algo);
            }
        }
    }

    private static function runVerify($password, $hash)
    {
        if(empty($password) || empty($hash))
        {
            return false; 
        }

        return password_verify($password, $hash);
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
}
?>