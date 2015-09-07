<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiError (Do Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: 错误捕获类，Fatal Error
 * 说明: 简单写写，随时扩展
 *
 * What's new ?
 * Ver0.3 Build 20150907
 * -  增加errorFormat参数和相应函数，增加XML和HTML输出格式
 *
 * Ver0.21 Build 20150813
 * -  增加errorIgnore参数，强制忽略，关闭错误反馈
 *
 * Ver0.2 Build 20150806
 * -  增加getConfig函数，可以通过设置params中的参数来提示自定义的错误信息
 * -  格式要求为：'XiiError' => ['404' => 'no page find', '' => '', ...]
 *
 * Ver0.1 Build 20150730
 * -  实现错误捕捉和提示
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
 *       if($error = \app\xii\XiiError::run()) 
 *       {
 *           \app\xii\XiiError::sendError($error['code'], $error['msg']);
 *       }
 *   }
 */

namespace app\xii;

use Yii;
use yii\web\Response;
use app\xii\XiiVersion;

class XiiError
{
    const XII_VERSION = 'XiiError/0.3';

    public static $codes = array();
    public static $errorIgnore = false;
    public static $errorFormat = 'json';

    public static function init()
    {
        register_shutdown_function('\app\xii\XiiError::run');
    }

    public static function run()
    {
        XiiVersion::run(self::XII_VERSION);

        if(self::$errorIgnore)
        {
            return null;
        }

        self::getConfig();

        //Yii1.x是这样捕获错误的
        //$e = Yii::app()->errorHandler->error;
        //Yii2.x是这样捕获错误的
        $e = Yii::$app->errorHandler->exception;

        $eplus = error_get_last();

        if($e !== null)
        {
            if (isset($e->statusCode)) 
            {
                $code = $e->statusCode;
                $msg = self::getErrorMessage($code);
            } 
            else 
            {
                $code = method_exists($e, 'getCode') ? $e->getCode() : '827';
                $msg = method_exists($e, 'getMessage') ? $e->getMessage() : 'No msg';
            }
 
            return ['code' => $code, 'msg' => $msg];
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

    public static function sendError($errorCode, $errorMessage = null)
    {
        XiiVersion::run(self::XII_VERSION);

        self::setFormat();
        self::setData($errorCode, $errorMessage);

        Yii::$app->response->send();
        Yii::$app->end();
    }

    public static function getErrorMessage($errorCode) 
    {
        XiiVersion::run(self::XII_VERSION);

        if(isset(self::$codes[$errorCode]))
        {
            return self::$codes[$errorCode];
        }

        $errorCodes = Response::$httpStatuses;
        return isset($errorCodes[$errorCode]) ? $errorCodes[$errorCode] : "Unrecognizable Error!";
    }

    private static function setFormat()
    {
        switch (self::$errorFormat)
        {
            case 'html':
                Yii::$app->response->format = Response::FORMAT_HTML;
                break;

            case 'xml':
                Yii::$app->response->format = Response::FORMAT_XML;
                break;

            case 'json':
            default:
                Yii::$app->response->format = Response::FORMAT_JSON;
                break;
        }
    }

    private static function setData($errorCode, $errorMessage)
    {
        switch (self::$errorFormat)
        {
            case 'html':
                $errorMessage == null ? self::getErrorMessage($errorCode) : $errorMessage;
                Yii::$app->response->data = 'errorCode : ' . $errorCode . PHP_EOL . 'errorMsg : ' . $errorMessage;
                break;

            case 'xml':
            case 'json':
            default:
                Yii::$app->response->data = ['errorCode' => $errorCode,
                                        'errorMsg' => $errorMessage == null ? self::getErrorMessage($errorCode) : $errorMessage,
                                    ];
                break;
        }
    }

    private static function getConfig()
    {
        if((isset(Yii::$app->params['XiiError'])) && (is_array(Yii::$app->params['XiiError'])))
        {
            self::$codes = Yii::$app->params['XiiError'];
        }
    }
}
?>