<?php
/**
 *  百度登录
 */
    require_once("./functions.php");
    
    // 取得传递的授权身份信息
    $param = force_get_param("param"); 

    $identify = json_decode($param,true);

    $access_token = $identify['access_token'];

    // 取得该身份的basic信息

    $basic =api(array('method'=>"m_basic",'module'=>"baidu",'access_token'=>$access_token));

    // 再次校验是否已经配置百度登录
    
    if(empty($config) || empty($config['identify'] || empty($config['account']) || empty($config['account']['uk']))){

        build_err("管理员未配置百度登录，本次为非法请求");
    }
    
    // 比较用户id
    if($config['account']['uk'] == $basic['uk']){
        // 登录成功
        set_session($user,$config['user']['name']);
        set_cookie($user,$config['user']['name'],time()+86400*30);  //30天
        // 是否需要重置锁定次数
        $is_save = false;
        if($config['user']['chance'] != $config['user']['lock']){
            $config['user']['chance'] = $config['user']['lock'];
            $is_save = true;
        }
        // 如果绑定的用户，是当前用户，则存储最新的identify信息，相当于重新授权
        if($config['basic']['uk'] == $basic['uk']){
            $config['identify'] = $identify;
            $is_save = true;
        }
        // 是否需要更新文件
        if($is_save){
            save_config();
        }
        // 重定向
        redirect($login_url);
        
    }else{
        // 登录失败
        js_alert("非法用户，请使用账户：'$a_baidu_name'");

        js_location($login_url);
    }
