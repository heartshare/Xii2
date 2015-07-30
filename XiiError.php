<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiError
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: 错误捕获类，Fatal Error
 * 说明: 简单写写，随时扩展
 *
 * 版本: Ver0.1 Build 20150730
 * 
 * 示例:
 * 1.根据config/web.php中的设置找到错误处理的Action
 *       'errorHandler' => [
 *          'errorAction' => 'site/error',
 *       ],
 * 2.修改SiteController
 * 将actions函数中return error的代码注释掉或者删除
 * public function actions()
 *   {
 *       return [
 *           //'error' => [
 *           //    'class' => 'yii\web\ErrorAction',
 *           //],
 *           'captcha' => [
 *               'class' => 'yii\captcha\CaptchaAction',
 *               'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
 *           ],
 *       ];
 *   }
 * 增加对应函数actionError
 * public function actionError()
 *   {
 *       if($error = \app\xii\XiiError::Run()) 
 *       {
 *           \app\xii\XiiError::sendError($error['code'], $error['msg']);
 *       }
 *   }
 */

namespace app\xii;
use Yii;

class XiiError
{
    public static $_Codes = array();

    public static function init()
    {
        register_shutdown_function('XiiError::Run');
    }

    public static function Run()
    {
        //Yii1.x是这样捕获错误的
        //$e = Yii::app()->errorHandler->error;
        //Yii2.x是这样捕获错误的
        $e = Yii::$app->errorHandler->exception;
        $eplus = error_get_last();

        if($e)
        {
            return array('code' => $e->statusCode, 'msg' => self::getErrorMessage($e->statusCode));
            
        }
        else
        {
            if($eplus)
            {
                if($eplus['type'] != 8192)
                {
                    self::sendError(826, $eplus['message']);
                }
            }
            else
            {
                return null;
            }
        }
    }

    public function sendError($errorCode, $errorMessage = null)
    {
        header("Content-type:application/json;");
        $response = array(
                            'errorCode' => $errorCode,
                            'errorMessage' => $errorMessage == null ? self::getErrorMessage($errorCode) : $errorMessage,
                         );
        echo \yii\helpers\Json::encode($response);
        Yii::$app->end();
    }
    
    public static function getErrorMessage($errorCode) 
    {
        if(isset(self::$_Codes[$errorCode]))
        {
            return self::$_Codes[$errorCode];
        }
        
        $codes = Array(  
                        100 => 'Continue',  
                        101 => 'Switching Protocols',  
                        200 => 'OK',  
                        201 => 'Created',  
                        202 => 'Accepted',  
                        203 => 'Non-Authoritative Information',  
                        204 => 'No Content',  
                        205 => 'Reset Content',  
                        206 => 'Partial Content',  
                        300 => 'Multiple Choices',  
                        301 => 'Moved Permanently',  
                        302 => 'Found',  
                        303 => 'See Other',  
                        304 => 'Not Modified',  
                        305 => 'Use Proxy',  
                        306 => '(Unused)',  
                        307 => 'Temporary Redirect',  
                        400 => 'Bad Request',  
                        401 => 'Unauthorized',  
                        402 => 'Payment Required',  
                        403 => 'Forbidden',  
                        404 => 'Not Found',  
                        405 => 'Method Not Allowed',  
                        406 => 'Not Acceptable',  
                        407 => 'Proxy Authentication Required',  
                        408 => 'Request Timeout',  
                        409 => 'Conflict',  
                        410 => 'Gone',  
                        411 => 'Length Required',  
                        412 => 'Precondition Failed',  
                        413 => 'Request Entity Too Large',  
                        414 => 'Request-URI Too Long',  
                        415 => 'Unsupported Media Type',  
                        416 => 'Requested Range Not Satisfiable',  
                        417 => 'Expectation Failed',  
                        500 => 'Internal Server Error',  
                        501 => 'Not Implemented',  
                        502 => 'Bad Gateway',  
                        503 => 'Service Unavailable',  
                        504 => 'Gateway Timeout',  
                        505 => 'HTTP Version Not Supported'  
        );  
        
        return isset($codes[$errorCode]) ? $codes[$errorCode] : "Unrecognizable Error!";
    }
}