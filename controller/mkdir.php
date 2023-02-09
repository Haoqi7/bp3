<?php

    // 创建文件夹
    require_once('../functions.php');
    
    force_login();
    // 多账户解析token
    $u = $_POST['u'] ?: 1;
    if($u!=1){
        $access_token = $config['disks'][$u]['access_token'];
    }

    // 获取name参数
    $name = force_post_param("name");
    
    // 获取path参数
    $path = force_post_param("path");

    $res =  api(array('method'=>'m_create','module'=>'baidu','isdir'=>1,'ser_dir'=>$path,'ser_name'=>$name,'access_token'=>$access_token));
    echo json_encode($res);
