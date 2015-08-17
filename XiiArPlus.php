<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiArPlus extends ActiveRecord (No Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: ActiveRecord扩展类，主要针对增改删的便捷性增强，查询主要还是使用原生Yii
 * 说明: 删除方法为逻辑删除，为了安全，也是个人一直以来的习惯
 *      只适用于单主键的数据表设计，复合主键建议直接使用SQL进行操作
 *      为Yii使用设计，非Yii下使用，需要做调整
 *
 * What's new ?
 * Ver0.1 Build 20150810
 * - 基于Yii自身，实现AR类功能部分简化和整合
 * - 新增数据：数据自动赋值；ip，密码和日期自定义赋值；Yii自带验证
 * - 编辑数据：数据自动赋值；Yii自带验证
 * - 删除数据：逻辑删除，不支持物理删除
 * - 密码自动生成加密字符串支持sha256,sha512,md5和php55+版本新加的加密方式
 * - 支持params中设置，格式要求如下：
 * - 'XiiArPlus'=> ['_deleteField' => 'status',
 *                    '_deleteValue' => -1,
 *                    '_deleteForce' => true,
 *                    '_autoFill' => true,
 *                    '_autoFieldsPassword' => ['password'],
 *                    '_autoFieldsDateTime' => ['createDt'],
 *                    '_autoFieldsIp' => ['ip'],
 *                    '_autoMethodPassword' => 'sha256',
 *                    '_autoParamsPassword' => '',
 *                    '_autoParamsDateTime' => '',
 *                    ];
 *
 * Public方法结果返回：
 * 格式：[
 *          'status' => true, // 成功 true；失败 false
 *          'errorCode' => xxx, // 成功 xxx；失败 xxxx
 *          'errorMsg' => '...', // 文字描述
 *          'data' => mixed(optional) // 数据
 *      ]
 *
 * 成功反馈编码：依据CRUD安排起始数
 * C：100 - 199
 * R：200 - 299
 * U：300 - 399
 * D：400 - 499
 *
 * 错误反馈编码：依据CRUD安排起始数
 * C：1000 - 1999
 * R：2000 - 2999
 * U：3000 - 3999
 * D：4000 - 4999
 *
 */
namespace app\xii;

use Yii;

class XiiArPlus extends \yii\db\ActiveRecord
{
    const XII_VERSION = 'XiiArPlus/0.1';
    
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

    const XII_DEL_FAIL_ALL = 4000;
    const XII_DEL_FAIL_PART = 4001;
    const XII_DEL_FAIL_WRONG_FIELD = 4002;

    private static $_deleteField = 'status'; //逻辑删除字段名称，一般建议使用status或isdelete
    private static $_deleteValue = -1; //数据库设计中，代表逻辑删除的值，一般为负值，例如：-1
    private static $_deleteForce = true; //数据库如果发生验证失败时，删除会失败，开启这个强制删除
    private static $_autoFill = true;
    private static $_autoFieldsPassword = ['password'];
    private static $_autoFieldsDateTime = ['createdt'];
    private static $_autoFieldsIp = ['ip'];
    private static $_autoMethodPassword = 'sha256';
    private static $_autoParamsPassword = '';
    private static $_autoParamsDateTime = '';

    private static $_getConfigYiiParams = 'XiiArPlus';
    private static $_getConfigFields = ['_deleteField',
                                        '_deleteValue',
                                        '_deleteForce',
                                        '_autoFill',
                                        '_autoFieldsPassword',
                                        '_autoFieldsDateTime',
                                        '_autoFieldsIp',
                                        '_autoMethodPassword',
                                        '_autoParamsPassword',
                                        '_autoParamsDateTime',
                                        ];
    
    private static $_autoPasswordAllow = ['sha256', 'sha512', 'md5', 'php55'];
    private static $_autoDateTimeAllow = ['int', 'string', 'timestamp'];
    private static $_autoFillSwitch = false;

    public static function model($className=__CLASS__)
    {
        return parent::model($className);
    }

    public function init()
    {
        parent::init();
        self::getConfig();
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
            }
            else
            {
                return self::getResponse(self::XII_EDIT_FAIL_NO_PRI);
            }
            
            $records = self::findAll($ids);

            $true_num = $false_num = 0;
            $total_num = count($records);
            
            foreach ($records as $record) 
            {
                foreach($para as $k => $v)
                {
                    $record->$k = $this->$k;
                }
                $result = $record->update();

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
        $feedback = [];

        if(in_array($this->_deleteField, $this->Attributes()))
        {
            $records = self::findAll($condition);
            $indexs = $this->primaryKey();
            $index = reset($indexs);

            $true_num = $false_num = 0;
            $total_num = count($records);

            foreach ($records as $record) 
            {
                $tmp = $this->_deleteField;
                $record->$tmp = $this->_deleteValue;
                $result = $this->_deleteForce ? $record->update(false) : $record->update();

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

    public static function findAll($condition = '')
    {
        $feedback = (!empty($condition)) ? parent::findAll($condition) : parent::find()->all();

        if($feedback)
        {
            return self::getResponse(self::XII_READ_DATA_SUCCESS, $feedback);
        }
        else
        {
            return self::getResponse(self::XII_READ_FAIL_NO_DATA, $feedback);
        }
    }

    public static function countAll($condition = '')
    {
        $feedback =  (!empty($condition)) ? parent::find()->where($condition)->count() : parent::find()->count();

        if($feedback)
        {
            return self::getResponse(self::XII_READ_COUNT_SUCCESS, $feedback);
        }
        else
        {
            return self::getResponse(self::XII_READ_FAIL_NO_COUNT, $feedback);
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
                        4000 => '删除失败(全部).',
                        4001 => '删除失败(部分).',
                        4002 => '删除失败(删除标示字段找不到).',
        ];  
        
        return isset($errorCodes[$errorCode]) ? $errorCodes[$errorCode] : "未定义错误.";
    }
}
?>