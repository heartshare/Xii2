<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiArPlus extends ActiveRecord (No Print)
 * 
 * 作者: EricXie | 邮箱: keigonec@126.com | 版本: Version 1.0 (2015)
 *
 * 说明: ActiveRecord扩展类，主要针对增改删的便捷性增强，查询主要还是使用原生Yii
 *
 * Public方法结果返回:
 * 类型: 
 *      Array
 * 格式: 
 *      [
 *          'status' => true, // 成功 true；失败 false
 *          'errorCode' => xxx, // 成功 xxx；失败 xxxx
 *          'errorMsg' => '...', // 文字描述
 *          'data' => mixed(optional) // 数据
 *      ]
 * 格式: [
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
 * Build 20151009
 * - Para参数统一入口, 集中处理, 通过预存变量使用
 *
 * Build 20151008
 * - 增加Para['orderBy'],格式要求['id desc', 'dt asc']
 * - ['id','dt']将默认为['id desc', 'dt desc']
 *
 * Build 20150919
 * - 增加格式为数组的_selectExcept参数，设置在这个数组中的字段，将不会被select
 *
 * Build 20150910
 * - 增加分页
 *
 * Build 20150810
 * - 基于Yii自身，实现AR类功能部分简化和整合
 * - 新增数据：数据自动赋值；ip，密码和日期自定义赋值；Yii自带验证
 * - 编辑数据：数据自动赋值；Yii自带验证
 * - 删除数据：逻辑删除，不支持物理删除（不支持复合主键）
 * - 密码自动生成加密字符串支持sha256,sha512,md5和php55+版本新加的加密方式
 * - 支持params中设置
 *
 *
 * 成功反馈编码：依据CRUD安排起始数
 * C：100 - 199 | R：200 - 299 | U：300 - 399 | D：400 - 499
 *
 * 错误反馈编码：依据CRUD安排起始数
 * C：1000 - 1999 | R：2000 - 2999 | U：3000 - 3999 | D：4000 - 4999
 *
 */
namespace app\xii;

use Yii;
use app\xii\XiiVersion;
use \yii\data\Pagination;
use \yii\widgets\LinkPager;

class XiiArPlus extends \yii\db\ActiveRecord
{
    const XII_VERSION = 'Xii Ar Plus/1.0.1009';

    //Success
    const XII_ADD_SUCCESS = 100;

    const XII_READ_DATA_SUCCESS = 200;
    const XII_READ_COUNT_SUCCESS = 201;

    const XII_EDIT_SUCCESS = 300;
    const XII_EDIT_SUCCESS_NOCHANGE = 301;

    const XII_DEL_SUCCESS = 400;
    const XII_DEL_SUCCESS_NOCHANGE = 401;

    //Fail
    const XII_ADD_FAIL_WRONG_PARA = 1000;
    const XII_ADD_FAIL_INVALID_PARA = 1001;
    const XII_ADD_FAIL_INSERT = 1002;

    const XII_READ_FAIL_NO_DATA = 2000;
    const XII_READ_FAIL_NO_COUNT = 2001;

    const XII_EDIT_FAIL_WRONG_PARA = 3000;
    const XII_EDIT_FAIL_NO_PRI = 3001;
    const XII_EDIT_FAIL_UPDATE_ALL = 3002;
    const XII_EDIT_FAIL_UPDATE_PART = 3003;
    const XII_EDIT_FAIL_NOT_FIND = 3004;
    const XII_EDIT_FAIL_NO_DATA = 3005;

    const XII_DEL_FAIL_ALL = 4000;
    const XII_DEL_FAIL_PART = 4001;
    const XII_DEL_FAIL_WRONG_FIELD = 4002;
    const XII_DEL_FAIL_NOT_FIND = 4003;

    protected static $_deleteField = 'status'; //逻辑删除字段名称，一般建议使用status或isdelete
    protected static $_deleteValue = -1; //数据库设计中，代表逻辑删除的值，一般为负值，例如：-1
    protected static $_deleteForce = true; //数据库如果发生验证失败时，删除会失败，开启这个强制删除
    protected static $_autoFill = true;
    protected static $_autoFieldsPassword = ['password'];
    protected static $_autoFieldsDateTime = ['createdt'];
    protected static $_autoFieldsIp = ['ip'];
    protected static $_autoMethodPassword = 'sha256';
    protected static $_autoParamsPassword = '';
    protected static $_autoParamsDateTime = '';
    protected static $_pageLinkPagerOn = false;
    protected static $_selectExcept = ['password'];
    protected static $_modelFields;
    //因为敏感字段不读取，编辑时会验证失败，开启这个确保编辑成功
    protected static $_editForce = true;

    protected static $_getConfigYiiParams = 'XiiArPlus';
    protected static $_getConfigFields = ['_deleteField',
                                            '_deleteValue',
                                            '_deleteForce',
                                            '_autoFill',
                                            '_autoFieldsPassword',
                                            '_autoFieldsDateTime',
                                            '_autoFieldsIp',
                                            '_autoMethodPassword',
                                            '_autoParamsPassword',
                                            '_autoParamsDateTime',
                                            '_pageLinkPagerOn',
                                            '_selectExcept',
                                            '_editForce'
                                            ];
    
    protected static $_autoPasswordAllow = ['sha256', 'sha512', 'md5', 'php55'];
    protected static $_autoDateTimeAllow = ['int', 'string', 'timestamp'];
    protected static $_autoFillSwitch = false;

    protected static $_paraCondition;
    protected static $_paraOrderby;
    protected static $_paraPage;
    protected static $_paraLimit;
    protected static $_paraSelectFields;

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function init()
    {
        parent::init();
        self::getConfig();
        self::$_modelFields = array_keys(parent::getAttributes());
        XiiVersion::run(self::XII_VERSION);
    }
    
    public function add($para = [])
    {
        self::$_autoFillSwitch = self::$_autoFill;
        
        if(self::prepareData($para))
        {
            if($this->validate())
            {
                $indexs = $this->primaryKey();
                $index = reset($indexs);
                unset($this->$index);

                if($this->insert())
                {
                    return self::getResponse(self::XII_ADD_SUCCESS, $this->getPrimaryKey());
                }
                else
                {
                    return self::getResponse(self::XII_ADD_FAIL_INSERT, $this->getErrors());
                }
            }
            else
            {
                return self::getResponse(self::XII_ADD_FAIL_INVALID_PARA, $this->getErrors());
            }
        }
        else
        {
            return self::getResponse(self::XII_ADD_FAIL_WRONG_PARA, $para); 
        }
    }

    public function edit($para = [])
    {
        if(self::prepareData($para))
        {
            $indexs = $this->primaryKey();
            $index = reset($indexs);

            if(isset($para[$index]))
            {
                $ids = explode(',', $this->$index);
                unset($para[$index]);
                $condition = [$index => $ids];
                if(empty($para))
                {
                    return self::getResponse(self::XII_EDIT_FAIL_NO_DATA);
                }
            }
            else
            {
                return self::getResponse(self::XII_EDIT_FAIL_NO_PRI);
            }

            $records = self::findAll(['condition' => $condition], true);
            if(!$records['status'])
            {
                return self::getResponse(self::XII_EDIT_FAIL_NOT_FIND);
            }

            $true_num = $false_num = 0;
            $total_num = count($records['data']);
            
            foreach ($records['data'] as $record) 
            {
                foreach($para as $k => $v)
                {
                    $record->$k = $this->$k;
                }
                $result = self::$_editForce ? $record->update(false) : $record->update();

                if($result !== false)
                {
                    $error_msg = ($result===0) ? $this->getErrorMessage(self::XII_EDIT_SUCCESS_NOCHANGE) : $para;
                    $feedback[$record->$index] = $error_msg;
                    $true_num++;
                }
                else
                {
                    $feedback[$record->$index] = $record->getErrors();
                    $false_num++;
                }
            }

            if($true_num == $total_num)
            {
                return self::getResponse(self::XII_EDIT_SUCCESS, $feedback); 
            }
            
            if($false_num == $total_num)
            {
                return self::getResponse(self::XII_EDIT_FAIL_UPDATE_ALL, $feedback); 
            }

            return self::getResponse(self::XII_EDIT_FAIL_UPDATE_PART, $feedback); 
        }
        else
        {
            return self::getResponse(self::XII_EDIT_FAIL_WRONG_PARA, $para);
        }
    }

    public function del($condition = '')
    {
        if(in_array(self::$_deleteField, $this->Attributes()))
        {
            $records = self::findAll(['condition' => $condition], true);
            if(!$records['status'])
            {
                return self::getResponse(self::XII_EDIT_FAIL_NOT_FIND);
            }

            $indexs = $this->primaryKey();
            $index = reset($indexs);

            $true_num = $false_num = 0;
            $total_num = count($records['data']);

            foreach ($records['data'] as $record) 
            {
                $tmp = self::$_deleteField;

                $record->$tmp = self::$_deleteValue;
                $result = self::$_deleteForce ? $record->update(false) : $record->update();

                if($result !== false)
                {
                    $error_msg = ($result===0) ? $this->getErrorMessage(self::XII_DEL_SUCCESS_NOCHANGE) : true;
                    $feedback[$record->$index] = $error_msg;
                    $true_num++;
                }
                else
                {
                    $feedback[$record->$index] = $record->getErrors();
                    $false_num++;
                }
            }

            if($true_num == $total_num)
            {
                return self::getResponse(self::XII_DEL_SUCCESS, $feedback); 
            }
            
            if($false_num == $total_num)
            {
                return self::getResponse(self::XII_DEL_FAIL_ALL, $feedback); 
            }

            return self::getResponse(self::XII_DEL_FAIL_PART, $feedback); 
        }
        else
        {
            return self::getResponse(self::XII_DEL_FAIL_WRONG_FIELD);
        }
    }

    public static function findAll($para = [], $obj = false)
    {
        self::paraProcess($para);

        if($obj)
        {
            $feedback = (!empty(self::$_paraCondition)) ? parent::find()->select(self::$_paraSelectFields)->where(self::$_paraCondition)->orderBy(self::$_paraOrderby)->all() : parent::find()->select(self::$_paraSelectFields)->orderBy(self::$_paraOrderby)->all();
        }
        else
        {
            $feedback = (!empty(self::$_paraCondition)) ? parent::find()->select(self::$_paraSelectFields)->where(self::$_paraCondition)->orderBy(self::$_paraOrderby)->asArray()->all() : parent::find()->select(self::$_paraSelectFields)->orderBy(self::$_paraOrderby)->asArray()->all();
        }
       
        if($feedback)
        {
            return self::getResponse(self::XII_READ_DATA_SUCCESS, $feedback);
        }
        else
        {
            return self::getResponse(self::XII_READ_FAIL_NO_DATA, $feedback);
        }
    }

    public static function findAllWithPage($para = [], $obj = false)
    {
        self::paraProcess($para);

        $feedback = (!empty(self::$_paraCondition)) ? parent::find()->select(self::$_paraSelectFields)->where(self::$_paraCondition)->orderBy(self::$_paraOrderby) : parent::find()->select(self::$_paraSelectFields)->orderBy(self::$_paraOrderby);

        if($feedback)
        {
            $countQuery = clone $feedback;
            $pages = new Pagination(['totalCount' => $countQuery->count()]);
            $pages->setPage(self::$_paraPage - 1);
            $pages->setPageSize(self::$_paraLimit);
            
            if($obj)
            {
                $models = $feedback->offset($pages->offset)
                            ->limit($pages->limit)
                            ->all();
            }
            else
            {
                $models = $feedback->offset($pages->offset)
                            ->limit($pages->limit)
                            ->asArray()
                            ->all();
            }

            $data['data'] = $models;
            if(self::$_pageLinkPagerOn)
            {
                $data['pager'] = LinkPager::widget(['pagination' => $pages]);
            }
            else
            {
                $data['pager'] = ['defaultPageSize' => $pages->defaultPageSize,
                                    'forcePageParam' => $pages->forcePageParam,
                                    'limit' => $pages->limit,
                                    'links' => $pages->links,
                                    'offset' => $pages->offset,
                                    'page' => $pages->page,
                                    'pageCount' => $pages->pageCount,
                                    'pageParam' => $pages->pageParam,
                                    'pageSize' => $pages->pageSize,
                                    'pageSizeLimit' => $pages->pageSizeLimit,
                                    'pageSizeParam' => $pages->pageSizeParam,
                                    'params' => $pages->params,
                                    'route' => $pages->route,
                                    'totalCount' => $pages->totalCount,
                                    'urlManager' => $pages->urlManager,
                                    'validatePage' => $pages->validatePage];
            }
            return self::getResponse(self::XII_READ_DATA_SUCCESS, $data);
        }
        else
        {
            return self::getResponse(self::XII_READ_FAIL_NO_DATA, $feedback);
        }
        
    }

    public static function useObjPager()
    {
        self::$_pageLinkPagerOn = false;
    }

    public static function useStrPager()
    {
        self::$_pageLinkPagerOn = true;
    }

    public static function countAll($para = [])
    {
        self::paraProcess($para);

        $feedback =  (!empty(self::$_paraCondition)) ? parent::find()->where(self::$_paraCondition)->count() : parent::find()->count();

        if($feedback)
        {
            return self::getResponse(self::XII_READ_COUNT_SUCCESS, $feedback);
        }
        else
        {
            return self::getResponse(self::XII_READ_FAIL_NO_COUNT, $feedback);
        }
    }

    private static function paraProcess($para)
    {
        self::$_paraCondition = isset($para['condition']) ? $para['condition'] : '';

        self::$_paraPage = isset($para['page']) ? $para['page'] : 1;

        self::$_paraLimit = isset($para['limit']) ? $para['limit'] : 10;

        self::$_paraSelectFields = isset($para['fields']) ? $para['fields'] : '';
        self::selectFields();

        self::$_paraOrderby = isset($para['orderby']) && !empty($para['orderby']) ? self::orderByfield($para['orderby']) : self::orderByfield(self::primaryKey());
    }

    private static function selectFields()
    {
        if(self::$_paraSelectFields != '')
        {
            if(!is_array(self::$_paraSelectFields))
            {
                $tmp = explode(',', self::$_paraSelectFields);
            }
            else
            {
                $tmp = self::$_paraSelectFields;
            }

            $feedback = [];
            foreach ($tmp as $v)
            {
                if(in_array($v, self::$_modelFields) && !in_array($v, self::$_selectExcept))
                {
                    $feedback[] = $v;
                }
            }

            if(!empty($feedback))
            {
                self::$_paraSelectFields = implode(',', $feedback);
            }
            else
            {
                self::$_paraSelectFields = implode(',', self::primaryKey());
            }
        }
        else
        {
            if(is_array(self::$_selectExcept))
            {
                $fields = array_filter(self::$_modelFields,"self::filterFields");
                self::$_paraSelectFields = implode(',', $fields);
            }
            else
            {
                self::$_paraSelectFields = '*';
            }
        }
        
    }

    private static function orderByfield($orders)
    {
        if(!is_array($orders))
        {
            $data = [$orders];
        }
        else
        {
            $data = $orders;
        }

        $feedback = [];

        foreach ($data as $v)
        {
            $tmp = explode(' ', $v);
            if(in_array($tmp[0], self::$_modelFields))
            {
                if(isset($tmp[1]) && (strtolower($tmp[1]) == 'asc'))
                {
                    $feedback[$tmp[0]] = SORT_ASC;
                }
                else
                {
                    $feedback[$tmp[0]] = SORT_DESC;
                }
            }
        }

        return $feedback;
    }

    private static function filterFields($para)
    {
        if(in_array($para, self::$_selectExcept))
        {
            return false;
        }
        else
        {
            return true;
        }
    }

    private function prepareData($para)
    {
        $fields = $this->getAttributes();
        $diff = array_diff_key($para, $fields);

        if(count($diff))
        {
            return false;
        }

        foreach ($para as $key => $value) 
        {
            $this->$key = $value;
        }

        if(self::$_autoFillSwitch)
        {
            self::$_autoFillSwitch = false;
            $this->autoSetValue();
        }
        
        return true;
    }

    private function autoSetValue()
    {
        if(is_array(self::$_autoFieldsPassword))
        {
            foreach (self::$_autoFieldsPassword as $v)
            {
                if($this->hasAttribute($v))
                {
                    $this->$v = $this->setPassword($this->$v);
                }
            }
        }

        if(is_array(self::$_autoFieldsDateTime))
        {
            foreach (self::$_autoFieldsDateTime as $v)
            {
                if($this->hasAttribute($v))
                {
                    $this->$v = $this->setDatetime();
                }
            }
        }
        
        if(is_array(self::$_autoFieldsIp))
        {
            foreach (self::$_autoFieldsIp as $v)
            {
                if($this->hasAttribute($v))
                {
                    $this->$v = $this->setIp();
                }
            }
        }
    }

    private function setPassword($para)
    {
        switch (self::$_autoMethodPassword) 
        {
            case 'sha256':
            case 'sha512':
            case 'md5':
                $password = hash(self::$_autoMethodPassword, $para . self::$_autoParamsPassword);
                break;

            case 'php55':
                if(is_array(self::$_autoParamsPassword))
                {
                    $password = password_hash($passwod, PASSWORD_DEFAULT, self::$_autoParamsPassword);
                }
                else
                {
                    $password = password_hash($passwod, PASSWORD_DEFAULT);
                }
                break;

            default:
                $password = hash("sha256", $para);
                break;
        }

        return $password;
    }

    private function setDatetime()
    {
        return self::$_autoParamsDateTime == '' ? time() : date(self::$_autoParamsDateTime, time());
    }

    private function setIp()
    {
        return Yii::$app->request->getUserIP() ? Yii::$app->request->getUserIP() : '0.0.0.0';
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

    private static function getResponse($errorCode, $data = NULL)
    {
        $status = $errorCode < 1000 ? true : false;
        $errorMsg = self::getErrorMessage($errorCode);

        if($data)
        {
            $feedback = ['status' => $status, 'errorCode' => $errorCode, 'errorMsg' => $errorMsg, 'data' => $data];
        }
        else
        {
            $feedback = ['status' => $status, 'errorCode' => $errorCode, 'errorMsg' => $errorMsg];
        }

        return $feedback;
    }

    private static function getErrorMessage($errorCode) 
    {        
        $errorCodes = [  
                        100 => '新增成功！',
                        200 => '检索成功！',
                        201 => '统计成功！',
                        300 => '编辑成功！',
                        301 => '编辑操作成功但数据无更改.',
                        400 => '删除成功！',
                        401 => '删除操作成功但数据无更改.',
                        1000 => '新增失败(参数错误).',
                        1001 => '新增失败(参数验证失败).',
                        1002 => '新增失败(插入数据失败).',
                        2000 => '检索失败(无数据).',
                        2001 => '统计失败(无数据).',
                        3000 => '编辑失败(参数错误).',
                        3001 => '编辑失败(没有主键数值).',
                        3002 => '编辑失败(更新数据全部错误).',
                        3003 => '编辑失败(更新数据部分错误).',
                        3004 => '编辑失败(主键值检索失败).',
                        3005 => '编辑失败(待修改数据为空).',
                        4000 => '删除失败(全部).',
                        4001 => '删除失败(部分).',
                        4002 => '删除失败(删除标示字段找不到).',
                        4003 => '删除失败(主键值检索失败).',
        ];  
        
        return isset($errorCodes[$errorCode]) ? $errorCodes[$errorCode] : "未定义错误.";
    }
}
?>