<?php

namespace app\index\controller;

use think\Cache;
use think\Db;

class Train
{
    public function index()
    {
        $openid = Cache::get('openid');
        $data = Db::name('train')->alias('a')->join('user b', 'a.organiser=b.real_name')
            ->where('b.openid', $openid)->where('a.status', 0)->field('a.id,a.content,a.time,a.status,a.limit')->find();
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
        $datatime = $date . ' ' . $time.':00';
        $limit = $_REQUEST['limit'];
        $data = [
            'organiser' => $organiser,
            'content' => $content,
            'time' => $datatime,
            'create_time' => time(),
            'limit'=>$limit
        ];
        $sql = Db::name('train')->insert($data);
        if ($sql) {
            return '提交成功';
        }
    }
    public function update()
    {
        $id = $_REQUEST['id'];
        $content = $_REQUEST['content'];
        $date = $_REQUEST['date'];
        $time = $_REQUEST['time'];
        $datatime = $date . ' ' . $time.':00';
        $limit = $_REQUEST['limit'];
        $sql = Db::name('train')
            ->where('id', $id)
            ->update([
                'content' => $content,
                'time' => $datatime,
                'update_time' => time(),
                'limit'=>$limit
            ]);
        if ($sql) {
            return '修改成功';
        }
    }
    public function get_list()
    {
        
        $list = Db::name('train')->where('status', 0)->order('create_time desc')->select();
        foreach ($list as $key => $value) {
            $list[$key]['num'] = Db::name('train_participants')->where('train_id', $list[$key]['id'])->count();
            $user=Db::name('user')->where('openid',Cache::get('openid'))->value('real_name');
            if (time() > strtotime($list[$key]['time']) - 600 || $list[$key]['limit'] <= $list[$key]['num'] || $list[$key]['organiser']==$user) {
                $list[$key]['disabled'] = true;
            }else{

                $list[$key]['disabled'] = false;
            }
            $res = Db::name('train_participants')->alias('a')
            ->join('user b','a.participant=b.real_name')
            ->where('a.train_id', $list[$key]['id'])
            ->where('b.openid',Cache::get('openid'))->find();
            if($res){
                $list[$key]['join'] = true;
            }else{
                $list[$key]['join'] = false;
            }
        }
        return json_encode($list);
    }
    public function cancel()
    {
        $id = $_GET['id'];
        $res = Db::name('train')->where('id', $id)->setField('status', 2);
        if ($res) {
            return "取消成功";
        }
    }

    public function join()
    {
        $id = $_GET['id'];
        $user = Db::name('user')->where('openid', Cache::get('openid'))->find();
        $data = ['train_id' => $id, 'participant' => $user['real_name'], 'create_time' => time()];
        $join = Db::name('train_participants')->insert($data);
        if ($join) {
            $content=Db::name('train')->where('id',$id)->value('content');
            write_log($user['user_id'],'参加培训:'.$content);
            return "参加成功";
        }

    }
    public function auto()
    {
        //超过时间三天的活动下架
        $overTime = date('Y-m-d H:s:m', time() - 86400 * 3);
        $res = Db::name('train')->whereTime('time', '<', $overTime)->where('status', 0)->setField('status', 1);
        //活动下架时评分数超过一半自动加分
        if ($res) {
            $ornagiser = Db::name('train')->alias('a')->join('user b', 'b.real_name=a.organiser')->field('a.id,a.content,a.limit,b.user_id')->where('a.status', 1)->select();
            foreach ($ornagiser as $key => $value) {
                $count = Db::name('train_participants')->where('train_id', $ornagiser[$key]['id'])->count();
                if ($count >= $ornagiser[$key]['limit'] / 2) {
                    //将记录修改为 下架后加分 状态
                    Db::name('train')->where('id', $ornagiser[$key]['id'])->setField('status', 3);
                    //组织者加分
                    $data = ['user_id' => $ornagiser[$key]['user_id'], 'integral_id' => '23', 'create_time' => time()];
                    Db::name('user_integral')->insert($data);
                    Db::name('user')->where('user_id', $ornagiser[$key]['user_id'])->setInc('score', 20);
                    write_log($ornagiser[$key]['user_id'], '你组织的培训:' . $ornagiser[$key]['content'] . ' 已结束，获得20积分');
                    //参与者加分
                    $participants = Db::name('train_participants')->alias('a')->join('user b', 'b.real_name=a.participant')
                        ->where('a.train_id', $ornagiser[$key]['id'])->where('a.score', 'not null')->field('b.user_id')->select();
                    foreach ($participants as $k => $v) {
                        $data = ['user_id' => $participants[$k]['user_id'], 'integral_id' => '24', 'create_time' => time()];
                        Db::name('user_integral')->insert($data);
                        Db::name('user')->where('user_id', $participants[$k]['user_id'])->setInc('score', 2);
                        write_log($participants[$k]['user_id'], '你参加的培训:' . $ornagiser[$key]['content'] . ' 已结束，获得2积分');
                    }
                }else{
                    //下架但不加分
                    write_log($ornagiser[$key]['user_id'], '你组织的培训:' . $ornagiser[$key]['content'] . ' 已结束，由于评分人数过少，无法获取积分');
                }
            }
        }
    }
}
