<?php
/**
 * Xii2 --- Mr Xie's Php class file.
 *          Most of them are designed for Yii2 project.
 *          That's why named Xii2.
 *
 * Xii2 Serial Class - XiiCurl (Do Print)
 * 
 * 作者: EricXie
 * 邮箱: keigonec@126.com
 * 功能: 针对Restful风格接口的Curl封装类
 * 说明: 简单的封装，无特殊处理，由Xii第一版修改而来
 *      CURLOPT_CUSTOMREQUEST需要服务器支持
 * 
 * 版本: Ver0.1 Build 20150818
 * 参数:
 * $para参数列表：
 * useragent: 模拟用户代理，默认IE5
 * url: 目标网址，必填
 * ref_url: 来源地址
 * method: 方法，这个参数只支持POST PUT DELETE, 需要其他办法使用 set
 * data:要提交的参数数组，必须为数组格式且必填
 * timeout: 超时设置
 * info: 获取CURL INFO
 * set: set参数会使用curl_setopt去设置
 *      例如$para['set'] = array('CURLOPT_URL' => 'http://www.focus.cn')
 *
 * 示例:
 * echo "<br><br>xii/XiiCurl Test...<br>";
 * $data = array('site' => 1,'page' => 1,'pos' => 75);
 * $para = array('url' => 'http://localhost/adserver/api/ad','data' => $data);
 * $feedback = XiiCurl::Run($para);
 * var_dump($feedback);
 */
namespace app\xii;

use Yii;
use yii\web\Response;
use app\xii\XiiVersion;
use app\xii\XiiToken;

class XiiCurl
{
    const XII_VERSION = 'XiiCurl/0.1';

    public static function run($para, $usetoken = true)
    {
        $ch = curl_init();

        if(isset($para['useragent']) && !empty($para['useragent']))
        {
            curl_setopt($ch, CURLOPT_USERAGENT, $para['useragent']);
        }
        else
        {
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/4.0 (compatible; MSIE 5.01; Windows NT 5.0)");
        }
        
        if(isset($para['url']) && !empty($para['url']))
        {
            curl_setopt($ch, CURLOPT_URL, $para['url']);
        }
        else
        {
            return 'Url is null!';
        }

        if(isset($para['ref_url']) && !empty($para['ref_url']))
        {
            curl_setopt($ch, CURLOPT_REFERER, $para['ref_url']);
        }

        if (is_array($para['data']) && count($para['data']) > 0)
        {
            if($usetoken)
            {
                $token = XiiToken::accessApi();
                $para['data'] = array_merge($para['data'], $token);
            }

            if(isset($para['method']) && !empty($para['method']) && in_array(strtoupper($para['method']), array('PUT', 'DELETE', 'POST')))
            {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($para['method']));
                //curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: " . strtoupper($para['method'])));
                $para['data'] = http_build_query($para['data']);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $para['data']);
            }
            else
            {
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'GET');
                //curl_setopt($ch, CURLOPT_HTTPHEADER, array("X-HTTP-Method-Override: POST"));
                $para['data'] = http_build_query($para['data']);
                curl_setopt($ch, CURLOPT_URL, $para['url'] . '?' . $para['data']);
            }
        }
        else
        {
            return 'Data is null!';
        }

        $timeout = isset($para['timeout']) && !empty($para['timeout']) ? intval($para['timeout']) : 10;
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        if(isset($para['set']) && !empty($para['set']))
        {
            foreach ($para['set'] as $k => $v) 
            {
                curl_setopt($ch, $k, $v);
            }
        }

        if(isset($para['info']))
        {
            $result = curl_exec($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            return $info;
        }
        else
        {
            $result = curl_exec($ch);
            curl_close($ch);
            return $result;
        }
        
    }

}