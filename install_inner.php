<?php
    // 快速配置
    require_once("./functions.php");

    $config = $base;
    $username = $_POST['username'] ?? null;

    // 请求配置，且还未配置
    if(!empty($username) && !$install){
        
        $config['user']['name']= $_POST['username'];
        $config['user']['pwd']= $_POST['password'];
        
        
        $config['identify']['grant_url'] = $grant2;
        
        save_config();
        js_alert('提交成功！正在前往登录页面...');
        js_location($login_url);
    }
    // 请求初始化，但已经初始化了
    elseif(!empty($username)){

        js_alert('你已经配置过了，如果需要重新配置，请把config.php文件删掉');
        js_location($login_url);
    }
    display();