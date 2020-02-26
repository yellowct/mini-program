<?php

namespace app\index\controller;

use think\Db;
use think\Cache;

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

    
    public function public_good()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $type=$_REQUEST['type'];
        $imgUrl= $_REQUEST['imgUrl'];
        $user_id = Db::name('user')->where('openid', $openid)->value('user_id');
        $data = [
            'user_id' => $user_id,
            'imgUrl'=>$imgUrl,
            'type' => $type,
            'create_time' => date('Y-m-d H:i:s', time()),
        ];
        $res = Db::name('public_good')->insert($data);
        if ($res) {
            return "提交成功";
        }
    }

    public function confirm()
    {
        $id = $_GET['id'];
        $res = Db::name('public_good')->where('id', $id)->setField('status', 1);
        if ($res) {
            $res = Db::name('public_good')->where('id', $id)->find();
            if ($res['type'] == 0) {
                Db::name('user')->where('user_id', $res['user_id'])->setInc('score', 10);
                write_log($res['user_id'], '捐献办公物品，获得10积分');
            } elseif ($res['type'] == 1) {
                Db::name('user')->where('user_id', $res['user_id'])->setInc('score', 5);
                write_log($res['user_id'], '献血，获得5积分');
            }elseif ($res['type'] == 2) {
                Db::name('user')->where('user_id', $res['user_id'])->setInc('score', 3);
                write_log($res['user_id'], '捐书，获得5积分');
            }elseif ($res['type'] == 3) {
                Db::name('user')->where('user_id', $res['user_id'])->setInc('score', 3);
                write_log($res['user_id'], '捐发票，获得5积分');
            }
            return '确认捐赠';
        }
    }

    public function get_list()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $user_type = Db::name('user')->where('openid', $openid)->value('type');
        if ($user_type == 0) {
            $list = Db::name('public_good')->where('status','in',[0,1])->order('status')->select();
        } else {
            $list = Db::name('public_good')->where('status', 1)->order('create_time desc')->select();
        }
        foreach ($list as $key => $value) {
            $list[$key]['real_name'] = Db::name('user')->where('user_id', $value['user_id'])->value('real_name');
        }
        $list['user_type']=$user_type;
        return json_encode($list);
    }

    public function upload()
    {
        $file = request()->file('img');
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/sundry');
        if ($info) {
            $saveName=str_replace('\\','/',$info->getSaveName());
            $imgUrl='https://app.genhigh.net/mini/public/uploads/sundry/'.$saveName;
            $data=['msg'=>'上传成功','url'=>$imgUrl];
            return json_encode($data);
        } else {
            return "上传失败";
        }
    }
}
