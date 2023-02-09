<?php
    // 本页面仅用于展示获取到的信息，包含token，refresh_token等
    require_once("../functions.php");

    $result = get_session('grant_result');
    
    if(empty($result)){

        build_err("授权结果不存在");
    }
    $bp3_tag->assign("result",$result);
    display();