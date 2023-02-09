<?php

// 多账户的token刷新
    require("../functions.php");

    $disks = $config['disks'];
    if(is_array($disks)){

        // 批量刷新token
        foreach ($disks as $k=>$v){
            $refresh_url = $v['refresh_url'];
            $grant_url = $v['grant_url'];
            $refresh_token = $v['refresh_token'];
            // 免app授权系统
            if($refresh_url==$grant_refresh){
                $param = api(array("method"=>"m_refresh","module"=>"baidu",'refresh_token'=>$refresh_token,'app_key'=>$app_key,'secret'=>$secret_key,'grant_url'=>$grant_url,'refresh_url'=>$refresh_url));
            }
            // 内置app授权系统
            elseif($refresh_url==$grant2_refresh){
                $param = api(array("method"=>"m_refresh","module"=>"baidu",'refresh_token'=>$refresh_token,'app_key'=>$inner_app_key,'secret'=>$inner_secret_key,'grant_url'=>$grant_url,'refresh_url'=>$refresh_url));
            }
            // url授权
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
                $config['disks'][$k] = array_merge($v,$identify); // 更新身份信息
                save_config(null,$config);  // 保存
                echo "<h2>uk：$k 之token刷新成功，$time</h2>".PHP_EOL;
            }else{
                // 消息处理
                // 如果开启了邮件提醒
                if(is_array($config['mail']) && $config['mail']['refresh']=="1"){
                    $file_log = TEMP_DIR."/refresh.log";
                    $err_text = json_encode($identify);
                    if(date('Y-m-d',filemtime($file_log)) != date('Y-m-d')){  // 文件不是今日修改
                        // 今日首次发送
                        $base_url = BASE_URL;
                        $is_email = send_mail("token刷新失败通知","您的站点多账户：<b>{$config['site']['title']}</a>，多账户uk【{$k}】token自动刷新失效，请及时处理并排查原因，站点地址：<a href='$base_url'>$base_url</a>，如不需要此提醒，请在后台设置中关闭。<pre><code>$err_text</code></pre>");
                        if($is_email){
                            file_put_contents($file_log,"邮件发送成功：".date("Y-m-d h:i:s"));
                        }else{
                            file_put_contents($file_log,"邮件发送失败：".date("Y-m-d h:i:s").PHP_EOL.$err_text);
                        }
                    }
                }
                // 输出错误
                echo "<h4>uk：$k 之token刷新失败，$time</h4>".PHP_EOL;
            }
        }
    }