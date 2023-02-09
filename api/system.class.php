<?php

/**
 * 系统基础api，无需登录
*/
class system extends api{
    
    
    /**
     * 登录接口
    */
    public static function login(){
        global $config;
        global $user;
        $name = isset($_GET['user'])?$_GET['user']:null;
        $pwd = isset($_GET['pwd'])?$_GET['pwd']:null;
        
        $chance = $config['user']['chance'];
        $lock = $config['user']['lock'];
        // 用户密码为空，不处理
        if(!$name && !$pwd){
            // 表示未输入
            return array("error_code"=>2,"info"=>"请输入账户密码");
        }
        else if($config['user']['name']==$name && $config['user']['pwd']==$pwd && $chance>0){
            // 登陆成功
            set_session($user,$name);
            set_cookie($user,$name,time()+86400*30);  //30天
            // 是否重置机会
            if($lock!=$chance){
                $config['user']['chance']=$lock;
                save_config();
            }
            return array("error_code"=>0,"info"=>"登录成功");
        }else{
            // 次数减少
            $chance--;
            $config['user']['chance'] = $chance;
            save_config();
            if($chance<=0){
                return array("error_code"=>1,"info"=>"账户已经锁定！有两个办法解决。<br><br>办法一：ftp编辑根目录下config.php文件chance为正数。<br><br>办法二：使用百度账户登录");
            }
            else{
                return array("error_code"=>3,"info"=>"用户名或密码错误！");
            }
        }
    }
    
}