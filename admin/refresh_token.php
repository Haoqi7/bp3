<?php
    // 刷新token接口
    define("TOKEN_REFRESH","1");  // 定义常量，再引入functions，即可强制刷新
    ob_start();
    require_once("../functions.php");
    $msg = ob_get_contents();
    // 调用内置函数，强制刷新
    if(check_session()){  // 已登录
        if($msg){  // 错误，返回错误日志
            build_err(json_decode($msg));
        }else{  // 正确
            build_success($access_token);
        }
    }else{  // 未登录
        if($msg){  // 错误，返回错误日志
            build_err(json_decode($msg));
        }else{  // 正确
            build_success("刷新成功");
        }
    }


