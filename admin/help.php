<?php
/**
 * 普通用户帮助文档
 */
    require_once("../functions.php");
    force_login();


    $bp3_tag->assign("grant",$grant);
    $bp3_tag->assign("grant2",$grant2);
    $bp3_tag->assign("open_url",$open_url);
    
    $config['update_time'] = $config['update_time'] ?? '';
    $bp3_tag->assign("update_notice",$config['update_notice'] ?? 0);
    
    $bp3_tag->assign("update_type_zh",$update_type=="cn" ? "Gitee" : ($update_type=="en" ? "Github" : "自定义"));
    
    display();