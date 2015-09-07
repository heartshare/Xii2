<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiYwcPlus extends Yii Web Controller (Do Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: Yii Web Controller扩展类
 * 说明: 
 *
 * What's new ?
 * Ver0.1 Build 20150906
 * - 基本功能实现，未来根据需求扩展功能
 *
 *
 */
namespace app\xii;  

use Yii;
use app\xii;
use yii\web\Controller;
use app\xii\XiiToken;
use app\xii\XiiError;
use app\xii\XiiVersion;
use app\xii\XiiResponse;

class XiiYwcPlus extends Controller 
{
    const XII_VERSION = 'XiiYwcPlus/0.1';

    protected $_requestData;
    protected $_requestValidSwtich = false;

    private $_requestValidData;
    private $_requestValidField = 'API_TOKEN';

    public function init()
    {
        parent::init();
        XiiVersion::run(self::XII_VERSION);
        XiiError::init();
        
        $this->_requestData = Yii::$app->request->get();

        if(isset($this->_requestData[$this->_requestValidField]))
        {
            $this->_requestValidData[$this->_requestValidField] = $this->_requestData[$this->_requestValidField];
            unset($this->_requestData[$this->_requestValidField]);
        }
    }

    public function beforeaction($action)
    {
        if($this->_requestValidSwtich)
        {
            /*
                API与验证码处于同一YII中，API请求验证码可以这样获取：
                XiiToken::accessApi();
                Array ( [API_TOKEN] => 7923c897b6fcde20380f3e1439262579 )
                如果不是同一YII配置，确保XiiToken设置一致即可
            */
            XiiToken::init();

            $valid = XiiToken::verify($this->_requestValidData);

            if($valid === 0)
            {
                XiiError::sendError(408);
                Yii::$app->end();
            }

            if($valid === false)
            {
                XiiError::sendError(403);
                Yii::$app->end();
            }
        }
        
        return true;
    }

}
?>