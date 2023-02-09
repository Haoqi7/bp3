<?php
    // 设置页面
    require_once("../functions.php");
    force_login();

    $bp3_tag->assign("name",$name);
    $bp3_tag->assign("pwd",$pwd);

    $bp3_tag->assign("lock",$lock);
    $bp3_tag->assign("app_key",$app_key);
    $bp3_tag->assign("secret_key",$secret_key);
    $bp3_tag->assign("redirect_uri",$redirect_uri);

    $bp3_tag->assign("pre_dir",$pre_dir);
    $bp3_tag->assign("baidu_account",$baidu_account);
    $bp3_tag->assign("baidu_pwd",$baidu_pwd);
    $bp3_tag->assign("close_dlink",$close_dlink);
    $bp3_tag->assign("close_dload",$close_dload);
    $bp3_tag->assign("open_grant",$open_grant);
    $bp3_tag->assign("open_grant2",$open_grant2);
    $bp3_tag->assign("open_session",$open_session);
    $bp3_tag->assign("inner_app_key",$inner_app_key);
    $bp3_tag->assign("inner_secret_key",$inner_secret_key);
    $bp3_tag->assign("theme",$theme);
    $bp3_tag->assign("themes",$themes);
    $bp3_tag->assign("grant",$grant);
    $bp3_tag->assign("grant2",$grant2);
    $bp3_tag->assign("noRefresh",$noRefresh);
    $bp3_tag->assign("dn_adv",$config['dn_adv'] ?? $base['dn_adv']);
    display();