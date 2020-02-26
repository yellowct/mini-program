<?php

namespace app\index\controller;

use think\Cache;
use think\Db;


class Exchange
{
    public function index()
    {
        $type=Db::name('exchange')->distinct(true)->column('type');
        foreach ($type as $key => $value) {
            $list[]=Db::name('exchange')->where('type',$value)->field('content')->select();
        }
        return json_encode($list);
    }

    public function get_list(){
        $list=Db::name('exchange_list')->order('status')->select();
        foreach ($list as $key => $value) {
            $list[$key]['name']=Db::name('user')->where('user_id', $value['user_id'])->value('real_name');
            $list[$key]['content']=Db::name('exchange')->where('id', $value['exchange_id'])->value('content');
        }
        return json_encode($list);
    }

    public function change()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $user_id = Db::name('user')->where('openid', $openid)->value('user_id');
        $content = $_GET['content'];
        $exchange = Db::name('exchange')->where('content', $content)->find();
        if($exchange['type']==0){
            $score='100';   
        }elseif($exchange['type']==1){
            $score='200';
        }elseif($exchange['type']==2){
            $score='500';
        }elseif($exchange['type']==3){
            $score='1000';
        }elseif($exchange['type']==4){
            $score='2000';
        }else {
            $score='5000';
        }
        Db::name('user')->where('openid',$openid)->setDec('score',$score);
        $data = [
            'user_id' => $user_id,
            'exchange_id' => $exchange['id'],
            'create_time'=>date('Y-m-d H:i:s',time()),
        ];
        $res=Db::name('exchange_list')->insert($data);
        if($res){
            return ('已申请，待处理');
        }        
    }
    public function confirm()
    {
        $id = $_GET['id'];
        $res=Db::name('exchange_list')->where('id',$id)->setField('status',1);
        if($res){
            $exchange=Db::name('exchange_list')->where('id',$id)->find();
            $content=Db::name('exchange')->where('id',$exchange['exchange_id'])->value('content');
            write_log($exchange['user_id'], '兑换'.$content.'成功');
            return '确认兑换';
        }
    }


   
}
