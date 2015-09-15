<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiCurl (No Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 版本: Version 1.0 (2015)
 * 功能: 针对Restful风格接口的Curl封装类
 * 说明: 简单的封装，无特殊处理，由Xii第一版修改而来
 *      CURLOPT_CUSTOMREQUEST需要服务器支持
 * 
 * What's new ?
 * Build 20150915
 * -  针对用户登录方面的使用，增加对含有验证码的返回数据的自动验证
 *
 * Build 20150908
 * -  规范了一下代码，不图省事直接修改变量了，改函数方式
 *
 * Build 20150907
 * -  修复一些使用中发现的bug，增加getconfig函数
 *
 * Build 20150818
 * -  实现基本功能
 *
 * 参数:
 * $para参数列表：
 * useragent: 模拟用户代理，默认IE5
 * url: 目标网址，必填
 * ref_url: 来源地址
 * method: 方法，这个参数只支持POST PUT DELETE, 需要其他办法使用 set
 * data:要提交的参数数组，必须为数组格式且必填
 * timeout: 超时设置
 * info: 获取CURL INFO
 * set: set参数会使用curl_setopt去设置
 *      例如$para['set'] = array('CURLOPT_URL' => 'http://www.focus.cn')
 *
 * 示例:
 * echo "<br><br>xii/XiiCurl Test...<br>";
 * $data = array('site' => 1,'page' => 1,'pos' => 75);
 * $para = array('url' => 'http://localhost/adserver/api/ad','data' => $data);
 * $feedback = XiiCurl::Run($para);
 * var_dump($feedback);
 */
namespace app\xii;

use Yii;
use yii\web\Response;
use yii\helpers\Json;
use app\xii\XiiVersion;
use app\xii\XiiToken;
use app\xii\XiiUtil;

class XiiCurl
{
    const XII_VERSION = 'Xii Curl/1.0.0908';
    protected static $_allowEmptyData = true;

    private static $_init = true;
    private static $_getConfigYiiParams = 'XiiCurl';
    private static $_getConfigFields = ['_allowEmptyData',
                                        ];

    public static function init()
    {
        XiiVersion::run(self::XII_VERSION);
        if(self::$_init)
        {
            self::getConfig();
        }
    }

    public static function run($para, $usetoken = true)
    {
        self::init();

        $ch = curl_init();

        if(isset($para['useragent']) && !empty($para['useragent']))
        {
            curl_setopt($ch, CURLOPT_USERAGENT, $para['useragent']);
        }
        else
        {
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        }
        
        if(isset($para['url']) && !empty($para['url']))
        {
            curl_setopt($ch, CURLOPT_URL, $para['url']);
        }
        else
        {
            return ['errorCode' => 0, 'errorMsg' => 'Url is null!'];
        }

        if(isset($para['ref_url']) && !empty($para['ref_url']))
        {
            curl_setopt($ch, CURLOPT_REFERER, $para['ref_url']);
        }

        if(!isset($para['data']))
        {
            if(!self::$_allowEmptyData)
            {
                return ['errorCode' => 0, 'errorMsg' => 'Data is not find!'];
            }
            $para['data'] = [];
        }
        else
        {
            if(!is_array($para['data']))
            {
                return ['errorCode' => 0, 'errorMsg' => 'Data must be array!'];
            }
        }

        if($usetoken)
        {
            $token = XiiToken::accessApi();
            $para['data'] = array_merge($para['data'], $token);
        }

        if (count($para['data']) > 0)
        {
            if(isset($para['method']) && !empty($para['method']) && in_array(strtoupper($para['method']), array('PUT', 'DELETE', 'POST')))
            {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($para['method']));
                //curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: " . strtoupper($para['method'])));
                $para['data'] = http_build_query($para['data']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $para['data']);
            }
            else
            {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                //curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: POST"));
                $para['data'] = http_build_query($para['data']);
                curl_setopt($ch, CURLOPT_URL, $para['url'] . '?' . $para['data']);
            }
        }
        else
        {
            if(!self::$_allowEmptyData)
            {
                return ['errorCode' => 0, 'errorMsg' => 'Data is null!'];
            }
        }

        $timeout = isset($para['timeout']) && !empty($para['timeout']) ? intval($para['timeout']) : 10;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        if(isset($para['set']) && !empty($para['set']))
        {
            foreach ($para['set'] as $k => $v) 
            {
                curl_setopt($ch, $k, $v);
            }
        }

        if(isset($para['info']))
        {
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            return ['errorCode' => 1, 'data' => $info];
        }
        else
        {
            $result = curl_exec($ch);
            curl_close($ch);

            if(XiiUtil::isJson($result))
            {
                $tmp = Json::decode($result);
                if(isset($tmp['data'][XiiToken::getIndex()]))
                {
                    if(!XiiToken::verify($tmp['data']))
                    {
                        return ['errorCode' => 0, 'errorMsg' => 'Response data be modify!']; 
                    }
                }
            }

            return ['errorCode' => 1, 'data' => $result];
        }
        
    }

    public static function allowEmptyData()
    {
        self::$_allowEmptyData = true;
        self::blockConfig();
    }

    public static function disallowEmptyData()
    {
        self::$_allowEmptyData = false;
        self::blockConfig();
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
}