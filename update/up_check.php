<?php
    // 检测更新
    require_once("../functions.php");
    
    $url = "https://gitee.com/api/v5/repos/zhufenghua1998/bp3/commits";

    if($update_type=="en"){
        $url = "https://api.github.com/repos/zhufenghua1998/bp3/commits";
    }
    
    $url = "https://api.github.com/repos/zhufenghua1998/bp3/commits";  //前面的规则忽略，优先使用github，因为其无频控，且测试国内不会被墙，速度极快。

    // 返回最后一个更新记录
    $res = easy_curl($url);
    $commits = json_decode($res,true);
    build_success($commits[0]);
