<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiVersion (No Print)
 * 
 * 作者: EricXie | 邮箱: keigonec@126.com | 版本: Version 1.0 (2015)
 *
 * 说明: 版本信息类, 将使用到的Xii组件版本信息输出到HEARDER，便于跟踪与调试
 * 
 * Public方法结果返回: (无页面输出)
 * 类型: 
 *      String
 * 格式: 
 *      输出到HEADER
 *
 * What's new ?
 * Build 20150818
 * -  版本信息统一输出类
 */
namespace app\xii;
use Yii;

class XiiVersion
{
    const XII_VERSION = 'Xii Version/1.0.0818';
    const XII_POWERED_BY = 'Xii2';
    const XII_SERVER = 'Service By Xii';
    const XII_COMPONENTS = 'Xii-Components';

    protected static $_outputXiiSingle = true;

    public static function run($ver = '')
    {
        Yii::$app->response->headers->set('Server', static::XII_SERVER);
        Yii::$app->response->headers->set('X-Powered-By', static::XII_POWERED_BY);

        if(self::$_outputXiiSingle)
        {
            list($name, $value) = explode('/' , static::XII_VERSION);
            Yii::$app->response->headers->set($name, $value);

            if($ver != '')
            {
                $tmp = explode('/' , $ver);
                Yii::$app->response->headers->set($tmp[0], $tmp[1]);
            }
        }
        else
        {
            $xii = Yii::$app->response->headers->get(static::XII_COMPONENTS);

            if(empty($xii))
            {
                Yii::$app->response->headers->set(static::XII_COMPONENTS, static::XII_VERSION);
                $xii = static::XII_VERSION;
            }

            if(strpos($xii, static::XII_VERSION) === false)
            {
                Yii::$app->response->headers->set(static::XII_COMPONENTS, $xii . ' ; ' . static::XII_VERSION);
            }

            if((strpos($xii, $ver) === false) && ($ver !=''))
            {
                Yii::$app->response->headers->set(static::XII_COMPONENTS, $xii . ' ; ' . $ver);
            }
        }

        
    }
}
?>