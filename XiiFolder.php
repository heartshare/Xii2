<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiFolder (No Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: 目录操作类，检测目录是否存在，不存在则建立
 * 说明: 为Yii使用设计，非Yii下使用，需要做调整
 *
 * 版本: Ver0.1 Build 20150730
 * -  实现目录创建
 *
 * 示例: \app\xii\XiiFolder::mkdir('upload/1/2/3');
 */
namespace app\xii;
use Yii;

class XiiFolder
{
    const XII_VERSION = 'XiiFolder/0.1';
    
    public static function mkdir($para, $base = 'web')
    {
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