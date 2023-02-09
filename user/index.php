<?php

    // session域解析版
    require_once("../functions.php");

    // 未登录，重定向至登录页面
    if(!check_session("access_token")){
        redirect("./login.php");
    }
    // 正在注销
    if(isset($_GET['logout']) && $_GET['logout']){
        set_session('access_token',null);
        redirect("./login.php");
    }

    $bp3_tag->assign("baidu_name",get_session('baidu_name'));
    $bp3_tag->assign("netdisk_name",get_session('netdisk_name'));

    display();