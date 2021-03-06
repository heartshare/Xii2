<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiFolder (No Print)
 * 
 * 作者: EricXie | 邮箱: keigonec@126.com | 版本: Version 1.0 (2015)
 *
 * 说明: 目录操作类, 检测目录是否存在, 不存在则建立, 为Yii使用设计
 * 
 * Public方法结果返回: (无页面输出)
 * 类型: 
 *      NULL
 * 格式: 
 *      NULL
 *
 * What's new ?
 * Build 20150730
 * -  实现目录创建
 *
 * 示例: \app\xii\XiiFolder::mkdir('upload/1/2/3');
 */
namespace app\xii;
use Yii;
use app\xii\XiiVersion;

class XiiFolder
{
    const XII_VERSION = 'Xii Folder/1.0.0730';

    public static function init()
    {
        XiiVersion::run(self::XII_VERSION);
    }

    public static function mkdir($para, $base = 'web')
    {
        self::init();
        
        if(strpos($para,'/'))
        {
            $root_dir = Yii::$app->basePath . '/' . $base . '/';
            $root_dir = str_replace('//', '/', $root_dir);

            $tmp_dir = explode('/',$para);
            foreach($tmp_dir as $current_dir) 
            {
                if($current_dir == "") 
                {
                    continue;
                }
                $root_dir .= $current_dir;
                if(!is_dir($root_dir))
                {
                    if(mkdir($root_dir, 0777, true))
                    {
                        chmod($root_dir, 0777);
                    }
                }
                $root_dir .= '/';
            }
        }
        else
        {
            if(!is_dir($para))
            {
                mkdir($para, 0777, true);
            }
            chmod($para, 0777);
        }
    }
}
?>