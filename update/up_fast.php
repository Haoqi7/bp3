<?php


    require_once("../functions.php");
    
    force_login();

    // 下载最新版代码
    $temp_uri = TEMP_DIR.DIRECTORY_SEPARATOR."bp3-main.zip";
    
    if($update_url!="https://github.com/zhufenghua1998/bp3/archive/refs/heads/main.zip" && $update_url!="https://gitee.com/zhufenghua1998/bp3/repository/archive/main.zip"){
        $update_url = "https://gitee.com/zhufenghua1998/bp3/repository/archive/main.zip";  //暂时强制指定为Gitee版本
    }
    
    easy_curl_down($update_url,array("Host: gitee.com","User-Agent: curl/7.79.1","Accept: */*"),$temp_uri);

    // 调用自动更新代码
    $type = os_type();
    require("./up_core_$type.php");
