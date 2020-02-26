<?php
namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Permonth extends Command
{
    protected function configure()
    {
        $this->setName('Permonth')->setDescription('Permonth');
    }
    protected function execute(Input $input, Output $output)
    {
        //全勤加分
        $end = date('Y-m-d', strtotime('-1 day'));
        $start = date('Y-m-1', strtotime('-1 day'));
        //统计需要打卡的天数
        $list = Db::name('clock')->where('date', 'between', [$start, $end])->where('clock_in', 'not null')->where('clock_out', 'not null')->field('date')->distinct(true)->select();
        $count = count($list);
        // // 判断后加分
        // $user = Db::name('user')->field('user_id')->select();
        // foreach ($user as $key => $value) {
        //     $dates = Db::name('clock')->where('date', 'between', [$start, $end])->where('clock_in', 'not null')
        //         ->where('clock_out', 'not null')->where('user_id', $value['user_id'])->field('date')->count();
        //     if ($dates == $count) {
        //         $res = Db::name('user')->where('user_id', $value['user_id'])->setInc('score', 5);
        //         if ($res) {
        //             write_log($user[$key]['user_id'], '获得5积分（每月工龄分）');
        //         }
        //     }
        // }
        // 按打卡用户分组统计
        $list = Db::name('clock')->where('date', 'between', [$start, $end])->where('clock_in', 'not null')->where('clock_out', 'not null')
            ->field('user_id,COUNT(*) as total')
            ->group('user_id')
            ->select();
        // 积分
        foreach ($list as $key => $value) {
            if ($list[$key]['total'] == $count) {
                $res = Db::name('user')->where('user_id', $value['user_id'])->setInc('score', 5);
                if ($res) {
                    write_log($value['user_id'], '获得5积分（每月工龄分）');
                }
            }
        }
        $output->writeln('done');
    }
}
