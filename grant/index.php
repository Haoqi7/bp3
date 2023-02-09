<?php

    require_once("../functions.php");

    if($open_grant==0){
        if($_GET['display']!=$baidu_login_url){
            force_login();
        }
    }
        
    // 允许携带重定向参数，参数为get，参数名display
    // 携带参数访问本页面，则在获取授权后携带结果重定向请求参数地址
    $display = $_GET['display'];
    if(isset($display)){
        // 存在redirect参数
        set_session('display',$display);
    }else{
        // 不存在重定向，则在默认页面显示
        set_session('display',"display");
    }
    
    // 2. 拼接授权信息
    $state = rand_pwd();
    set_session('state',$state);

    if(empty($app_key)){
        build_err("授权系统未初始化");
    }
    
    // 2.1自动检测链接
    $conn = "https://openapi.baidu.com/oauth/2.0/authorize?response_type=code&client_id=$app_key&redirect_uri=$redirect_uri&scope=basic,netdisk&display=popup&state=$state&confirm_login=1&login_type=sms";
    // 2.2强制登录链接
    $force_conn = $conn.'&force_login=1';
    // 3.3扫码登录链接
    $pr_conn = "https://openapi.baidu.com/oauth/2.0/authorize?response_type=code&client_id=$app_key&redirect_uri=$redirect_uri&scope=basic,netdisk&display=tv&qrcode=1&force_login=1&state=$state";
    $bp3_tag->assign("conn",$conn);
    $bp3_tag->assign("force_conn",$force_conn);
    $bp3_tag->assign("pr_conn",$pr_conn);
    $bp3_tag->assign("state",$state);
    $grant_result = get_session('grant_result');
    $bp3_tag->assign("grant_result",$grant_result);
    $bp3_tag->assign("display",$display);
    display();