<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiYwcPlus extends Yii Web Controller (Do Print)
 * 
 * 作者: EricXie | 邮箱: keigonec@126.com | 版本: Version 1.0 (2015)
 *
 * 说明: 基于Yii2 Web Controller实现功能扩展类
 *
 * Public方法结果返回: - Use XiiResponse - 
 * 类型: 
 *      Json, XML, JSONP, HTML(Array To String), Redirect
 * 格式: 
 *      [
 *          'status' => true, // 成功 true；失败 false
 *          'errorCode' => xxx, // 成功 xxx；失败 xxxx
 *          'errorMsg' => '...', // 文字描述
 *          'data' => mixed(optional) // 数据
 *      ]
 *      [
 *          'status' => true, // 成功 true；失败 false
 *          'errorCode' => xxx, // 成功 xxx；失败 xxxx
 *          'errorMsg' => '...', // 文字描述
 *          'data' => [
 *                      'data' => mixed(optional) // 数据
 *                      'pager' => mixed(optional) //分页（obj | str）
 *                    ]
 *      ]
 *
 * What's new ?
 * Build 20150906
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
use app\xii\XiiUser;
use app\xii\XiiCacheId;

class XiiYwcPlus extends Controller 
{
    const XII_VERSION = 'Xii Ywc Plus/1.0.0906';

    protected $_model;
    protected $_modelReady = false;

    protected $_pageSwitch = true;
    protected $_requestData;
    protected $_requestIds;
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
        XiiUser::open();
        
        switch (Yii::$app->request->getMethod())
        {
            case 'GET':
                $this->_requestData = Yii::$app->request->get();
                $this->_requestIds = isset($this->_requestData['id']) ? $this->_requestData['id'] : '';
                break;

            case 'POST':
                $this->_requestData = Yii::$app->request->post();
                break;

            case 'PUT':
                parse_str(file_get_contents('php://input'), $put_vars);
                $this->_requestData = $put_vars;
                $this->_requestIds = Yii::$app->request->get();
                $this->_requestData = array_merge($this->_requestData, $this->_requestIds);
                break;

            case 'DELETE':
                parse_str(file_get_contents('php://input'), $put_vars);
                $this->_requestData = $put_vars;
                $this->_requestIds = Yii::$app->request->get();
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

        $this->checkModel();
        return true;
    }

    public function noModel()
    {
        $this->_modelReady = true;
    }

    public function setModel($model)
    {
        $this->_model = new $model;
        $this->_modelReady = true;
    }

    public function checkModel()
    {
        if(!$this->_modelReady)
        {
            XiiError::sendError(409);
            Yii::$app->end();
        }
    }

    public function needLogin($loginpage = false)
    {
        if($loginpage)
        {
            if(XiiUser::islogin() > 0)
            {
                return $this->redirect(XiiUser::goHome());
            }
        }
        else
        {
            if(XiiUser::islogin() < 0)
            {
                return $this->redirect(XiiUser::goLogin());
            }
        }
    }

    public function actionIndex()
    {
        $para['page'] = isset($this->_requestData['page']) ? (int)$this->_requestData['page'] : 1;
        $para['limit'] = isset($this->_requestData['pagesize']) ? (int)$this->_requestData['pagesize'] : 10;

        if(isset($this->_requestData['condition']))
        {
            $para['condition'] = $this->_requestData['condition'];
        }

        if(isset($this->_requestData['orderby']))
        {
            $para['orderby'] = $this->_requestData['orderby'];
        }

        $cacheId = XiiCacheId::run($para);
        $cacheId = $cacheId ? $cacheId : '';

        if($this->_pageSwitch)
        {
            if(isset($this->_requestData['useobjpager']))
            {
                $this->_model->useObjPager();
            }

            if(isset($this->_requestData['usestrpager']))
            {
                $this->_model->useStrPager();
            }

            XiiResponse::run($this->_model->findAllWithPage($para), $cacheId);
        }
        else
        {
            XiiResponse::run($this->_model->findAll($para), $cacheId);
        }
    }

    public function actionView()
    {
        $para = ['condition' => ['id' => $this->_requestIds]];
        
        $cacheId = XiiCacheId::run($para);
        $cacheId = $cacheId ? $cacheId : '';

        XiiResponse::run($this->_model->findAll($para), $cacheId);
    }

    public function actionCreate()
    {
        XiiResponse::run($this->_model->add($this->_requestData));
    }

    public function actionUpdate()
    {
        XiiResponse::run($this->_model->edit($this->_requestData));
    }

    public function actionDelete()
    {
        XiiResponse::run($this->_model->del($this->_requestIds));
    }

}
?>