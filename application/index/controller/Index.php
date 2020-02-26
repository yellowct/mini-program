<?php

namespace app\index\controller;

use think\Cache;
use think\Db;
use WXBizDataCrypt;

class Index
{
    public function index()
    {
        // // $yesterday=date('Y-m-d',strtotime('-1 day'))
        // $end = date('Y-m-d', strtotime('-1 month'));
        // $start = date('Y-m-1', strtotime($end));

        // //统计需要打卡的天数
        // $list = Db::name('clock')->where('date', 'between', [$start, $end])->where('clock_in', 'not null')->where('clock_out', 'not null')->field('date')->distinct(true)->select();
        // $count = count($list);

        // $list=Db::name('clock')->where('date','between',[$start,$end])->where('clock_in', 'not null')->where('clock_out', 'not null')
        //     ->field('user_id,COUNT(*) as total')
        //     ->group('user_id')
        //     ->select();

        // foreach ($list as $key => $value) {
        //     if($list[$key]['total']==$count){
        //         $res=Db::name('user')->where('user_id',$value['user_id'])->setInc('score',5);
        //         if ($res) {
        //             write_log($value['user_id'], '获得5积分（每月工龄分）');
        //         }
        //     }
            
        // }

        // dump($list);
    }

    public function get_openid()
    {
        $code = $_REQUEST['code'];
        $appid = "wx0a26ad2d7d081bf3";
        $serect = "c6f207f094d6ef692aa5a5f82b993ad3";
        $url = "https://api.weixin.qq.com/sns/jscode2session?appid=$appid&secret=$serect&js_code=$code&grant_type=authorization_code";
        $weixin = file_get_contents($url);
        $jsondecode = json_decode($weixin); //对JSON格式的字符串进行编码
        $array = get_object_vars($jsondecode); //转换成数组
        $openid = $array['openid']; //输出openid
        $session_key = $array['session_key'];
        //为openid设置缓存
        $open = create_code();
        $sess = create_code();
        Cache::set($open, $openid, 7200);
        Cache::set($sess, $session_key, 7200);
        $data = ['open' => $open, 'sess' => $sess];
        return json_encode($data);
    }

    //保存用户信息
    public function user_insert()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $user = $_REQUEST['user'];
        $user = json_decode($user, true);
        $sql = Db::name('user')->where('openid', $openid)->count();
        if ($sql !== 1) {
            $data = [
                'nickname' => $user['nickName'],
                'avatar' => $user['avatarUrl'],
                'openid' => $openid,
                'create_time' => time(),
            ];
            $result = Db::name('user')->insert($data);
        } else {
            return '此用户已存在！';
        }
    }

    //身份鉴定
    public function check()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $type = Db::name('user')->where('openid', $openid)->value('type');
        return $type;
    }

    //获取微信步数
    public function get_step()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $encryptedData = $_REQUEST['encryptedData'];
        $iv = $_REQUEST['iv'];
        $sess = $_REQUEST['sess'];
        $sessionKey = Cache::get($sess);
        $appid = 'wx0a26ad2d7d081bf3';
        include_once "public/wxBizDataCrypt.php";
        $pc = new WXBizDataCrypt($appid, $sessionKey);
        $errCode = $pc->decryptData($encryptedData, $iv, $data);

        if ($errCode == 0) {
            $data = json_decode($data, true);
            $step = $data['stepInfoList'][29]['step'];
            $last_step = Db::name('user')->where('openid', $openid)->value('step');
            if ($last_step != $step) {
                Db::name('user')->where('openid', $openid)->setField('step', $step);
            }
        } else {
            // print($errCode . "\n");
            return $errCode;
        }
    }
}
