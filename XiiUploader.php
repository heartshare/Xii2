<?php
/*
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii Serial Class - XiiUploader (No Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 版本: Version 1.0 (2015)
 * 功能: 基于Yii2的文件上传类
 * 说明: 对Yii2的UploadedFile以及Xii2的XiiFolder有依赖
 *
 * What's new ?
 * Build 20150806
 * - 增加函数getConfig,通过设置params中的参数来自定义
 * - 格式要求：    'XiiUploader' => ['_pathFolder' => 'uploads', 
 *                                  '_pathUseDateFormat' => true,
 *                                  '_sizeLimit' => true,
 *                                  '_sizeMin' => '5k',
 *                                  '_sizeMax' => '300k', 
 *                                  '_fileTypeLimit' => true,
 *                                  '_fileTypeAllow' => ['png', 'jpg', 'jpeg', 'gif'],
 *                                  '_fileNameEncrypt' => true, 
 *                                  '_thumbnailNeed' => true,
 *                                  '_thumbnailNeedOff' => 'Thumbnail is Off!',
 *                                  '_thumbnailSameType' => true,
 *                                  '_thumbnailPercent' => 5, 
 *                                  '_thumbnailWidth' => 0,
 *                                  '_thumbnailHeight' => 0, 
 *                                  '_thumbnailSuffix' => '_params_thumb',],
 * - 使用说明：所有操作前使用 XiiUploader::init();
 *
 * Build 20150803
 * -  增加缩略图上传功能，缩略图保存路径和原图完全一致，只是缩略图增加了_thumb后缀（可自定义）
 * -  例如：原图：uploads/20150803/abcdef.jpg；缩略图：uploads/20150803/abcdef_thumb.jpg
 *
 * Build 20150803
 * -  实现文件单个或多个（数组形式）上传；自定义目录；年月日8位日期目录；文件大小过滤；类型过滤；文件名sha256处理
 * 
 * 示例: 
 *      需要app\xii\XiiFolder;
 *      use app\xii;
 *      use app\xii\XiiUploader;
 *
 *      //如果报错400，controller增加一行代码public $enableCsrfValidation = false;
 *
 *      //XiiUploader::$_thumbnailNeed = false;
 *      XiiUploader::$_thumbnailPercent = 0.1;
 *      //XiiUploader::$_thumbnailWidth = 100;
 *      //XiiUploader::$_thumbnailHeight = 100;
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
 *            0 => string 'uploads/20150803/9897d4d618dee40468125f93264db10ed43a25ca1df8f69f23bc6a6aed3a9518.jpg' (length=85)
 *            1 => string 'uploads/20150803/61488e85357daee8378c2a8ac77cc5bcb39dd7fcce9234650628a7e815d330ac.png' (length=85)
 *        'thumb' => 
 *          array (size=2)
 *            0 => string 'uploads/20150803/9897d4d618dee40468125f93264db10ed43a25ca1df8f69f23bc6a6aed3a9518_thumb.jpg' (length=91)
 *            1 => string 'uploads/20150803/61488e85357daee8378c2a8ac77cc5bcb39dd7fcce9234650628a7e815d330ac_thumb.png' (length=91)
 */

namespace app\xii;

use Yii;
use yii\web\UploadedFile;
use app\xii\XiiFolder;
use app\xii\XiiVersion;

class XiiUploader
{
    const XII_VERSION = 'Xii Uploader/1.0.0806';

    protected static $_pathFolder = 'uploads'; //文件上传保存目录，可以带/，也可以不带
    protected static $_pathUseDateFormat = true; //是否在保存目录中自动建立20150808这样的日期目录
    private static $_pathDateFormatStatus = false; //用于判断日期目录是否已经建立

    protected static $_sizeLimit = true; //是否对文件大小进行限制
    protected static $_sizeMin = '5k'; //最小文件
    protected static $_sizeMax = '300k'; //最大文件
    private static $_sizeUnit = ['kb' => 1000, 'mb' => 1000000]; //根据单位换算大小

    protected static $_fileTypeLimit = true;  //是否对文件类型进行限制
    protected static $_fileTypeAllow = ['png', 'jpg', 'jpeg', 'gif']; //允许的文件类型后缀名
    protected static $_fileNameEncrypt = true; //是否使用sha256修改上传文件的名字

