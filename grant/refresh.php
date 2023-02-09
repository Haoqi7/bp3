<?php

/**
 * 使用方式：传递refresh_token参数即可返回刷新数据
 */
    require_once("../functions.php");

    // 1.获取参数
    $refresh_token = force_get_param('refresh_token');

    if(!$app_key){
        $msg = array(
            'errno' => -1,
            'errmsg' => "此授权系统还未初始化"
        );
        build_err($msg);
    }

    // 获取刷新后的信息
    $identify = api(array('method'=>'m_refresh','module'=>'baidu','refresh_token'=>$refresh_token,'app_key'=>$app_key,'secret'=>$secret_key,'grant_url'=>$grant,'refresh_url'=>$grant_refresh));

    // 输出
    echo $identify;


