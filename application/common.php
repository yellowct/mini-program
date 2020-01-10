<?php

// 应用公共文件
use think\Db;
// use think\Log

function write_log($user,$action){
    $data=[
        'user_id'=>$user,
        'action'=>$action,
        'create_time'=>time()
    ];
    Db::name('log')->insert($data);

}

function order_msg($data)
{
  //获取access_token
  $appid="wx0a26ad2d7d081bf3";
  $appsecret="c6f207f094d6ef692aa5a5f82b993ad3";
  $token_url = "https://api.weixin.qq.com/cgi-bin/token?grant_type=client_credential&appid=$appid&secret=$appsecret";
  $html = file_get_contents($token_url);
  $output = json_decode($html, true);
  $access_token = $output['access_token'];
  $order_url="https://api.weixin.qq.com/cgi-bin/message/subscribe/send?access_token=$access_token";
  // curlpost发送订阅消息
  $data  = json_encode($data);
  $headerArray =array("Content-type:application/json;charset='utf-8'","Accept:application/json");
  $curl = curl_init();
  curl_setopt($curl, CURLOPT_URL, $order_url);
  curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, FALSE);
  curl_setopt($curl, CURLOPT_SSL_VERIFYHOST,FALSE);
  curl_setopt($curl, CURLOPT_POST, 1);
  curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
  curl_setopt($curl,CURLOPT_HTTPHEADER,$headerArray);
  curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
  $output = curl_exec($curl);
  curl_close($curl);
  // return json_decode($output，true);
}
// 生成随机字符串
function create_code(){
  $code = "1230ABCDEFGHIJKLMNOPQRSTUVWXYZ";
  $arr=[];
  for($i=0;$i<6;$i++){
    $arr[$i]=$code[rand(0,29)];
  }
  $code=implode('',$arr);
  return $code;
}