    protected static $_thumbnailNeed = true; //是否生成缩略图
    protected static $_thumbnailNeedOff = 'Thumbnail is Off!'; //缩略图关闭的提示语
    protected static $_thumbnailSameType = true; //缩略图是否与原图同类型
    protected static $_thumbnailPercent = 5; //是否按照比例缩小原图，设置范围 1 > x >= 0
    protected static $_thumbnailWidth = 0; //缩略图宽度设置
    protected static $_thumbnailHeight = 0; //缩略图高度设置
    protected static $_thumbnailSuffix = '_thumb'; //缩略图文件名后缀，同目录保存，增加后缀

    /*
    缩略图，缩小尺寸优先级说明：
    $_thumbnailPercent > 0; 按照设置比例确定宽度和高度生成缩略图
    $_thumbnailPercent = 0; 按照缩略图宽度和高度生成缩略图
    宽度和高度都不为0时，按照设置宽度高度生成缩略图
    宽度不为0，高度为0，以宽度缩小比例设置高度，生成缩略图
    高度不为0，宽度为0，以高度缩小比例设置高度，生成缩略图
    三个设置都为0，则等同于不生成缩略图
    */
    private static $_init = true;
    private static $_getConfigYiiParams = 'XiiUploader';
    private static $_getConfigFields = ['_pathFolder',
                                        '_pathUseDateFormat',
                                        '_sizeLimit',
                                        '_sizeMin',
                                        '_sizeMax',
                                        '_fileTypeLimit',
                                        '_fileTypeAllow',
                                        '_fileNameEncrypt',
                                        '_thumbnailNeed',
                                        '_thumbnailNeedOff',
                                        '_thumbnailSameType',
                                        '_thumbnailPercent',
                                        '_thumbnailWidth',
                                        '_thumbnailHeight',
                                        '_thumbnailSuffix',];

    public static function init()
    {
        XiiVersion::run(self::XII_VERSION);

        if(self::$_init)
        {
            self::getConfig();
        }
    }

