<?php
/**
 * api 包含了所有的 ajax ，如果直接访问本页面，api()函数会输出结果。
 */
    session_start([
        'cookie_lifetime' => 86400,
        'read_and_close'  => true,
    ]);
    require("../inc/fun_core.php");
    require("../inc/fun_http.php");
    // 请求参数处理
    $param = array_merge($_POST,$_GET); // 如果参数同名，以后者为准，即GET

    if(empty($param['method']) || empty($param['module'])){
        build_err("参数错误");
    }

    api($param);
