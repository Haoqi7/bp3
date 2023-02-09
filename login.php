<?php 
    require_once("./functions.php");
    force_login(array("user"=>$user,"tryLogin"=>1));
    // 校验是否配置百度登录
    $bind_baidu = false;
    if(isset($config) && isset($config['identify']) && isset($config['account'])){
        $bind_baidu = true;
    }
    $login_baidu_url = null;
    if($bind_baidu){

        $login_controller = urlencode($baidu_login_url);

        $login_baidu_url = "$grant_url?display=$login_controller"; // 快速登录百度地址
    }


    $bp3_tag->assign("check_login",$check_login);
    $bp3_tag->assign("admin_url",$admin_url);
    

    $bp3_tag->assign("login_baidu_url",$login_baidu_url);

    display();
