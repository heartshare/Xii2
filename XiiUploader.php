<?php
/*
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii Serial Class - XiiUploader
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: 基于Yii2的文件上传类
 * 说明: 对Yii2的UploadedFile以及Xii2的XiiFolder有依赖
 *
 * 版本: Ver0.1 Build 20150803
 * -  实现文件单个或多个（数组形式）上传；自定义目录；年月日8位日期目录；文件大小过滤；类型过滤；文件名sha256处理
 * 
 * 示例: 
 *      需要app\xii\XiiFolder;
 *      use app\xii;
 *      use app\xii\XiiUploader;
 *      $test = XiiUploader::run('file');
 *      var_dump($test);
 *      失败范例：
 *      array (size=2)
 *        'status' => boolean false
 *        'msg' => 
 *          array (size=2)
 *            0 => string 'charles.txt(2kb) is too small!(5k-300k)' (length=39)
 *            1 => string 'snoopy.zip is not valid file type!(Allow:png,jpg,jpeg)' (length=54)
 *      成功范例：
 *       array (size=2)
 *        'status' => boolean true
 *        'file' => 
 *          array (size=2)
 *              0 => string 'uploads/20150803/c9e0fe26af017c3fae71f0e37c52c8c6a9ed623d13f0bcf7384279639fb7a6f3.jpg' (length=85)
 *              1 => string 'uploads/20150803/f793a3f09a6fc6f94554c0c0c92b51d7db6ab2a2e99a079698cbe7de433b48d1.jpg' (length=85)
 */

namespace app\xii;

use Yii;
use yii\web\UploadedFile;
use app\xii\XiiFolder;

class XiiUploader
{
    public static $_PathFolder = 'uploads';
    public static $_PathUseDateFormat = true;
    private static $_PathDateFormatStatus = false;

    public static $_SizeLimit = true;
    public static $_SizeMin = '5k';
    public static $_SizeMax = '300k';
    private static $_SizeUnit = ['kb' => 1000, 'mb' => 1000000];

    public static $_FileTypeLimit = true;
    public static $_FileTypeAllow = ['png', 'jpg', 'jpeg'];
    public static $_FileNameEncrypt = true;

    public static function run($para)
    {
        $attaches = self::PrepareUpload($para);

        if($attaches['status'])
        {
            self::PreparePath();

            $feedback = [];

            foreach($attaches['file'] as $v)
            {
                $tmp_name = self::PrepareFileName($v);
                    
                if($v->saveAs($tmp_name))
                {
                    $feedback[] = $tmp_name;
                }
            }

            $files_num = count($feedback);

            if( $files_num > 0)
            {
                if($files_num == 1)
                {
                    return ['status' => true, 'file' => reset($feedback)];
                }
                else
                {
                    return ['status' => true, 'file' => $feedback];
                }
                
            }
            else
            {
                return ['status' => false, 'msg' => 'Errores occurred during file upload!'];
            }

        }
        else
        {
            return ['status' => false, 'msg' => $attaches['msg']];
        }
    }

    private static function PrepareUpload($para)
    {
        if(isset($_FILES[$para]))
        {
            if(is_array($_FILES[$para]['name']))
            {
                $attaches = UploadedFile::getInstancesByName($para);
            }
            else
            {
                $attach = UploadedFile::getInstanceByName($para);
                if(!empty($attach))
                {
                    $attaches = [$attach];
                }
                else
                {
                    return ['status' => false, 'msg' => 'No file upload'];
                }
            }

            if(!empty($attaches))
            {
                $checkall = true;
                $msg = [];

                foreach ($attaches as $v)
                {
                    $check = self::PrepareCheck($v);
                    if(!$check['status'])
                    {
                        $checkall = false;
                    }
                    $msg[] = $check['msg'];
                }
                if($checkall)
                {
                    return ['status' => true, 'file' => $attaches];
                }
                else
                {
                    return ['status' => false, 'msg' => $msg];
                }
            }
            else
            {
                return ['status' => false, 'msg' => 'No files upload'];
            }

        }
        else
        {
            return ['status' => false, 'msg' => 'No $_FILES named ' . $para];
        }  
    }

    private static function PreparePath()
    {
        if(substr(self::$_PathFolder, -1) != '/')
        {
            self::$_PathFolder .= '/';
        }

        if(self::$_PathUseDateFormat && !self::$_PathDateFormatStatus)
        {
            self::$_PathFolder .= date("Ymd", time()) . '/';
            self::$_PathDateFormatStatus = true;
        }

        self::$_PathFolder = str_replace('//', '/', self::$_PathFolder);

        XiiFolder::mkdir(self::$_PathFolder);
    }

    private static function PrepareFileName($para)
    {
        if(self::$_FileNameEncrypt)
        {
            $salt = substr(md5(uniqid(rand(), true)), 0, 6); 
            $name = hash("sha256", $para->name . $salt);
            return self::$_PathFolder . $name . '.' . $para->extension;
        }
        else
        {
            return self::$_PathFolder . $para->name;
        }
    }

    private static function PrepareCheck($para)
    {
        if(self::$_SizeLimit)
        {
            $min_size = (strpos(self::$_SizeMin, 'm')) ? self::$_SizeMin * self::$_SizeUnit['mb'] : self::$_SizeMin * self::$_SizeUnit['kb'];
            $max_size = (strpos(self::$_SizeMax, 'm')) ? self::$_SizeMax * self::$_SizeUnit['mb'] : self::$_SizeMax * self::$_SizeUnit['kb'];

            $size_notice = self::$_SizeMin . '-' . self::$_SizeMax;
            $file_size = round($para->size / 1000, 0);

            $check_min = ($para->size > $min_size) ? true : false ;
            if(!$check_min)
            {
                return ['status' => false, 'msg' => $para->name . '(' . $file_size .'kb) is too small!(' . $size_notice  . ')'];
            }

            $check_max = ($para->size < $max_size) ? true : false ;
            if(!$check_max)
            {
                return ['status' => false, 'msg' => $para->name . '(' . $file_size . 'kb) is too large!(' . $size_notice  . ')'];
            }
        }
        
        if(self::$_FileTypeLimit)
        {
            $check_type = in_array(strtolower($para->extension), self::$_FileTypeAllow)  ? true : false ;
            if(!$check_type)
            {
                return ['status' => false, 'msg' => $para->name . ' is not valid file type!(Allow:' . implode(',' , self::$_FileTypeAllow). ')'];
            }
        }

        return ['status' => true, 'msg' => $para->name . ' is ready to upload!'];
    }
}
?>