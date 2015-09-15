<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiUser (No Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 版本: Version 1.0 (2015)
 * 功能: 用户相关类
 * 说明: 用于用户登录，权限验证等
 *
 * What's new ?
 * Build 20150915
 * -  完成基本功能
 */
namespace app\xii;
use Yii;
use app\xii\XiiResponse;
use app\xii\XiiCurl;

class XiiUser
{
    const XII_VERSION = 'Xii User/1.0.0915';

    protected static $_inited = false;
    protected static $_model = '';

    protected static $_session;
    protected static $_loginUrl;
    protected static $_loginCondition;

    public static function init()
    {
        XiiVersion::run(self::XII_VERSION);
        
        if(!self::$_inited)
        {
            XiiResponse::run(['status' =>false, 'errorCode' => '410', 'errorMsg' => 'XiiUser未初始化！']);
        }
        self::$_session = Yii::$app->session;
        self::$_session->open();
    }

    public static function config($para)
    {
        self::$_inited = true;
        self::init();
        foreach ($para as $key => $value) 
        {
            if($key == 'url')
            {
                self::$_loginUrl = $value;
            }
            else
            {
                self::$_loginCondition[$key] = $value;
            }
        }
    }

    public static function login()
    {
        self::init();
        $data = self::$_loginCondition;
        $para = array('url' => self::$_loginUrl , 'data' => $data);
        return XiiCurl::Run($para);
    }

    public static function logout()
    {
        self::init();
        self::$_session->destroy();
        self::$_session->close();
    }

    public static function reg()
    {
        self::init();
    }

    public static function getUser()
    {
        self::init();
    }

    public static function saveUser()
    {
        self::init();
    }
}
?>