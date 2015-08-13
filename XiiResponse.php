<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiResponse
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: 信息反馈类
 * 说明: 
 *
 * What's new ?
 * Ver0.1 Build 20150811
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

class XiiResponse 
{
    const MEMCACHE_DURATION = 3600;
    const MSG_NO_STATUS = 'No Status';
    const MSG_NO_ERRORCODE = 'No ErrorCode';
    const MSG_NO_ERRORMSG = 'No ErrorMsg';
    const CALLBACKFUNC = 'callback';

    public static $CustomerHeader;
    public static $XiiResponseVer = 'Ver0.1 Build 20150811';
    public static $_sendFormat = 'json';
    public static $_jsonpCallback = '';
    public static $_saveToFile = false;
    public static $_saveToMemcache = true;
    public static $_saveToRedis = true;

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
        self::getConfig();
        self::$_sendFormat = strtolower(self::$_sendFormat);
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
        Yii::$app->response->headers->set('XiiResponse Version', self::$XiiResponseVer);

        if(empty(self::$CustomerHeader))
        {
            return;
        }

        if(is_array(self::$CustomerHeader))
        {
            foreach (self::$CustomerHeader as $key => $value)
            {
                Yii::$app->response->headers->set($key, $value);
            }
        }
        else
        {
            Yii::$app->response->headers->set('XiiResponse', self::$CustomerHeader);
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
        XiiError::$errorIgnore = true;

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
        XiiError::$errorIgnore = true;
        
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

        Yii::$app->response->data = self::$_outputData;
        Yii::$app->response->send();
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