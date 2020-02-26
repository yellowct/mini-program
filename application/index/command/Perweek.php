<?php
namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Perweek extends Command
{
    protected function configure()
    {
        $this->setName('Perweek')->setDescription('Perweek');
    }
    protected function execute(Input $input, Output $output)
    {
        //更新抽奖池
        Db::name('turntable')->where('type', 'in', '1,4')->setField('num', 3);
        Db::name('turntable')->where('type', 3)->setField('num', 1);
        $output->writeln('done');
    }
}
