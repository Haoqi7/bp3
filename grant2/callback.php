<?php
    require_once("../functions.php");
    
    
    // 1.获取state
    $state = force_get_param("state");
    $s_state = force_session_param("state");
    // 校验state，防止csrf攻击
    if($state!=$s_state){
        build_err("state无效");
    }
    // 2.获取基础连接信息
    
    $code = $_GET['code'];
    $app_id = $config['inner']['app_id'];
    $secrect_key = $config['inner']['secret_key'];
    $redirect_uri = $config['inner']['redirect_uri'];
    
    // 3.callback请求
    $identify = api(array('method'=>'m_callback','module'=>'baidu','code'=>$code,'appKey'=>$app_id,'secret'=>$secrect_key,'redirect'=>$redirect_uri,'state'=>$state,'grant_url'=>$grant2,'refresh_url'=>$grant2_refresh));

    // 4.结果处理，存入session并重定向
    set_session('grant2_result',$identify);

    if(get_session('display')=='display'){
        redirect("display.php");
    }else{
        $display = urldecode(get_session('display')); // 重定向地址
        $str_identify = json_encode($identify);
        $encode_result = urlencode($str_identify); // 字符串转码
        // 重定向并携带参数
        $is_param = strpos($display,"?");
        if($is_param){
            $redirect_with_param = $display.'&param='.$encode_result;
        }else{
            $redirect_with_param = $display.'?param='.$encode_result;
        }
        redirect($redirect_with_param);
    }
