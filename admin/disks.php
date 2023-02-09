<?php
    // 多账户管理
    require("../functions.php");

    force_login();

    $error = popError();

    $bp3_tag->assign("error",$error);

    $disks = $config['disks'] ?? array();

    $bp3_tag->assign("disks",$disks);

    $enc_multi_page_url = urlencode(BASE_URL."/controller/bind_multi.php");
    $grant_multi_url = $grant_url."?display=".$enc_multi_page_url;

    $bp3_tag->assign("grant_multi_url",$grant_multi_url);

    display();
