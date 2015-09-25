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
 */
namespace app\xii;
use Yii;
use yii\helpers\Json;

class XiiUtil
{
    const XII_VERSION = 'Xii Util/1.0.0925';

    private static $_implodePlus;

    /*
        Camart专用,外包图片保存地址转换函数,
        这地址的设计除了给自己添乱完全无意义 -_-!!!
    */
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

    /*
        implode之前, 顾名思义, 确保implode操作的pieces不是多维数组
        在implodePlus诞生后，此函数已经无意义了:D
    */
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

    /*
        Json_decode之前使用的函数
        判断一下是否为Json字符串
    */
    public static function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    /*
        implode加强版, 多维数组一样可以implode了, 只是参数顺序跟implode相反了
    */
    public static function implodePlus($pieces, $glue = ',')
    {
        $glue = (is_array($glue)) ? reset($glue) : $glue;
        if($glue != null)
        {
            self::$_implodePlus = $glue;
        }

        if(is_array($pieces))
        {
            return implode(self::$_implodePlus, array_map("self::implodePlus", $pieces, [self::$_implodePlus]));
        }

        return $pieces;
    }

    /*
        多维数组转一维数组
        $ignoreKey = true时, 会忽略原有数组的key值, 将所有value依次保存
        $ignoreKey = false时, 不忽略原有数组的key值, 同key将赋值为最后一个
    */
    public static function multiToSingle($array, $ignoreKey = true)
    {  
        static $feedback = [];
        foreach ($array as $key => $value)
        {  
            if (is_array($value))
            {
                self::multiToSingle($value, $ignoreKey);
            }  
            else
            {
                if($ignoreKey)
                {
                    $feedback[] = $value;
                }
                else
                {
                    $feedback[$key] = $value;
                }
            }   
        }
        return $feedback;
    }

    /*
        Yii Json decode 壳函数
    */
    public static function JsonDecode($json)
    {
        if(self::isJson($json))
        {
            return Json::decode($json);
        }
        else
        {
            return [];
        }
    }
}
?>