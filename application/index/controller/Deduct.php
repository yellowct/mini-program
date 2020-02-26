<?php

namespace app\index\controller;

use think\Cache;
use think\Db;

class Deduct
{
    public function get_list()
    {
        $list = Db::name('deduct')->order('create_time desc')->select();
        foreach ($list as $key => $value) {
            $list[$key]['real_name'] = Db::name('user')->where('user_id', $value['user_id'])->value('real_name');
        }
        return json_encode($list);
    }

    public function insert()
    {
        $real_name = $_REQUEST['real_name'];
        $type = $_REQUEST['type'];
        $user_id = Db::name('user')->where('real_name', $real_name)->value('user_id');
        $data = [
            'type'=>$type,
            'user_id' => $user_id,
            'create_time' => date('Y-m-d H:i:s', time()),
        ];
        $sql = Db::name('deduct')->insert($data);
        if ($sql) {
            if($type==1){
                Db::name('user')->where('user_id', $user_id)->setDec('score', 5);
                write_log($user_id, '违反考勤，扣除5积分');
            } if($type==2){
                Db::name('user')->where('user_id', $user_id)->setDec('score', 5);
                write_log($user_id, '缺少活动积极性，扣除5积分');
            } if($type==3){
                Db::name('user')->where('user_id', $user_id)->setDec('score', 3);
                write_log($user_id, '失约(领取公司任务未及时完成)，扣除3积分');
            } if($type==4){
                Db::name('user')->where('user_id', $user_id)->setDec('score', 10);
                write_log($user_id, '滥用公司资源，扣除10积分');
            } if($type==5){
                Db::name('user')->where('user_id', $user_id)->setField('score', 0);
                write_log($user_id, '在外兼职，积分已被清零');
            } if($type==6){
                Db::name('user')->where('user_id', $user_id)->setField('score', 0);
                write_log($user_id, '收受回扣，积分已被清零');
            } if($type==7){
                Db::name('user')->where('user_id', $user_id)->setField('score', 0);
                write_log($user_id, '离职，积分已被清零');
            }
            return '提交成功';
        }
    }

}
