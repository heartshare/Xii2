<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiCacheId (No Print)
 *
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 版本: Version 1.0 (2015)
 * 功能: 缓存ID生成类
 * 说明: 为memcache和redis写入与读取，生成ID
 *
 * What's new ?
 * Build 20150923
 * - 实现生成CacheId功能,应用在GET方式，列表和详情
 *
 */

namespace app\xii;  

use Yii;
use yii\web\Response;
use app\xii;
use app\xii\XiiVersion;

class XiiCacheId 
{
    const XII_VERSION = 'Xii CacheId/1.0.0923';

    const ERROR_PARA_EMPTY = -1;
    const ERROR_PARA_NO_URL = -2;
    const ERROR_PARA_NOT_GET = '';

    public static function init()
    {
        XiiVersion::run(self::XII_VERSION);
    }

    public static function run($para = [])
    {
        self::init();
        if(empty($para))
        {
            return self::ERROR_PARA_EMPTY;
        }

        if(isset($para['url']))
        {
            $tmp_url = explode('/',$para['url']);
            $first_key = end($tmp_url);
            if(is_numeric($first_key))
            {
                array_pop($tmp_url);
                $second_key = end($tmp_url);

                return $second_key . '_' . $first_key;
            }

            if(!isset($para['data']))
            {
                return self::ERROR_PARA_NO_URL;
            }

            if(isset($para['method']) && (strtolower(trim($para['method']))!='get'))
            {
                return self::ERROR_PARA_NOT_GET;
            }

            $data = [];
            if(isset($para['data']['page']))
            {
                $data['page'] = $para['data']['page'];
            }

            if(isset($para['data']['pagesize']))
            {
                $data['pagesize'] = $para['data']['pagesize'];
            }

            if(isset($para['data']['condition']))
            {
                $data['condition'] = is_array($para['data']['condition']) ? implode('.', $para['data']['condition']) : $para['data']['condition'];
            }

            //return $first_key . '_' . implode('.', $data);
            return $first_key . '_' . md5(implode('.', $data));
        }
        else
        {
            $first_key = Yii::$app->controller->id;
            if(isset($para['condition']['id']))
            {
                return $second_key . '_' . $para['condition']['id'];
            }

            if(isset($para['page']))
            {
                $data['page'] = $para['page'];
            }

            if(isset($para['limit']))
            {
                $data['limit'] = $para['limit'];
            }

            if(isset($para['condition']))
            {
                $data['condition'] = is_array($para['condition']) ? implode('.', $para['condition']) : $para['condition'];
            }

            //return $first_key . '_' . implode('.', $data);
            return $first_key . '_' . md5(implode('.', $data));
        }
    }
}
?>