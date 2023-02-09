<?php
    require_once("../functions.php");
    
    force_login();

    // 统计授权信息时间
    $warning = false;
    $express_day = 0;
    $warningType = "";
    // 判断是否已获取授权
    if(empty($config['identify']) || empty($config['identify']['access_token'])){
        $warning = true;
        $warningType = "connect";
    }
    // 检测刷新是否异常
    else{
        $pass_time = time()-$config['identify']['conn_time'];
        $express_time = $config['identify']['expires_in']-$pass_time;
        if($express_time<1296000){ //有效期小于15天，给出警告
            $warning = true;
            $express_day = number_format($express_time/3600/24, 2);
            $warningType = "refresh";
        }
    }
    $bp3_tag->assign("conn_time",$conn_time);
    
    //检测更新
    $update_check_time = $config['update_check_time'] ?? 0;
    $update_notice = $config['update_notice'] ?? 0;
    if($update_check_time < time()-60*30 && $update_notice==0){  //半小时自动检测一次【已检测到新版时不会再次检测】
        $url = "https://api.github.com/repos/zhufenghua1998/bp3/commits";
        $res = easy_curl($url);
        $commits = json_decode($res,true);
        $commits = $commits[0];
        if($commits){
            $time = strtotime($commits['commit']['committer']['date']);
            $update_time = isset($config['update_time']) && $config['update_time'] ? strtotime($config['update_time']) : 0;
            if($time > $update_time){
                $config['update_notice'] = 1;  //要消除的办法，要么忽略，要么完成了更新
                $update_notice = 1;
            }
        }
        $config['update_check_time'] = time();
        save_config("main",$config);
    }
    $bp3_tag->assign("update_notice",$update_notice);
    
    //尝试自动解锁
    $login_fail_notice = 0;
    if($config['user']['chance']<=0){
        $login_fail_notice = $config['user']['lock']-$config['user']['chance'];
        $config['user']['chance'] = $config['user']['lock'];
        save_config("main",$config);
    }
    $bp3_tag->assign("login_fail_notice",$login_fail_notice);
    
    //检测是否有普通错误
    $errorLog = BASE_ROOT."/log/php_errors.php";
    if(file_exists($errorLog)){
        $errorLog = require($errorLog);
        if(!empty($errorLog)){
            if($errorLog['ignore']==0){  //还没被忽略
                $bp3_tag->assign("phpError",json_encode($errorLog));
            }else{
                $bp3_tag->assign("phpError",0);
            }
        }else{
            $bp3_tag->assign("phpError",0);
        }
    }else{
        $bp3_tag->assign("phpError",0);
    }

    $bp3_tag->assign("warning",$warning);
    $bp3_tag->assign("express_day",$express_day);
    $bp3_tag->assign("warningType",$warningType);

    $bp3_tag->assign("baidu_name",$baidu_name);
    $bp3_tag->assign("netdisk_name",$netdisk_name);
    $bp3_tag->assign("uk",$uk);
    $bp3_tag->assign("vip_type",$vip_type);

    $bp3_tag->assign("connect_grant_url",$connect_grant_url);
    $bp3_tag->assign("bind_account_grant_url",$bind_account_grant_url);


    $bp3_tag->assign("a_baidu_name",$a_baidu_name);
    $bp3_tag->assign("a_netdisk_name",$a_netdisk_name);
    $bp3_tag->assign("a_uk",$a_uk);
    $bp3_tag->assign("a_vip_type",$a_vip_type);

    display();
