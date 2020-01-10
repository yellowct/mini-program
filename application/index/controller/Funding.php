<?php

namespace app\index\controller;

use think\Cache;
use think\Db;

class Funding
{
    public function index()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $data = Db::name('funding')->alias('a')->join('user b', 'a.organiser=b.real_name')
            ->where('b.openid', $openid)->where('a.status', 0)->field('a.id,a.content,a.status')->find();
        $data = json_encode($data);
        return $data;
    }

    public function insert()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $organiser = Db::name('user')->where('openid', $openid)->value('real_name');
        $content = $_REQUEST['content'];
        $data = [
            'organiser' => $organiser,
            'content' => $content,
            'create_time' => time(),
        ];
        $sql = Db::name('funding')->insert($data);
        if ($sql) {
            return '提交成功，等待审核';
        }
    }
    public function update()
    {
        $id = $_REQUEST['id'];
        $content = $_REQUEST['content'];
        $sql = Db::name('funding')
            ->where('id', $id)
            ->update([
                'content' => $content,
                'update_time' => time(),
            ]);
        if ($sql) {
            return '修改成功，等待审核';
        }
    }
    public function get_list()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $user = Db::name('user')->where('openid', $openid)->find();
        $join_id = Db::name('funding_participants')->where('participant', $user['user_id'])->column('funding_id');
        if ($user['type'] == 0) {
            $list = Db::name('funding')->where('status', 'in', '0,1')->where('id', 'not in', $join_id)->order('status')->select();
        } elseif ($user['type'] == 1) {
            $list = Db::name('funding')->where('status', 1)->where('id', 'not in', $join_id)->order('create_time desc')->select();
        }
        return json_encode($list);
    }
    public function access()
    {
        $id = $_GET['id'];
        Db::name('funding')->where('id', $id)->setField('status', 1);
        //给组织者加分
        $organiser = Db::name('funding')->alias('a')->join('user b', 'a.organiser=b.real_name')
            ->field('b.user_id')->find();
        $data = ['user_id' => $organiser['user_id'], 'integral_id' => '15', 'create_time' => time()];
        Db::name('user_integral')->insert($data);
    }
    public function refuse()
    {
        $id = $_GET['id'];
        Db::name('funding')->where('id', $id)->setField('status', 2);
    }
    public function join()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $id = $_GET['id'];
        $user = Db::name('user')->where('openid', $openid)->value('user_id');
        $data = ['funding_id' => $id, 'participant' => $user, 'create_time' => time()];
        $join = Db::name('funding_participants')->insert($data);
        //给参加者加分
        if ($join) {
            $data = ['user_id' => $user, 'integral_id' => '16', 'create_time' => time()];
            Db::name('user_integral')->insert($data);
        }
    }
}
