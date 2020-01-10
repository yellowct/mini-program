<?php

namespace app\index\controller;

use think\Cache;
use think\Db;

class Mine
{
    public function get_userInfo()
    {
        $open=$_REQUEST['open'];
        $openid = Cache::get($open);
        // echo $openid;
        $userInfo = Db::name('user')->where('openid', $openid)->find();
        return json_encode($userInfo);
    }

    //获取用户积分
    public function get_integral()
    {
        $open=$_REQUEST['open'];
        $openid = Cache::get($open);
        $integral = Db::name('user_integral')->alias('a')
            ->join('user b', 'b.user_id=a.user_id')
            ->join('integral c', 'c.integral_id=a.integral_id')
            ->where('b.openid', $openid)->sum('c.score');
        return json_encode($integral);
    }

    public function get_list()
    {
        $open=$_REQUEST['open'];
        $openid = Cache::get($open);
        $log =  Db::name('log')->alias('a')
            ->join('user b', 'b.user_id=a.user_id')
            ->limit(10)
            ->where('b.openid', $openid)
            ->field('a.id,a.action,a.create_time')->order('create_time desc')
            ->select();
        foreach ($log as $key => $value) {
            $log[$key]['create_time'] = date('Y-m-d H:i:s', $log[$key]['create_time']);
            $list_id[] = $value['id'];
        }
        $read_id = Db::name('read')->alias('a')
            ->join('user b', 'b.user_id=a.user_id')
            ->where('b.openid', $openid)
            ->where('a.type', 0)
            ->column('a.read_id');
        $result = array_diff($list_id, $read_id);
        if ($result == null) {
            $log['unread'] = false;
        } else {
            $log['unread'] = true;
        }

        $startTime = date('Y-m-d H:s:m', time() - 86400 * 3);
        $endTime = date('Y-m-d H:s:m', time());
        $activity = Db::name('activity_participants')->alias('a')
            ->join('user b', 'b.real_name=a.participant')
            ->join('activity c', 'a.activity_id=c.id')
            ->where('c.status', 0)->where('b.openid', $openid)
            ->whereTime('time', 'between', [$startTime, $endTime])
            ->field('a.id,a.score,c.content,c.time,c.create_time')->order('create_time desc')
            ->distinct(true)->select();
        $count = Db::name('activity_participants')->alias('a')
            ->join('user b', 'b.real_name=a.participant')
            ->join('activity c', 'a.activity_id=c.id')
            ->where('c.status', 0)->where('b.openid', $openid)->where('a.score', 'null')
            ->whereTime('time', 'between', [$startTime, $endTime])
            ->count();
        if ($count && $count != 0) {
            $activity['unread'] = true;
        } else {
            $activity['unread'] = false;
        }
        $train = Db::name('train_participants')->alias('a')
            ->join('user b', 'b.real_name=a.participant')
            ->join('train c', 'a.train_id=c.id')
            ->where('c.status', 0)->where('b.openid', $openid)
            ->whereTime('time', 'between', [$startTime, $endTime])
            ->field('a.id,a.score,c.content,c.time,c.create_time')->order('create_time desc')
            ->distinct(true)->select();
        $count = Db::name('train_participants')->alias('a')
            ->join('user b', 'b.real_name=a.participant')
            ->join('train c', 'a.train_id=c.id')
            ->where('c.status', 0)->where('b.openid', $openid)->where('a.score', 'null')
            ->whereTime('time', 'between', [$startTime, $endTime])
            ->count();
        if ($count && $count != 0) {
            $train['unread'] = true;
        } else {
            $train['unread'] = false;
        }
        $public_task = Db::name('task')
            ->where('status', 'in', [0, 1])->where('task_type', 0)
            ->whereTime('time', 'between', [$startTime, $endTime])
            ->order('create_time desc')->select();
        // $count = Db::name('task')
        //     ->where('status', 0)->where('task_type', 0)
        //     ->whereTime('time', 'between', [$startTime, $endTime])
        //     ->count();
        // if ($count && $count != 0) {
        //     $public_task['unread'] = true;
        // } else {
        //     $public_task['unread'] = false;
        // }
        $private_task = Db::name('task')
            ->where('status', 'in', [0, 1])->where('task_type', 1)
            ->whereTime('time', 'between', [$startTime, $endTime])
            ->order('create_time desc')->select();

        // $count = Db::name('task')
        //     ->where('status', 0)->where('task_type', 1)
        //     ->whereTime('time', 'between', [$startTime, $endTime])
        //     ->count();
        // if ($count && $count != 0) {
        //     $private_task['unread'] = true;
        // } else {
        //     $private_task['unread'] = false;
        // }
        $list[0] = $log;
        $list[1] = $activity;
        $list[2] = $train;
        $list[3] = $public_task;
        $list[4] = $private_task;
        // dump($list);
        return json_encode($list);
    }

    //已读列表
    public function read()
    {
        $index = $_REQUEST['index'];
        $open=$_REQUEST['open'];
        $openid = Cache::get($open);
        $item = json_decode($_REQUEST['item'], true);
        foreach ($item as $key => $value) {
            $data = [
                'user_id' => Db::name('user')->where('openid', $openid)->value('user_id'),
                'read_id' => $value['id'],
                'type' => $index
            ];
            Db::name('read')->insert($data);
        }
    }

    public function set_score()
    {
        
        $parentIndex = $_GET['parentIndex'];
        $id = $_GET['id'];
        $score = $_GET['score'];
        if ($parentIndex == 1) {
            Db::name('activity_participants')->where('id', $id)->setField('score', $score);
        } elseif ($parentIndex == 2) {
            Db::name('train_participants')->where('id', $id)->setField('score', $score);
        }
        return "评分成功";
    }
    public function update_name()
    {
        $open=$_REQUEST['open'];
        $openid = Cache::get($open);
        $real_name = $_GET['real_name'];
        $res = Db::name('user')->where('openid', $openid)->setField('real_name', $real_name);
        if ($res) {
            return '修改成功';
        }
    }
}
