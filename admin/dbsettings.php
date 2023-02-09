<?php

    require("../functions.php");
    
    force_login();

    //系统多账户列表
    $disks = $config['disks'] ?? array();

    //主账户
    $disks[$uk] = $config['basic'];

    //已经绑定的数据
    $bindDb = $config['bindDb'] ?? array();
    //找出绑定的db名称
    foreach ($disks as $key=>$val){
        $disks[$key] = $bindDb[$key] ?? "";
    }

    //所有账户
    $bp3_tag->assign("bindDb",json_encode($disks));

    //原始db
    $baseDb = $config['baseDb'] ?? array();
    $bp3_tag->assign("baseDb",json_encode($baseDb));
    
    //禁用db【账户】
    $unUseDb = $config['unUseDb'] ?? array();
    $bp3_tag->assign("unUseDb",json_encode($unUseDb));

    display();