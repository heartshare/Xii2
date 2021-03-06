<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiCurl (No Print)
 * 
 * 作者: EricXie | 邮箱: keigonec@126.com | 版本: Version 1.0 (2015)
 *
 * 说明: 针对Restful风格接口的Curl封装类, CURLOPT_CUSTOMREQUEST需要服务器支持
 *      
 * Public方法结果返回:
 * 类型: 
 *      Array
 * 格式: 
 *      [
 *          'errorCode' => 0, //失败 0
 *          'errorMsg' => '...', // 文字描述
 *      ]
 *      [
 *          'errorCode' => 1, //成功 1
 *          'data' => mixed(optional) // 数据
 *      ]
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
    const XII_PARAMS_URL = 'url';
    const XII_PARAMS_DATA = 'data';
    const XII_PARAMS_USERAGENT = 'useragent';
    const XII_PARAMS_REF_URL = 'ref_url';
    const XII_PARAMS_METHOD = 'method';
    const XII_PARAMS_TIMEOUT = 'timeout';
    const XII_PARAMS_SET = 'set';
    const XII_PARAMS_INFO = 'info';

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

        if(isset($para[self::XII_PARAMS_USERAGENT]) && !empty($para[self::XII_PARAMS_USERAGENT]))
        {
            curl_setopt($ch, CURLOPT_USERAGENT, $para[self::XII_PARAMS_USERAGENT]);
        }
        else
        {
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        }
        
        if(isset($para[self::XII_PARAMS_URL]) && !empty($para[self::XII_PARAMS_URL]))
        {
            curl_setopt($ch, CURLOPT_URL, $para[self::XII_PARAMS_URL]);
        }
        else
        {
            return ['errorCode' => 0, 'errorMsg' => self::XII_PARAMS_URL . ' is null!'];
        }

        if(isset($para[self::XII_PARAMS_REF_URL]) && !empty($para[self::XII_PARAMS_REF_URL]))
        {
            curl_setopt($ch, CURLOPT_REFERER, $para[self::XII_PARAMS_REF_URL]);
        }

        if(!isset($para[self::XII_PARAMS_DATA]))
        {
            if(!self::$_allowEmptyData)
            {
                return ['errorCode' => 0, 'errorMsg' => self::XII_PARAMS_DATA . ' is not find!'];
            }
            $para[self::XII_PARAMS_DATA] = [];
        }
        else
        {
            if(!is_array($para[self::XII_PARAMS_DATA]))
            {
                return ['errorCode' => 0, 'errorMsg' => self::XII_PARAMS_DATA . ' must be array!'];
            }
        }

        if($usetoken)
        {
            $token = XiiToken::accessApi();
            $para[self::XII_PARAMS_DATA] = array_merge($para[self::XII_PARAMS_DATA], $token);
        }

        if (count($para[self::XII_PARAMS_DATA]) > 0)
        {
            if(isset($para[self::XII_PARAMS_METHOD]) && !empty($para[self::XII_PARAMS_METHOD]) && in_array(strtoupper($para[self::XII_PARAMS_METHOD]), array('PUT', 'DELETE', 'POST')))
            {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($para[self::XII_PARAMS_METHOD]));
                //curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: " . strtoupper($para[self::XII_PARAMS_METHOD])));
                $para[self::XII_PARAMS_DATA] = http_build_query($para[self::XII_PARAMS_DATA]);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $para[self::XII_PARAMS_DATA]);
            }
            else
            {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                //curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: POST"));
                $para[self::XII_PARAMS_DATA] = http_build_query($para[self::XII_PARAMS_DATA]);
                curl_setopt($ch, CURLOPT_URL, $para['url'] . '?' . $para[self::XII_PARAMS_DATA]);
            }
        }
        else
        {
            if(!self::$_allowEmptyData)
            {
                return ['errorCode' => 0, 'errorMsg' => self::XII_PARAMS_DATA . ' is null!'];
            }
        }

        $timeout = isset($para[self::XII_PARAMS_TIMEOUT]) && !empty($para[self::XII_PARAMS_TIMEOUT]) ? intval($para[self::XII_PARAMS_TIMEOUT]) : 10;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        if(isset($para[self::XII_PARAMS_SET]) && !empty($para[self::XII_PARAMS_SET]))
        {
            foreach ($para[self::XII_PARAMS_SET] as $k => $v) 
            {
                curl_setopt($ch, $k, $v);
            }
        }

        if(isset($para[self::XII_PARAMS_INFO]))
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