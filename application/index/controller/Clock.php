<?php

namespace app\index\controller;

use think\Cache;
use think\Db;

class Clock
{
    public function index()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $list = Db::name('clock')->alias('a')->join('user b', 'b.user_id=a.user_id')
            ->where('b.openid', $openid)
            ->field('a.*')->select();
        $arr = [];
        foreach ($list as $key => $value) {
            $arr[$key]['id'] = $list[$key]['date'];
            if ($list[$key]['clock_in'] != '' && $list[$key]['clock_out'] == '') {
                $arr[$key]['style'] = 'background: #E6A23C;color: #ffff00;border-radius: 30px;';
            } elseif ($list[$key]['clock_in'] == '' && $list[$key]['clock_out'] != '') {
                $arr[$key]['style'] = 'background: #0A2355;color: #fff; border-radius: 30px;';
            } else {
                $arr[$key]['style'] = 'background: #67C23A;color: #666; border-radius: 30px;';
            }
        }
        return json_encode($arr);
    }
    public function clock_in()
    {
        $date = $_GET['date'];
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $user_id = Db::name('user')->where('openid', $openid)->value('user_id');
        $res = Db::name('clock')->where('user_id', $user_id)->where('date', $date)->find();
        if ($res) {
            return "您已打卡！";
        } else {
            $data = [
                'user_id' => $user_id,
                'clock_in' => date("Y-m-d H:i:s", time()),
                'date' => date("Y-m-d", time()),
            ];

            $res = Db::name('clock')->where('date', date("Y-m-d", time()))->find();
            if ($res) {
                Db::name('clock')->insert($data);

            } else {
                Db::name('clock')->insert($data);
                //第一个到公司
                //添加积分记录
                $data = ['user_id' => $user_id, 'integral_id' => '1', 'create_time' => time()];
                Db::name('user_integral')->insert($data);
                //加分
                Db::name('user')->where('user_id', $user_id)->setInc('score', 1);
                write_log($user_id, '今天是第一个到公司，赞啊！');
                // 发送订阅消息
                //  $open=$_REQUEST['open'];
                // $openid = Cache::get($open);
                $template_id = "dXK8NDVwtJdpJqjPXZlW7OpSK9LzKKhk3B7UDjPj0eo";
                $data = array( 
                    "touser" => $openid,
                    "template_id" => $template_id,
                    // "form_id"=>$template_id,
                    "page" => "index",
                    // "form_id"=>$formid,
                    "data" => array(
                        "phrase4" => array(
                            "value" => "上班打卡",
                        ),
                        "time5" => array(
                            "value" => date("Y-m-d H:i:s", time()),
                        ),
                        "thing6" => array(
                            "value" => "今天第一个打卡,获得1积分",
                        ),
                    ),
                    // "emphasis_keyword"=>"keyword1.DATA",//需要进行加大的消息
                );
                $res = order_msg($data);
            }
            return "上班打卡";
        }
    }

    public function clock_out()
    {
        $date = $_GET['date'];
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $user_id = Db::name('user')->where('openid', $openid)->value('user_id');
        $res = Db::name('clock')->where('user_id', $user_id)->where('date', $date)->find();
        if (!$res) {
            $data = [
                'user_id' => $user_id,
                'clock_out' => date("Y-m-d H:i:s", time()),
                'date' => date("Y-m-d", time()),
            ];
            Db::name('clock')->insert($data);
            return "下班打卡";
        } elseif ($res['clock_out'] == null) {
            $clock_out = date("Y-m-d H:i:s", time());
            Db::name('clock')->where('id', $res['id'])->setField('clock_out', $clock_out);
            return "下班打卡";
        } else {
            return "您已打卡！";
        }
    }

    // //自动执行
    // public function auto()
    // {

    //     $open = $_REQUEST['open'];
    //     $openid = Cache::get($open);
    //     $user_id = Db::name('user')->where('openid', $openid)->value('user_id');

    //     // // 昨天最晚打卡
    //     // $res = Db::name('log')
    //     //     ->where('create_time', 'between', [strtotime(date(("Y-m-d 00:00:00"), time())), strtotime(date(("Y-m-d 23:59:59"), time()))])
    //     //     ->where('action', '这么卖力工作，付出会得到回报的！')->find();
    //     // if (!$res && date("w") != 6 && date("w") != 7) {
    //     //     if (date("w") != 1) {
    //     //         $id = Db::name('clock')->where('clock_out', 'not null')->where('date', date('Y-m-d', strtotime('-1 day')))->max('id');
    //     //     } else {
    //     //         $id = Db::name('clock')->where('clock_out', 'not null')->where('date', date('Y-m-d', strtotime('-3 day')))->max('id');
    //     //     }
    //     //     if ($id) {
    //     //         $user = Db::name('clock')->where('id', $id)->value('user_id');
    //     //         //添加积分记录
    //     //         $data = ['user_id' => $user_id, 'integral_id' => '2', 'create_time' => time()];
    //     //         Db::name('user_integral')->insert($data);
    //     //         //加分
    //     //         Db::name('user')->where('user_id', $user_id)->setInc('score', 1);
    //     //         write_log($user_id, '这么卖力工作，付出会得到回报的！');
    //     //     }
    //     // }

    //     //周末自动打卡
    //     // $res = Db::name('clock')->where('date', date('Y-m-d', strtotime('-1 day')))->find();
    //     // if (date("w") == 1 && !$res) {
    //     //     $data = [
    //     //         [
    //     //             'user_id' => $user_id,
    //     //             'date' => date('Y-m-d', strtotime('-2 day')),
    //     //             'clock_in' => date('Y-m-d', strtotime('-2 day')) . ' 09:00:00',
    //     //             'clock_out' => date('Y-m-d', strtotime('-2 day')) . ' 18:00:00',
    //     //         ],
    //     //         [
    //     //             'user_id' => $user_id,
    //     //             'date' => date('Y-m-d', strtotime('-1 day')),
    //     //             'clock_in' => date('Y-m-d', strtotime('-1 day')) . ' 09:00:00',
    //     //             'clock_out' => date('Y-m-d', strtotime('-1 day')) . ' 18:00:00',
    //     //         ],
    //     //     ];

    //     //     Db::name('clock')->insertAll($data);
    //     // }

    //     //每月结算
    //     $date = substr(date('Y-m-d', time()), -2);
    //     if ($date == '01') {
    //         //工龄分：每月加5分
    //         $res = Db::name('user')->setInc('score', 5);
    //         if ($res) {
    //             $user = Db::name('user')->select();
    //             foreach ($user as $key => $value) {
    //                 $data = ['user_id' => $user[$key]['user_id'], 'integral_id' => '19', 'create_time' => time()];
    //                 Db::name('user_integral')->insert($data);
    //                 write_log($user[$key]['user_id'], '每月工龄分加5！');
    //             }
    //         }

    //         //全勤结算
    //         $last_month = date("Y-m-d", strtotime('-1 month'));
    //         $month = substr($last_month, 5, 2);
    //         $arr = ['01', '03', '05', '07', '08', '10', '12'];
    //         $begin_day = date('Y-m-01', strtotime('-1 month'));
    //         $end_day = date('Y-m-t', strtotime('-1 month'));
    //         $days = Db::name('clock')->whereTime('date', 'between', [$begin_day, $end_day])->where('user_id', $user_id)
    //             ->where('clock_in', 'not null')->where('clock_out', 'not null')->count();
    //         if (in_array($month, $arr) && $days == 31) {
    //             //添加积分记录
    //             $data = ['user_id' => $user_id, 'integral_id' => '3', 'create_time' => time()];
    //             Db::name('user_integral')->insert($data);
    //             //加分
    //             Db::name('user')->where('user_id', $user_id)->setInc('score', 10);
    //             write_log($user_id, '上月全勤，劳动模范，佩服佩服！');
    //         } elseif (!in_array($month, $arr) && $days == 30) {
    //             //添加积分记录
    //             $data = ['user_id' => $user_id, 'integral_id' => '3', 'create_time' => time()];
    //             Db::name('user_integral')->insert($data);
    //             //加分
    //             Db::name('user')->where('user_id', $user_id)->setInc('score', 10);
    //             write_log($user_id, '上月全勤，劳动模范，佩服佩服！');
    //         } elseif (in_array($month, $arr) && $days != 31) {
    //             write_log($user_id, '上月31天，成功打卡' . $days . '天，还需继续努力哦!');
    //         } else {
    //             write_log($user_id, '上月30天，成功打卡' . $days . '天，还需继续努力哦!');
    //         }
    //     }
    // }
}
