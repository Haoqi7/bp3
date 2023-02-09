<?php

    require_once("../functions.php");
    
    force_login();
    
    $param = force_get_param("param"); 

    $identify = json_decode($param,true);
    
    $access_token = $identify['access_token'];

    $basic = api(array('method'=>'m_basic','module'=>'baidu','access_token'=>$access_token));
    
    $config['account'] = $basic;
    
    save_config();
    
    redirect($admin_url);
