<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiAcPlus extends ActiveController (Do Print)
 * 
 * 作者: EricXie | 邮箱: keigonec@126.com | 版本: Version 1.0 (2015)
 *
 * 说明: 基于Yii2 ActiveController类实现的功能扩展类
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
 * Build 20150810
 * - 基本功能实现，未来根据需求扩展功能
 *
 *
 */
namespace app\xii;  

use Yii;
use app\xii;
use yii\rest\ActiveController;
use app\xii\XiiToken;
use app\xii\XiiError;
use app\xii\XiiVersion;
use app\xii\XiiResponse;
use app\xii\XiiUser;
use app\xii\XiiCacheId;

class XiiAcPlus extends ActiveController 
{
    const XII_VERSION = 'Xii Ac Plus/1.0.0810';

    public $modelClass = 'XiiAcPlus';

    protected $_modelClass;
    protected $_requestCurrent;
    protected $_requestData;
    protected $_requestIds;
    protected $_requestValidSwtich = false;

    private $_requestValidData;
    private $_requestValidField = 'XII_API_TOKEN';

    public function init()
    {
        parent::init();
        XiiVersion::run(self::XII_VERSION);

        if ($this->modelClass === 'XiiAcPlus') 
        {
            XiiError::sendError(0,'The "modelClass" property must be set.');
            Yii::$app->end();
        }

        $this->_requestCurrent = Yii::$app->request->getMethod();
        XiiError::open();
        $this->_modelClass = new $this->modelClass;

        switch ($this->_requestCurrent)
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

    public function actions()
    {
        $actions = parent::actions();
        // 注销系统自带的实现方法
        unset($actions['index'], $actions['update'], $actions['create'], $actions['delete'], $actions['view']);
        return $actions;
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
        if($this->_requestCurrent != 'GET')
        {
            XiiError::sendError(400);
            Yii::$app->end();
        }

        $para['page'] = isset($this->_requestData['page']) ? (int)$this->_requestData['page'] : 1;
        $para['limit'] = isset($this->_requestData['pagesize']) ? (int)$this->_requestData['pagesize'] : 10;

        if(isset($this->_requestData['condition']))
        {
            $para['condition'] = $this->_requestData['condition'];
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
        if($this->_requestCurrent != 'GET')
        {
            XiiError::sendError(400);
            Yii::$app->end();
        }

        $para = ['condition' => ['id' => $this->_requestIds]];
        
        $cacheId = XiiCacheId::run($para);
        $cacheId = $cacheId ? $cacheId : '';
        
        XiiResponse::run($this->_model->findAll($para), $cacheId);
    }

    public function actionCreate()
    {
        if($this->_requestCurrent != 'POST')
        {
            XiiError::sendError(400);
            Yii::$app->end();
        }

        XiiResponse::run($this->_modelClass->add($this->_requestData));
    }

    public function actionUpdate()
    {
        if($this->_requestCurrent != 'PUT')
        {
            XiiError::sendError(400);
            Yii::$app->end();
        }

        XiiResponse::run($this->_modelClass->edit($this->_requestData));
    }

    public function actionDelete()
    {
        if($this->_requestCurrent != 'DELETE')
        {
            XiiError::sendError(400);
            Yii::$app->end();
        }

        XiiResponse::run($this->_modelClass->del($this->_requestIds));
    }
}
?>