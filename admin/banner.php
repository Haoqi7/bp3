<?php
    require_once("../functions.php");
    
    force_login();  // 强制登录
    
    //轮播图
    $barners = isset($config['barners']) && $config['barners'] ? $config['barners'] : array();
    if(empty($barners)){
        $barners = array(
            [
                'path' => '/static/img/carousel/1.png',
                'title' => "$title - $subtitle",
                'link' => ''
            ],
            [
                'path' => '/static/img/carousel/2.png',
                'title' => "$title - $subtitle",
                'link' => ''
            ],
            [
                'path' => '/static/img/carousel/3.png',
                'title' => "$title - $subtitle",
                'link' => ''
            ]
        );
    }
    
    $bp3_tag->assign("barners",json_encode($barners));
    
    display();