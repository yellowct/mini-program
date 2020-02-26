<?php

namespace app\index\controller;

use think\Cache;
use think\Db;

class Turntable
{
    //生成抽奖随机数
    public function index()
    {
        $list = Db::name('turntable')->where('num', 'neq', 0)->select();
        foreach ($list as $key => $value) {
            $min = ($value['type'] - 1) * 72 + 1;
            $max = $value['type'] * 72;
            $rand[$key] = rand($min, $max);
        }
        // dump($rand);
        $res = $rand[array_rand($rand)];
        return $res;
    }

    public function get_result()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $user_id = Db::name('user')->where('openid', $openid)->value('user_id');
        $val = $_REQUEST['val'];
        if ($val == '牛奶') {
            $type = 1;
        } elseif ($val == '1积分') {
            $type = 2;
        } elseif ($val == '午餐') {
            $type = 3;
        } elseif ($val == '饮料') {
            $type = 4;
        } else {
            $type = 5;
        }
        if ($val != '1积分') {
            Db::name('turntable')->where('val', $val)->setDec('num');
            if ($val == '3积分') {
                Db::name('user')->where('openid', $openid)->setInc('score', 3);
            }
        } else {
            Db::name('user')->where('openid', $openid)->setInc('score');
        }

        $data = [
            'user_id' => $user_id,
            'type' => $type,
            'time' => date('Y-m-d', time()),

        ];
        Db::name('cjjg')->insert($data);
        write_log($user_id, '今日抽奖获得' . $val);
    }

    public function show()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $res = Db::name('cjjg')->alias('a')->join('user b', 'a.user_id=b.user_id')
            ->where('b.openid', $openid)->where('time', date('Y-m-d', time()))->find();
        if ($res) {
            return 'ture';
        } else {
            return 'false';
        }
    }
}
