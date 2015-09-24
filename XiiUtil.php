<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiUtil (No Print)
 * 
 * 作者: EricXie | 邮箱: keigonec@126.com | 版本: Version 1.0 (2015)
 *
 * 说明: 小工具函数整合类
 *
 * Public方法结果返回:
 * 类型: 
 *      Boolean, String, Array
 * 格式: 
 *      不固定
 *
 * What's new ?
 * Build 20150912
 * -  基本工具类
 */
namespace app\xii;
use Yii;

class XiiUtil
{
    const XII_VERSION = 'Xii Util/1.0.0912';

    public static function yafGetPicByHash($hash = '')
    {
        if (substr($hash, 0, 4) == 'http') {
            return $hash;
        }

        if (substr($hash, 0, 1) == '/') {
            return $hash;
            //return 'http://www.camart.cn' . $hash;
        }

        if(empty($hash))
        {
            return 'http://img.camart.cn';
        }
        $url = 'http://img.camart.cn' . "/{$hash[0]}$hash[1]/{$hash[2]}{$hash[3]}/" . $hash . ".jpg";

        return $url;
    }

    public static function beforeImplode($array)
    {
        if(is_array($array))
        {
            foreach ($array as $v)
            {
                if(is_array($v))
                {
                    return false;
                }
            }

            return true;
        }

        return false;
    }

    public static function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    public static function implodeplus($pieces, $glue)
    {
        $glue = (!is_array($glue)) ? [$glue] : $glue;

        if(is_array($pieces))
        {
            return implode(reset($glue), array_map("self::implodeplus", $pieces, $glue));
        }

        return $pieces;
    }
}
?>