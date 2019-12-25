<?php

namespace app\index\controller;

use think\Db;

class Sundry
{
    //公益
    public function employ()
    {
        $real_name = $_GET['name'];
        $type = $_GET['type'];
        $user_id = Db::name('user')->where('real_name', $real_name)->value('user_id');
        $data = [
            'user_id' => $user_id,
            'type' => $type,
            'create_time' => time()
        ];
        $res = Db::name('employ')->insert($data);
        if ($res) {
            if ($type == 0) {
                $data = ['user_id' => $user_id, 'integral_id' => '8', 'create_time' => time()];
                Db::name('user_integral')->insert($data);
                Db::name('user')->where('real_name', $real_name)->setInc('score', 10);
                write_log($user_id, '入职成功，获得10积分');
            } elseif ($type == 1) {
                $data = ['user_id' => $user_id, 'integral_id' => '9', 'create_time' => time()];
                Db::name('user_integral')->insert($data);
                Db::name('user')->where('real_name', $real_name)->setInc('score', 5);
                write_log($user_id, '推荐面试成功，获得5积分');
            }
            return "操作完成";
        }
    }
    //公益
    public function public_good()
    {
        $real_name = $_GET['name'];
        $type = $_GET['type'];
        $user_id = Db::name('user')->where('real_name', $real_name)->value('user_id');
        $data = [
            'user_id' => $user_id,
            'type' => $type,
            'create_time' => time()
        ];
        $res = Db::name('public_good')->insert($data);
        if ($res) {
            if ($type == 0) {
                Db::name('user')->where('user_id', $user_id)->setInc('score', 10);
                write_log($user_id, '捐献办公物品，获得10积分');
            } elseif ($type == 1) {
                Db::name('user')->where('user_id', $user_id)->setInc('score', 5);
                write_log($user_id, '献血，获得5积分');
            }elseif ($type == 2) {
                Db::name('user')->where('user_id', $user_id)->setInc('score', 3);
                write_log($user_id, '捐书，获得5积分');
            }elseif ($type == 3) {
                Db::name('user')->where('user_id', $user_id)->setInc('score', 3);
                write_log($user_id, '捐发票，获得5积分');
            }
            return "操作完成";
        }
    }
}
