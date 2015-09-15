<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiResponse (Do Print)
 *
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 版本: Version 1.0 (2015)
 * 功能: 信息反馈类
 * 说明: 基于Yii2 Response类的功能扩展类
 *
 * What's new ?
 * Build 20150915
 * - 针对用户登录方面的使用，增加对data项目的加密开关函数encryptOpen()
 * - 增加加密操作函数doEncrypt()，加密只针对一维数组，多维会自动忽略，不加密
 *
 * Build 20150811
 * - 基于Yii本身功能完成基础的response功能
 * - 支持HTML，XML，JSON和JSONP格式，其他格式暂不计划支持
 * - 支持自定义header信息
 * - 提供response同时，将反馈数据写入memcache和redis
 *
 * What's plan ?
 * - 提供文件反盗链下载
 * - ？？？
 *
 */

namespace app\xii;  

use Yii;
use yii\web\Response;
use app\xii;
use app\xii\XiiError;
use app\xii\XiiVersion;
use app\xii\XiiToken;
use app\xii\XiiUtil;

class XiiResponse 
{
    const XII_VERSION = 'Xii Response/1.0.0811';

    const MEMCACHE_DURATION = 3600;
    const MSG_NO_STATUS = 'No Status';
    const MSG_NO_ERRORCODE = 'No ErrorCode';
    const MSG_NO_ERRORMSG = 'No ErrorMsg';
    const CALLBACKFUNC = 'callback';

    protected static $_customerHeader;
    protected static $_sendFormat = 'json';
    protected static $_jsonpCallback = '';
    protected static $_saveToFile = false;
    protected static $_saveToMemcache = false;
    protected static $_saveToRedis = false;

    private static $_init = true;
    private static $_encrypt = false;
    private static $_tmpData;
    private static $_outputData;
    private static $_outputName;
    private static $_responseError = [];

    private static $_getConfigYiiParams = 'XiiResponse';
    private static $_getConfigFields = ['_sendFormat',
                                        '_jsonpCallback',
                                        '_saveToFile',
                                        '_saveToMemcache',
                                        '_saveToRedis',
                                        ];

    public static function init()
    {
        XiiVersion::run(self::XII_VERSION);

        if(self::$_init)
        {
            self::getConfig();
            self::$_sendFormat = strtolower(self::$_sendFormat);
        }
    }

    public static function run($data, $name = '')
    {
        //init
        self::init();
        self::$_tmpData = $data;
        self::$_outputName = trim($name);

        //send info to client
        self::setFormat();
        self::setCustomerHeader();
        self::processData();
        self::saveToFile();
        self::saveToMemcache();
        self::saveToRedis();
        self::send();

        //end
        Yii::$app->end();
    }

    public static function download()
    {
        self::init();
    }

    public static function encryptOpen()
    {
        self::$_encrypt = true;
    }

    public static function getFormat($format = 'json')
    {
        self::lodaConfigThenBlock();
        self::$_sendFormat = strtolower($format);
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

    private static function setFormat()
    {
        switch (self::$_sendFormat)
        {
            case 'html':
                Yii::$app->response->format = Response::FORMAT_HTML;
                break;

            case 'xml':
                Yii::$app->response->format = Response::FORMAT_XML;
                break;

            case 'jsonp':
                Yii::$app->response->format = Response::FORMAT_JSONP;
                self::$_outputData['callback'] = self::$_jsonpCallback!='' ? self::$_jsonpCallback : self::CALLBACKFUNC;
                self::$_outputData['data'] = [];
                break;

            case 'json':
            default:
                Yii::$app->response->format = Response::FORMAT_JSON;
                break;
        }
    }

    private static function setCustomerHeader()
    {
        if(empty(self::$_customerHeader))
        {
            return;
        }

        if(is_array(self::$_customerHeader))
        {
            foreach (self::$_customerHeader as $key => $value)
            {
                Yii::$app->response->headers->set($key, $value);
            }
        }
        else
        {
            Yii::$app->response->headers->set('XiiResponse', self::$_customerHeader);
        }
    }

    private static function processData()
    {
        self::$_outputData['status'] = isset(self::$_tmpData['status']) ? self::$_tmpData['status'] : self::MSG_NO_STATUS;

        self::$_outputData['errorCode'] = isset(self::$_tmpData['errorCode']) ? self::$_tmpData['errorCode'] : self::MSG_NO_ERRORCODE;

        self::$_outputData['errorMsg'] = isset(self::$_tmpData['errorMsg']) ? self::$_tmpData['errorMsg'] : self::MSG_NO_ERRORMSG;

        if(isset(self::$_tmpData['data']))
        {
            self::$_outputData['data'] = self::$_tmpData['data'];
        }

        Yii::$app->response->data = self::$_outputData;
        
        $formatter = Yii::$app->response->formatters[Yii::$app->response->format];
        $formatter = Yii::createObject($formatter);
        $formatter->format(Yii::$app->response);
    }

    private static function saveToFile()
    {
        if(!self::$_saveToFile)
        {
            return;
        }

        if(empty(self::$_outputData['data']))
        {
            return ;
        }

        if(self::$_outputName == '')
        {
            return;
        }

    }

    private static function saveToMemcache()
    {
        if(!self::$_saveToMemcache)
        {
            return;
        }

        if(empty(self::$_outputData['data']))
        {
            return ;
        }

        if(self::$_outputName == '')
        {
            return;
        }
        
        $memcache = @memcache_connect('localhost', 11211);
        XiiError::ignoreError();

        if(!$memcache)
        {
            self::$_responseError[] = 'Fail to connect MemCache Service';
        }
        else
        {
            Yii::$app->memcache->set(self::$_outputName, Yii::$app->response->content, self::MEMCACHE_DURATION);
        }
    }

    private static function saveToRedis()
    {
        if(!self::$_saveToRedis)
        {
            return;
        }

        if(empty(self::$_outputData['data']))
        {
            return ;
        }

        if(self::$_outputName == '')
        {
            return;
        }
        
        $redis = @stream_socket_client(Yii::$app->redis->hostname . ':' . Yii::$app->redis->port, $errno, $errstr, 1);
        XiiError::ignoreError();
        
        if(!$redis)
        {
            self::$_responseError[] = 'Fail to connect Redis Service';
        }
        else
        {
            Yii::$app->redis->set(self::$_outputName, Yii::$app->response->content);
        }
    }

    private static function send()
    {
        if(!empty(self::$_responseError))
        {
            self::$_outputData['responseError'] = self::$_responseError;
        }

        if(self::$_sendFormat == 'html')
        {
            Yii::$app->response->data = implode(PHP_EOL, self::$_outputData);
        }
        else
        {
            if(self::$_encrypt)
            {
                self::doEncrypt();
            }

            Yii::$app->response->data = self::$_outputData;
        }
        
        Yii::$app->response->send();
    }

    private static function doEncrypt()
    {
        if(Yii::$app->response->format == Response::FORMAT_JSON)
        {
            if(isset(self::$_outputData['data']) && !empty(self::$_outputData['data']))
            {
                if(XiiUtil::beforeImplode(self::$_outputData['data']))
                {
                    self::$_outputData['data'][XiiToken::getIndex()] = XiiToken::get(self::$_outputData['data']);
                }
            }
        }
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