<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiRequest (No Print)
 *
 * 作者: EricXie | 邮箱: keigonec@126.com | 版本: Version 1.0 (2015)
 *
 * 说明: 信息请求类, 基于XiiCurl类的功能扩展
 *
* Public方法结果返回:
 * 类型: 
 *      Array
 * 格式: 
 *      [
 *          'status' => true, // 成功 true；失败 false
 *          'errorCode' => xxx, // 成功 xxx；失败 xxxx
 *          'errorMsg' => '...', // 文字描述
 *          'data' => mixed(optional) // 数据
 *      ]
 *      [
 *          'status' => true, // 成功 true；失败 false
 *          'errorCode' => xxx, // 成功 xxx；失败 xxxx
 *          'errorMsg' => '...', // 文字描述
 *          'data' => [
 *                      'data' => mixed(optional) // 数据
 *                      'pager' => mixed(optional) //分页（obj | str）
 *                    ]
 *      ]
 *
 * What's new ?
 * Build 20150919
 * - 基于XiiCurl类的GET方式信息请求类
 * - 提供请求前memcache读取功能
 * - 提供请求前redis读取功能
 * - 支持XiiToken内部验证
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
use app\xii\XiiCurl;

class XiiRequest 
{
    const XII_VERSION = 'Xii Request/1.0.0919';

    const MEMCACHE_DURATION = 3600;
    const MSG_NO_STATUS = 'No Status';
    const MSG_NO_ERRORCODE = 'No ErrorCode';
    const MSG_NO_ERRORMSG = 'No ErrorMsg';

    protected static $_getFromMemcache = false;
    protected static $_getFromRedis = false;

    private static $_init = true;
    private static $_para;
    private static $_apiUrl;
    private static $_apiCondition;
    private static $_cacheId;
    private static $_outputData;
    private static $_requestError = [];

    private static $_getConfigYiiParams = 'XiiRequest';
    private static $_getConfigFields = [
                                        '_getFromMemcache',
                                        '_getFromRedis',
                                        ];

    public static function init()
    {
        XiiVersion::run(self::XII_VERSION);

        self::$_outputData = null;

        if(self::$_init)
        {
            self::getConfig();
        }
    }

    public static function run($para, $cacheId = '')
    {
        //init
        self::init();
        self::$_para = $para;
        self::$_cacheId = trim($cacheId);

        //get data
        self::getFromMemcache();
        self::getFromRedis();
        self::getFromApi();

        self::outputData();
        //end
        Yii::$app->end();
    }

    public static function blockConfig()
    {
        self::$_init = false;
    }

    public static function openCache()
    {
        self::blockConfig();
        self::$_getFromMemcache = true;
        self::$_getFromRedis = true;
    }

    public static function onlyMemcache()
    {
        self::blockConfig();
        self::$_getFromMemcache = true;
        self::$_getFromRedis = false;
    }

    public static function onlyRedis()
    {
        self::blockConfig();
        self::$_getFromRedis = true;
        self::$_getFromMemcache = false;
    }

    private static function getFromMemcache()
    {
        if(!self::$_getFromMemcache)
        {
            return;
        }

        if(!empty(self::$_outputData))
        {
            return ;
        }

        if(self::$cacheId == '')
        {
            return;
        }
        
        $servers = Yii::$app->memcache->getServers();
        $memcache = false;
        foreach ($servers as $v)
        {
            $readay = @memcache_connect($v->host, $v->port);
            if($readay)
            {
                $memcache = true;
                break;
            }
        }
        XiiError::ignoreError();

        if(!$memcache)
        {
            self::$_requestError[] = 'Fail to connect MemCache Service';
        }
        else
        {
            self::$_outputData = Yii::$app->memcache->get(self::$_cacheId);
        }
    }

    private static function getFromRedis()
    {
        if(!self::$_getFromRedis)
        {
            return;
        }

        if(!empty(self::$_outputData))
        {
            return ;
        }

        if(self::$cacheId == '')
        {
            return;
        }
        
        $redis = @stream_socket_client(Yii::$app->redis->hostname . ':' . Yii::$app->redis->port, $errno, $errstr, 1);
        XiiError::ignoreError();
        
        if(!$redis)
        {
            self::$_requestError[] = 'Fail to connect Redis Service';
        }
        else
        {
            self::$_outputData = Yii::$app->redis->get(self::$_outputName);
        }
    }

    private static function outputData()
    {
        if(!empty(self::$_requestError))
        {
             $self::$_outputData['status'] = false;
              $self::$_outputData['errorCode'] = 0;
            $self::$_outputData['errorMsg'] = implode(';', self::$_requestError);
        }

        return self::$_outputData;
    }

    private static function getFromApi()
    {
        self::config();
        $tmp = XiiCurl::Run(['url' => self::$_apiUrl , 'data' => self::$_apiCondition]);

        if($tmp['errorCode'])
        {
            self::$_outputData = XiiUtil::JsonDecode($tmp['data']);
        }
        else
        {
            self::$_requestError[] = $tmp['errorMsg'];
        }
    }

    private static function config()
    {
        foreach (self::$_para as $key => $value) 
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