<?php

namespace app\index\controller;

use think\Cache;
use think\Db;

class Book
{
    public function index()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $data = Db::name('book')->alias('a')->join('user b', 'a.user_id=b.user_id')
            ->where('b.openid', $openid)->where('a.status', 0)->field('a.id,a.content,a.book_name,a.status,a.imgUrl')->find();
        $data = json_encode($data);
        return $data;
    }

    public function get_list()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $user_type = Db::name('user')->where('openid', $openid)->value('type');
        if ($user_type == 0) {
            $list = Db::name('book')->where('status','in',[0,1])->order('status')->select();
        } else {
            $list = Db::name('book')->where('status', 1)->order('create_time desc')->select();
        }
        foreach ($list as $key => $value) {
            $list[$key]['real_name'] = Db::name('user')->where('user_id', $value['user_id'])->value('real_name');
        }
        return json_encode($list);
    }

    public function insert()
    {
        $open = $_REQUEST['open'];
        $openid = Cache::get($open);
        $book_name = $_REQUEST['book_name'];
        $content = $_REQUEST['content'];
        $imgUrl= $_REQUEST['imgUrl'];
        $user_id = Db::name('user')->where('openid', $openid)->value('user_id');
        $data = [
            'imgUrl'=>$imgUrl,
            'book_name' => '<<' . $book_name . '>>',
            'content' => $content,
            'user_id' => $user_id,
            'create_time' => date('Y-m-d H:i:s', time()),
        ];
        $sql = Db::name('book')->insert($data);
        if ($sql) {
            return '提交成功';
        }
    }
    public function update()
    {
        $id = $_REQUEST['id'];
        $book_name = $_REQUEST['book_name'];
        $content = $_REQUEST['content'];
        $imgUrl= $_REQUEST['imgUrl'];
        if($imgUrl){
            $sql = Db::name('book')
            ->where('id', $id)
            ->update([
                'imgUrl'=>$imgUrl,
                'book_name' => $book_name,
                'content' => $content,
                'update_time' => date('Y-m-d H:i:s', time()),
            ]);
        }else {
            $sql = Db::name('book')
            ->where('id', $id)
            ->update([
                'book_name' => $book_name,
                'content' => $content,
                'update_time' => date('Y-m-d H:i:s', time()),
            ]); 
        }
        
        if ($sql) {
            return '修改成功';
        }
    }

    public function cancel()
    {
        $id = $_GET['id'];
        $res = Db::name('book')->where('id', $id)->setField('status', 2);
        if ($res) {
            return "取消成功";
        }
    }

    public function confirm()
    {
        $id = $_GET['id'];
        $res = Db::name('book')->where('id', $id)->setField('status', 1);
        if ($res) {
            $book = Db::name('book')->where('id', $id)->find();
            Db::name('user')->where('user_id', $book['user_id'])->setInc('score', 5);
            write_log($book['user_id'], '分享<<' . $book['book_name'] . '>>成功');
            return '确认分享';
        }
    }

    public function upload()
    {
        $file = request()->file('img');
        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads/book');
        if ($info) {
            $saveName=str_replace('\\','/',$info->getSaveName());
            $imgUrl='https://app.genhigh.net/mini/public/uploads/book/'.$saveName;
            $data=['msg'=>'上传成功','url'=>$imgUrl];
            return json_encode($data);
        } else {
            return "上传失败";
        }
    }

}
