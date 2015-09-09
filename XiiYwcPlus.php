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
    protected $_responseType = 'json';
    protected $_responseTypeField = 'response';

    private $_requestValidData;
    private $_requestValidField = 'XII_API_TOKEN';

    public function init()
    {
        parent::init();
        XiiVersion::run(self::XII_VERSION);
        XiiError::open();
        
        switch (Yii::$app->request->getMethod())
        {
            case 'GET':
                $this->_requestData = Yii::$app->request->get();
                break;

            case 'POST':
                $this->_requestData = Yii::$app->request->post();
                break;

            case 'PUT':
            case 'DELETE':
                parse_str(file_get_contents('php://input'), $put_vars);
                $this->_requestData = $put_vars;
                break;
            
            default:
                $this->_requestData = Null;
                break;
        }

        if(isset($this->_requestData[$this->_requestValidField]))
        {
            $this->_requestValidData[$this->_requestValidField] = $this->_requestData[$this->_requestValidField];
            unset($this->_requestData[$this->_requestValidField]);
        }

        if(isset($this->_requestData[$this->_responseTypeField]))
        {
            $this->_responseType = $this->_requestData[$this->_responseTypeField];
            unset($this->_requestData[$this->_responseTypeField]);
            XiiResponse::getFormat($this->_responseType);
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