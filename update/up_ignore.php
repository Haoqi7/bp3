<?php
    //忽略版本更新

    require_once("../functions.php");

    force_login();

    $config['update_time'] = $_POST['date'];
    $config['update_notice'] = 0;

    save_config();

    build_success("操作成功");