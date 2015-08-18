<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiVersion (No Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: 版本信息类
 * 说明: 将使用的类信息输出到header中，便于调试
 *
 * 版本: Ver0.1 Build 20150818
 * -  版本信息统一输出类
 */
namespace app\xii;
use Yii;

class XiiVersion
{
    const XII_VERSION = 'XiiVersion/0.1';
    const XII_POWERED_BY = 'Xii2';
    const XII_SERVER = 'Service By Xii';

    public static function run($ver = '')
    {
        Yii::$app->response->headers->set('Server', static::XII_SERVER);
        Yii::$app->response->headers->set('X-Powered-By', static::XII_POWERED_BY);

        $xii = Yii::$app->response->headers->get('Xii');

        if(empty($xii))
        {
            Yii::$app->response->headers->set('Xii', static::XII_VERSION);
            $xii = static::XII_VERSION;
        }

        if(strpos($xii, static::XII_VERSION) === false)
        {
            Yii::$app->response->headers->set('Xii', $xii . ';' . static::XII_VERSION);
        }

        if((strpos($xii, $ver) === false) && ($ver !=''))
        {
            Yii::$app->response->headers->set('Xii', $xii . ';' . $ver);
        }
    }
}
?>