    public static function run($para)
    {
        self::init();

        $attaches = self::prepareUpload($para);

        if($attaches['status'])
        {
            self::preparePath();

            $feedback = [];
            $thumbs = self::$_thumbnailNeed ? [] : self::$_thumbnailNeedOff;

            foreach($attaches['file'] as $v)
            {
                $tmp_name = self::prepareFileName($v);
                    
                if($v->saveAs($tmp_name))
                {
                    if(self::$_thumbnailNeed)
                    {
                        $thumb = self::createThumbnail($tmp_name);
                        $thumbs[] = $thumb['status'] ? $thumb['file'] : $thumb['msg'];
                    }
                    $feedback[] = $tmp_name;
                }
            }

            $files_num = count($feedback);

            if( $files_num > 0)
            {
                if($files_num == 1)
                {
                    return ['status' => true, 'file' => reset($feedback) , 'thumb' => $thumbs];
                }
                else
                {
                    return ['status' => true, 'file' => $feedback , 'thumb' => $thumbs];
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

    public static function check($para)
    {
        self::init();
        //name, size , extension
        return self::prepareCheck($para);
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

    private static function prepareUpload($para)
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
                    $check = self::prepareCheck($v);
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

    private static function preparePath()
    {
        if(substr(self::$_pathFolder, -1) != '/')
        {
            self::$_pathFolder .= '/';
        }

        if(self::$_pathUseDateFormat && !self::$_pathDateFormatStatus)
        {
            self::$_pathFolder .= date("Ymd", time()) . '/';
            self::$_pathDateFormatStatus = true;
        }

        self::$_pathFolder = str_replace('//', '/', self::$_pathFolder);

        XiiFolder::mkdir(self::$_pathFolder);
    }

    private static function prepareFileName($para)
    {
        if(self::$_fileNameEncrypt)
        {
            $salt = substr(md5(uniqid(rand(), true)), 0, 6); 
            $name = hash("sha256", $para->name . $salt);
            return self::$_pathFolder . $name . '.' . $para->extension;
        }
        else
        {
            return self::$_pathFolder . $para->name;
        }
    }

    private static function prepareCheck($para)
    {
        if(self::$_sizeLimit)
        {
            $min_size = (strpos(self::$_sizeMin, 'm')) ? self::$_sizeMin * self::$_sizeUnit['mb'] : self::$_sizeMin * self::$_sizeUnit['kb'];
            $max_size = (strpos(self::$_sizeMax, 'm')) ? self::$_sizeMax * self::$_sizeUnit['mb'] : self::$_sizeMax * self::$_sizeUnit['kb'];

            $size_notice = self::$_sizeMin . '-' . self::$_sizeMax;
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
        
        if(self::$_fileTypeLimit)
        {
            $check_type = in_array(strtolower($para->extension), self::$_fileTypeAllow)  ? true : false ;
            if(!$check_type)
            {
                return ['status' => false, 'msg' => $para->name . ' is not valid file type!(Allow:' . implode(',' , self::$_fileTypeAllow). ')'];
            }
        }

        return ['status' => true, 'msg' => $para->name . ' is ready to upload!'];
    }

    private static function createThumbnail($para)
    {
        if (!function_exists("imagecopyresampled")) 
        {
            return ['status' => false, 'msg' => 'GD is not work well!'];
        }

        $file_info = @getimagesize($para);
        $file_name = str_replace(self::$_pathFolder, '', $para);

        if(!$file_info)
        {
            return ['status' => false, 'msg' => $file_name . ' is failed to get info!'];
        }
        else
        {
            list($width, $height, $type, $attr) = $file_info;
        }

        if($type > 3)
        {
            return ['status' => false, 'msg' => $file_name . ' is not valid type!'];
        }

        if((self::$_thumbnailPercent > 0) && (self::$_thumbnailPercent < 1))
        {
            $thumb_width = (int)($width * self::$_thumbnailPercent);
            $thumb_height = (int)($height * self::$_thumbnailPercent);
        }
        else
        {
            if((self::$_thumbnailWidth == 0) && (self::$_thumbnailHeight == 0))
            {
                return ['status' => false, 'msg' => $file_name . ' is failed to create thumbnail without configuration!'];
            }

            if(self::$_thumbnailWidth >= $width)
            {
                return ['status' => false, 'msg' => $file_name . ' is failed to create thumbnail with wrong width!'];
            }

            if(self::$_thumbnailHeight >= $height)
            {
                return ['status' => false, 'msg' => $file_name . ' is failed to create thumbnail with wrong height!'];
            }

            $thumb_width = self::$_thumbnailWidth;
            $thumb_height = self::$_thumbnailHeight;

            if ($thumb_width == 0)
            {
                $thumb_width = (int)($width * $thumb_height / $height); 
            }

            if ($thumb_height == 0)
            {
                $thumb_height = (int)($height * $thumb_width / $width); 
            }
        }

        switch ($type) 
        {
            //1 = GIF，2 = JPG，3 = PNG
            case 1:
                $source_img = imagecreatefromgif($para);
                break;
            
            case 2:
                $source_img = imagecreatefromjpeg($para);
                break;

            case 3:
                $source_img = imagecreatefrompng($para);
                break;
        }

        $thumb_img = imagecreatetruecolor($thumb_width, $thumb_height);
        imagecopyresampled($thumb_img, $source_img, 0, 0, 0, 0, $thumb_width, $thumb_height, $width, $height);
        
        $thumb_file = str_replace('.', self::$_thumbnailSuffix . '.', $para);

        if(self::$_thumbnailSameType)
        {
            switch ($type) 
            {
                //1 = GIF，2 = JPG，3 = PNG
                case 1:
                    $status = imagegif($thumb_img ,$thumb_file);
                    break;
                
                case 2:
                    $status = imagejpeg($thumb_img ,$thumb_file);
                    break;

                case 3:
                    $status = imagepng($thumb_img ,$thumb_file);
                    break;
            }
        }
        else
        {
            $status = imagejpeg($thumb_img ,$thumb_file);
        }
        
        imagedestroy($thumb_img);

        return ['status' => $status, 'file' => $thumb_file];
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