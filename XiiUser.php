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
use yii\helpers\Json;

class XiiUser
{
    const XII_VERSION = 'Xii User/1.0.0915';

    protected static $_inited = false;
    protected static $_session;
    protected static $_sessionId = 'sid';
    protected static $_cookiesRead;
    protected static $_cookiesWrite;

    protected static $_apiUrl;
    protected static $_apiCondition;

    protected static $_fieldId = 'id';
    protected static $_fieldAccount = 'account';
    protected static $_fieldName = 'nickname';
    protected static $_fieldPwd = 'passoword';
    protected static $_fieldAuto = 'autologin';
    protected static $_fieldTimeout = 'expired';
    protected static $_valueTimeout = 3600;

    protected static $_goHomeUrl;
    protected static $_goLoginUrl;
    protected static $_autoLogin = false;

    private static $_getConfigYiiParams = 'XiiUser';
    private static $_getConfigFields = ['_goHomeUrl',
                                        '_goLoginUrl',
                                        '_fieldId',
                                        '_fieldAccount',
                                        '_fieldName',
                                        '_fieldPwd',
                                        '_fieldTimeout',
                                        '_valueTimeout'
                                        ];

    public static function init()
    {
        self::open();
        
        if(!self::$_inited)
        {
            XiiResponse::run(['status' =>false, 'errorCode' => '410', 'errorMsg' => 'XiiUser未初始化！']);
        }
    }

    public static function run($para)
    {
        self::config($para);
        return self::getFromApi();
    }

    public static function get($para)
    {
        self::open();
        return self::getSession($para);
    }

    public static function open()
    {
        XiiVersion::run(self::XII_VERSION);

        self::getConfig();

        self::$_session = Yii::$app->session;
        self::$_session->open();

        self::$_cookiesRead = Yii::$app->request->cookies;
        self::$_cookiesWrite = Yii::$app->response->cookies;
    }

    public static function close($clear = false)
    {
        self::open();

        if($clear)
        {
            self::$_session->destroy();
            self::$_session->close();
            self::$_cookiesWrite->remove(self::$_sessionId);
            self::$_cookiesWrite->remove('PHPSESSID');
        }
        else
        {
            if(!self::getSession(self::$_fieldAuto))
            {
                self::$_session->destroy();
                self::$_session->close();
                self::$_cookiesWrite->remove(self::$_sessionId);
                self::$_cookiesWrite->remove('PHPSESSID');
            }
        }
        
    }

    public static function isLogin()
    {
        self::open();

        $session_id = self::getCookies(self::$_sessionId);

        if($session_id)
        {
            self::$_session->setId($session_id);

            if(time() > self::getSession(self::$_fieldTimeout))
            {
                self::close(true);
                return -11;
            }

            if(!self::getSession(self::$_fieldId))
            {
                return -12;
            }

            if(!self::getSession(self::$_fieldAccount))
            {
                return -13;
            }

            if(!self::getSession(self::$_fieldName))
            {
                return -14;
            }

            return 1;
        }
        else
        {
            if(time() > self::getSession(self::$_fieldTimeout))
            {
                self::close(true);
                return -21;
            }

            if(!self::getSession(self::$_fieldId))
            {
                return -22;
            }

            if(!self::getSession(self::$_fieldAccount))
            {
                return -23;
            }

            if(!self::getSession(self::$_fieldName))
            {
                return -24;
            }

            return 2;
        }
    }

    public static function login($para)
    {
        self::open();

        if(isset($para[self::$_fieldAuto]))
        {
            unset($para[self::$_fieldAuto]);
            self::$_autoLogin = true;
        }

        if(isset($para[XiiCurl::XII_PARAMS_URL]))
        {
            $condition[XiiCurl::XII_PARAMS_URL] = $para[XiiCurl::XII_PARAMS_URL];
        }

        if(isset($para[self::$_fieldAccount]))
        {
            $condition[self::$_fieldAccount] = $para[self::$_fieldAccount];
        }

        //这段密码字段处理针对拍藏项目使用md5，今后改成XiiPassword
        if(isset($para[self::$_fieldPwd]))
        {
            $condition[self::$_fieldPwd] = md5($para[self::$_fieldPwd]);
        }

        $feedback = self::run($condition);

        if($feedback['errorCode'])
        {
            $tmp = Json::decode($feedback['data']);
            if(isset($tmp['status']) && $tmp['status'])
            {
                $session_data = [];

                $session_data[self::$_fieldTimeout] = time() + self::$_valueTimeout;
                $session_data[self::$_fieldAuto] = self::$_autoLogin ? 1 : 0;

                if(isset($tmp['data'][self::$_fieldId]))
                {
                    $session_data[self::$_fieldId] = $tmp['data'][self::$_fieldId];
                }
                if(isset($tmp['data'][self::$_fieldAccount]))
                {
                    $session_data[self::$_fieldAccount] = $tmp['data'][self::$_fieldAccount];
                }
                if(isset($tmp['data'][self::$_fieldName]))
                {
                    $session_data[self::$_fieldName] = $tmp['data'][self::$_fieldName];
                }

                self::setSession($session_data);
                $session_id = self::$_session->getId();
                self::setCookies(['name' => self::$_sessionId, 'value' => $session_id]);

                return ['errorCode' => 1, 'data' => $tmp['data']];
            }
            else
            {
                return ['errorCode' => 0, 'errorMsg' => $tmp['errorMsg']];
            }
        }
        else
        {
            return ['errorCode' => 0, 'errorMsg' => $feedback['errorMsg']];
        }
    }

    public static function goLogin()
    {
        return self::$_goLoginUrl;
    }

    public static function goHome()
    {
        return self::$_goHomeUrl;
    }

    private static function getSession($para)
    {
        if(is_array($para))
        {
            $feedback = [];
            foreach ($para as $v)
            {
                $feedback[$v] = self::$_session->get($v, -1);
            }
            return $feedback;
        }
        else
        {
            return self::$_session->get($para, -1);
        }
    }

    private static function setSession($para = [])
    {
        if(!is_array(($para)))
        {
            return false;
        }

        $feedback = [];

        foreach ($para as $k => $v)
        {
            $feedback[] = self::$_session->set($k, $v);
        }

        return $feedback;
    }

    private static function getCookies($para = [])
    {
        if(is_array($para))
        {
            $feedback = [];
            foreach ($para as $v)
            {
                $feedback[$v] = self::$_cookiesRead->getValue($v, null);
            }
            return $feedback;
        }
        else
        {
            return self::$_cookiesRead->getValue($para, null);
        }
    }

    private static function setCookies($para = [])
    {
        if(!is_array(($para)))
        {
            return false;
        }

        return self::$_cookiesWrite->add(new \yii\web\Cookie($para));
    }

    private static function config($para)
    {
        self::$_inited = true;
        self::init();
        foreach ($para as $key => $value) 
        {
            if($key == 'url')
            {
                self::$_apiUrl = $value;
            }
            else
            {
                self::$_apiCondition[$key] = $value;
            }
        }
    }

    private static function getFromApi()
    {
        return XiiCurl::Run(['url' => self::$_apiUrl , 'data' => self::$_apiCondition]);
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