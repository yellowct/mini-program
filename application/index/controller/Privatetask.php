<?php

namespace app\index\controller;

use think\Cache;
use think\Db;

class Privatetask
{
    public function index()
    {
        $openid = Cache::get('openid');
        $data = Db::name('task')->alias('a')->join('user b', 'a.organiser=b.real_name')
            ->where('b.openid', $openid)->where('a.status', 0)->where('a.task_type', 1)
            ->field('a.id,a.content,a.time,a.status,a.limit,a.score,b.type')->find();
        $data = json_encode($data);
        return $data;
    }

    public function insert()
    {
        $openid = Cache::get('openid');
        $organiser = Db::name('user')->where('openid', $openid)->value('real_name');
        $content = $_REQUEST['content'];
        $date = $_REQUEST['date'];
        $time = $_REQUEST['time'];
        $datatime = $date . ' ' . $time . ':00';
        $limit = $_REQUEST['limit'];
        $score = $_REQUEST['score'];
        $user_score = Db::name('user')->where('openid', $openid)->value('score');
        if ($user_score < $score * $limit) {
            return "积分不足";
        } else {
            $data = [
                'organiser' => $organiser,
                'content' => $content,
                'time' => $datatime,
                'create_time' => time(),
                'limit' => $limit,
                'score' => $score,
                'task_type' => 1
            ];
            $sql = Db::name('task')->insert($data);
            if ($sql) {
                return '提交成功';
            }
        }
    }
    public function update()
    {
        $id = $_REQUEST['id'];
        $content = $_REQUEST['content'];
        $date = $_REQUEST['date'];
        $time = $_REQUEST['time'];
        $datatime = $date . ' ' . $time . ':00';
        $limit = $_REQUEST['limit'];
        $score = $_REQUEST['score'];
        $user_score = Db::name('user')->where('openid', Cache::get('openid'))->value('score');
        if ($user_score < $score * $limit) {
            return "积分不足";
        } else {
            $sql = Db::name('task')
                ->where('id', $id)
                ->update([
                    'content' => $content,
                    'time' => $datatime,
                    'update_time' => time(),
                    'limit' => $limit,
                    'score' => $score
                ]);
            if ($sql) {
                return '修改成功';
            }
        }
    }
    public function get_list()
    {

        $list = Db::name('task')->where('status', 0)->where('task_type', 1)->order('create_time desc')->select();
        // dump($list);
        foreach ($list as $key => $value) {
            $list[$key]['num'] = Db::name('task_participants')->where('task_id', $list[$key]['id'])->count();
            $user = Db::name('user')->where('openid', Cache::get('openid'))->value('real_name');
            // dump($user);
            //开始前10分钟/参与人数已满/用户为组织者，都不能参加
            if (time() > strtotime($list[$key]['time']) - 600 || $list[$key]['limit'] <= $list[$key]['num'] || $list[$key]['organiser'] == $user) {
                $list[$key]['disabled'] = true;
            } else {

                $list[$key]['disabled'] = false;
            }
            $res = Db::name('task_participants')->alias('a')
                ->join('user b', 'a.participant=b.real_name')
                ->where('a.task_id', $list[$key]['id'])
                ->where('b.openid', Cache::get('openid'))->find();
            if ($res) {
                $list[$key]['join'] = true;
            } else {
                $list[$key]['join'] = false;
            }
        }
        return json_encode($list);
    }
    public function cancel()
    {
        $id = $_GET['id'];
        $res = Db::name('task')->where('id', $id)->setField('status', 2);
        if ($res) {
            return "取消成功";
        }
    }

    public function join()
    {
        $id = $_GET['id'];
        $user = Db::name('user')->where('openid', Cache::get('openid'))->find();
        $data = ['task_id' => $id, 'participant' => $user['real_name'], 'create_time' => time()];
        $join = Db::name('task_participants')->insert($data);
        if ($join) {
            $content = Db::name('task')->where('id', $id)->value('content');
            write_log($user['user_id'], '参加任务:' . $content);
            return "参加成功";
        }
    }
    public function auto()
    {
        //超过时间三天的活动下架
        $overTime = date('Y-m-d H:s:m', time() - 86400 * 3);
        $res = Db::name('task')->whereTime('time', '<', $overTime)->where('status', 0)->setField('status', 3);
    }

    //发布任务者确认后给参与者加分
    public function confirm()
    {
        $id = $_GET['id'];
        $task = Db::name('task')->where('id', $id)->find();

        //任务状态改为2
        Db::name('task')->where('id', $id)->setField('status', 1);

        //组织者扣分
        $res=Db::name('user')->where('real_name', $task['organiser'])->setDec('score', $task['score']);
        if($res){
            $organiser=Db::name('user')->where('real_name', $task['organiser'])->value('user_id');
            write_log($organiser, '你发出的任务:' . $task['content'] . ' 已结束，你被扣除了' . $task['score'] . '积分');
        }

        //参与者加分
        $count = Db::name('task_participants')->where('task_id', $id)->count();
        if($count && $count != 0){
            $participants = Db::name('task_participants')->alias('a')->join('user b', 'b.real_name=a.participant')
            ->where('a.task_id', $id)->field('b.user_id')->select();

        foreach ($participants as $k => $v) {
            Db::name('user')->where('user_id', $participants[$k]['user_id'])->setInc('score', $task['score'] / $count);
            write_log($participants[$k]['user_id'], '你参加的任务:' . $task['content'] . ' 已结束，你获得了' . $task['score']/$count . '积分');
        }
        }
       
    }
}
