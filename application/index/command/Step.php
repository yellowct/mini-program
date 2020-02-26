<?php
namespace app\index\command;

use think\console\Command;
use think\console\Input;
use think\console\Output;
use think\Db;

class Step extends Command
{
    protected function configure()
    {
        $this->setName('Step')->setDescription('Wechat step');
    }
    protected function execute(Input $input, Output $output)
    {
        //微信步数加分推送
        $step = Db::name('user')->max('step');
        if($step){
            $user=Db::name('user')->where('step',$step)->find();
            //加分
            Db::name('user')->where('user_id', $user['user_id'])->setInc('score', 1);
            //小程序推送
            write_log($user['user_id'], '昨日步数最高，获得1积分');
            //vx推送
            $template_id = "xBqQXAZP8hoIq1lm7jD8mn_DKNifcRJHgU3pFfDTK-4";
            $data = array( 
                "touser" => $user['openid'],
                "template_id" => $template_id,
                "page" => "index",
                "data" => array(
                    "date1" => array(
                        "value" => date('Y-m-d H:i:s', time()),
                    ),
                    "number2" => array(
                        "value" => 1,
                    ),
                    "thing3" => array(
                        "value" => "昨日微信步数最高($step)",
                    ),
                ),
            );
            $res = order_msg($data);
        }

        $output->writeln($step);
    }
}
