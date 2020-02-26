<?php
namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Clock extends Command
{
    protected function configure()
    {
        $this->setName('Clock')->setDescription('Clock out');
    }
    protected function execute(Input $input, Output $output)
    {
        // if (date("w") != 1) {
        //     $id = Db::name('clock')->where('clock_out', 'not null')->where('date', date('Y-m-d', strtotime('-1 day')))->max('id');
        // } else {
        //     $id = Db::name('clock')->where('clock_out', 'not null')->where('date', date('Y-m-d', strtotime('-3 day')))->max('id');
        // }

        //下班打卡加分推送
        $id = Db::name('clock')->where('clock_out', 'not null')->where('date', date('Y-m-d', strtotime('-1 day')))->max('id');
        if ($id) {
            $clock = Db::name('clock')->where('id', $id)->find();
            //加分
            Db::name('user')->where('user_id', $clock['user_id'])->setInc('score', 1);
            //推送
            write_log($clock['user_id'], '这么卖力工作，付出会得到回报的！');
            $openid=Db::name('user')->where('user_id', $clock['user_id'])->value('openid');
            $template_id = "dXK8NDVwtJdpJqjPXZlW7ChBTDWRnxJp4Mxb2ssSRxQ";
            $data = array( 
                "touser" => $openid,
                "template_id" => $template_id,
                "page" => "index",
                "data" => array(
                    "phrase4" => array(
                        "value" => "昨日下班打卡",
                    ),
                    "time5" => array(
                        "value" => $clock['clock_out'],
                    ),
                    "thing6" => array(
                        "value" => "最后一个打卡,获得1积分",
                    ),
                ),

            );
            $res = order_msg($data);
        }

        //更新抽奖池
        Db::name('turntable')->where('type', 5)->setField('num',2);
        

        
        $output->writeln("done");
    }
}
