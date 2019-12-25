<?php

namespace app\index\controller;

use think\Cache;
use think\Db;
use WXBizDataCrypt;

class Index
{
    public function index()
    {
        return 'session';
    }

    public function get_openid()
    {
        $code = $_REQUEST['code'];
        $appid = "wx0a26ad2d7d081bf3";
        $serect = "c6f207f094d6ef692aa5a5f82b993ad3";
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$serect&js_code=$code&grant_type=authorization_code";
        $weixin =  file_get_contents($url);
        $jsondecode = json_decode($weixin); //对JSON格式的字符串进行编码
        $array = get_object_vars($jsondecode); //转换成数组
        $openid = $array['openid']; //输出openid
        $session_key = $array['session_key'];
        echo $session_key;
        //为openid设置缓存
        Cache::set('openid', $openid);
        Cache::set('session_key', $session_key);
        // session('session_key', $session_key);
        return $openid;
    }

    //保存用户信息
    public function user_insert()
    {
        $openid = $_REQUEST['openid'];
        $user = $_REQUEST['user'];
        $user = json_decode($user, true);
        $sql = Db::name('user')->where('openid', $openid)->count();
        if ($sql !== 1) {
            $data = [
                'nickname' => $user['nickName'],
                'avatar' => $user['avatarUrl'],
                // 'gender'=>$user['gender'],
                'openid' => $openid,
                'create_time' => time()
            ];
            $result = Db::name('user')->insert($data);
        } else {
            return '此用户已存在！';
        }
    }

    //身份鉴定
    public function check()
    {
        $openid = Cache::get('openid');
        $type = Db::name('user')->where('openid', $openid)->value('type');
        return $type;
    }

    public function get_step()
    {
        $encryptedData = $_REQUEST['encryptedData'];
        $iv = $_REQUEST['iv'];
        $sessionKey = Cache::get('session_key');
        echo ($sessionKey);
        $appid = 'wx0a26ad2d7d081bf3';
        include_once "public/wxBizDataCrypt.php";
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);
        

        if ($errCode == 0) {
            $data=json_decode($data,true);
            $step=$data['stepInfoList'][29]['step'];
            dump($data) ;
            Db::name('user')->where('openid',Cache::get('openid'))->setField('step',$step);
        } else {
            // print($errCode . "\n");  
            return $errCode;
        }
    }
}
