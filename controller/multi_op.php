<?php

require("../functions.php");

force_login();

$method = force_get_param("method");

$uk = $_POST['uk'] ?: $_GET['uk'];

// 更新绑定目录
if($method=="save"){

    if($uk==1){
        $config['control']['show_name'] = $_POST['show_name'];  //主账户显示名称
    }
    else{
        $config['disks'][$uk]['show_name'] = $_POST['show_name'];
        $config['disks'][$uk]['pre_dir'] = str_split_format($_POST['pre_dir'],"||");
        $config['disks'][$uk]['pre_dir_show'] = str_split_format($_POST['pre_dir_show'],"||");
        $config['disks'][$uk]['pre_dir_icon'] = str_split_format($_POST['pre_dir_icon'],"||");
        $config['disks'][$uk]['hideDir'] = str_split_format($_POST['hideDir'],",");
        $config['disks'][$uk]['hideFile'] = str_split_format($_POST['hideFile'],",");
    }
    save_config();
    build_success("更新成功");
}
// 删除一个绑定账户
elseif($method=="del"){
    unset($config['disks'][$uk]);
    save_config();
    build_success("删除成功");
}
//刷新token
elseif($method=="refresh"){

    $refresh_url = $config['disks'][$uk]['refresh_url'];  // 刷新地址
    $grant_url = $config['disks'][$uk]['grant_url'];  // 授权地址
    $refresh_token = $config['disks'][$uk]['refresh_token'];  // 刷新token
    // 免授权系统
    if($refresh_url==$grant_refresh){
        $param = api(array("method"=>"m_refresh","module"=>"baidu",'refresh_token'=>$refresh_token,'app_key'=>$app_key,'secret'=>$secret_key,'grant_url'=>$grant_url,'refresh_url'=>$refresh_url));
    }
    elseif($refresh_url==$grant2_refresh){
        $param = api(array("method"=>"m_refresh","module"=>"baidu",'refresh_token'=>$refresh_token,'app_key'=>$inner_app_key,'secret'=>$inner_secret_key,'grant_url'=>$grant_url,'refresh_url'=>$refresh_url));
    }
    else{
        $urlKey = "?";
        if(strpos($refresh_url,$urlKey)){
            $urlKey = "&";
        }
        $url = $refresh_url. $urlKey. "refresh_token=$refresh_token";
        $param = easy_curl($url);
    }
    $identify = json_decode($param,true);
    if($identify['access_token']){  // 刷新token时，正常应该会得到access_token，得不到则不正常
        $config['disks'][$uk] = array_merge($config['disks'][$uk],$identify); // 更新身份信息
        save_config(null,$config);  // 保存
        build_success($identify['access_token']);  //返回新的 token
    }else{
        // 消息处理
        // 如果开启了邮件提醒
        if(is_array($config['mail']) && $config['mail']['refresh']=="1"){
            $file_log = TEMP_DIR."/refresh.log";
            $err_text = json_encode($identify);
            if(date('Y-m-d',filemtime($file_log)) != date('Y-m-d')){  // 文件不是今日修改
                // 今日首次发送
                $base_url = BASE_URL;
                $is_email = send_mail("token刷新失败通知","您的站点多账户：<b>{$config['site']['title']}</a>，多账户uk【{$uk}】token自动刷新失效，请及时处理并排查原因，站点地址：<a href='$base_url'>$base_url</a>，如不需要此提醒，请在后台设置中关闭。<pre><code>$err_text</code></pre>");
                if($is_email){
                    file_put_contents($file_log,"邮件发送成功：".date("Y-m-d h:i:s"));
                }else{
                    file_put_contents($file_log,"邮件发送失败：".date("Y-m-d h:i:s").PHP_EOL.$err_text);
                }
            }
        }
        // 返回错误
        build_err($identify);  // 输出错误
    }
}
// 不合法
else{
    build_err("参数错误");
}
