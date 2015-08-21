<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiPassword (No Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: 密码服务类
 * 说明: 基于PHP5.5+版本支持的密码服务封装
 *
 * 版本: Ver0.1 Build 20150821
 * -  密码服务实现
 *
 * 示例：
 *      $pwd = '123456';
 *      $hash = XiiPassword::hash($pwd);
 *      
 *      echo 'hash:'; var_dump($hash);
 *
 *      $verify = XiiPassword::verify($pwd, $hash);
 *
 *      echo 'verify:'; var_dump($verify); //true
 *
 *      XiiPassword::$blockConfig = true;
 *      XiiPassword::$cost = 12;
 *
 *      $hash2 = XiiPassword::rehash($pwd,$hash);
 *
 *      echo 'hash2:'; var_dump($hash2);
 *
 *      $verify = XiiPassword::verify($pwd, $hash2);
 *
 *      echo 'verify:'; var_dump($verify); //true
 *      exit;
 */
namespace app\xii;
use Yii;
use app\xii\XiiVersion;

class XiiPassword
{
    const XII_VERSION = 'XiiPassword/0.1';

    public static $blockConfig = false;
    public static $algo = PASSWORD_DEFAULT;
    public static $salt;
    public static $cost;

    private static $_getConfigYiiParams = 'XiiPassword';
    private static $_getConfigFields = ['algo',
                                        'salt',
                                        'cost'];

    public static function init()
    {
        if(strnatcasecmp(phpversion(), '5.5.0') < 0)
        {
            XiiError::sendError(0,'This Class need PHP 5.5 +');
            Yii::$app->end();
        }

        XiiVersion::run(self::XII_VERSION);
        if(!self::$blockConfig)
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

        if(!empty(self::$salt))
        {
            $option['salt'] = self::$salt; 
        }

        if(!empty(self::$cost))
        {
            $option['cost'] = self::$cost; 
        }

        if(!empty($option))
        {
            if($rehash)
            {
                return password_needs_rehash($para, self::$algo, $option);
            }
            else
            {
                return password_hash($para, self::$algo, $option);
            }
        }
        else
        {
            if($rehash)
            {
                echo $para;
                return password_needs_rehash($para, self::$algo);
            }
            else
            {
                return password_hash($para, self::$algo);
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