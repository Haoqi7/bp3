<?php
    // 快速配置
    require_once("./functions.php");

    $config = $base;

    $param = $_GET['param'] ?? null; //取得的安装信息

    $identify = null; //身份信息
    
    // 补全callback.php信息
    $redirect = $dir_url.'/grant/callback.php';

    // 如果未安装，且接收到了参数，开始安装
    if(!$install && !empty($param)){
        
        $config['user']['name']='bp3';
        $config['user']['pwd']='bp3';

        $config['connect']['redirect_uri']=$redirect;

        $param = urldecode($param); // 这是一个转码后的字符串，先转码，再转数组
        $identify = json_decode($param,true);

        $identify['conn_time'] = $time; // 使用外部系统可能比较旧缺少此项，在这里重新添加一次

        $config['identify'] = $identify;

        // 先保存config
        save_config();
    
        // 获取basic
        $basic = api(array('method'=>'m_basic','module'=>'baidu','access_token'=>$identify['access_token']));
        
        $config['basic'] = $basic;
        
        // 保存config
        save_config();
        redirect($login_url,0);
    }else if(!empty($param)){
        // 请求初始化，但已经初始化了
        js_alert("你已经配置过了，如果需要重新配置，请把config.php文件删掉");
        js_location($login_url);
    }
    $bp3_tag->assign("enc_page_url",$enc_page_url);
    display();