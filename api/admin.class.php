<?php

/**
 * 管理员api模块，必须登录
*/
global $user;
force_login($user);

class admin extends api{
    
    /**
     * 保存轮播图
    */
    public static function saveBanner(){
        global $config;
        $config['barnerHeight'] = (int)$_POST['barnerHeight'];
        $config['MbarnerHeight'] = (int)$_POST['MbarnerHeight'];
        $config['barnerWidth'] = (int)$_POST['barnerWidth'];
        $config['MbarnerWidth'] = (int)$_POST['MbarnerWidth'];
        
        $config['barners'] = $_POST['barners'];
        
        save_config("main",$config);
        build_success("保存成功");
    }
    
    
    /**
     * 上传图标、图片
    */
    public static function uploadIcon(){
        
        $path = $_POST['fileRealPath'] ?? '';  //指定了路径
        if(empty($path)){
            //自动生成路径，默认在temp下
            $path = "/temp/".$_FILES['uploadFile']['name'];
        }
        move_uploaded_file($_FILES["uploadFile"]["tmp_name"],BASE_ROOT.$path);
        $url = BASE_URL.$path;
        
        build_success(array("info"=>"上传成功","path"=>$path,"url"=>$url));
    }
    
    /**
     * 忽略PHP错误
    */
    public static function ignorePHPError(){
        $confirm = $_POST['confirm'] ?? 0;
        if(!$confirm){
            build_err("confirmError");
        }
        //检测是否有普通错误
        $errorLog = BASE_ROOT."/log/php_errors.php";
        if(file_exists($errorLog)){
            $errorFile = require($errorLog);
            if(!empty($errorFile)){
                if($errorFile['ignore']==0){  //还没被忽略
                    $text = "<?php return ".var_export(array(),true).";";
                    file_put_contents($errorLog,$text);
                    build_success("错误忽略成功");
                }else{
                    build_err("错误已经被忽略");
                }
            }else{
                build_err("错误不存在");
            }
        }else{
            build_err("错误不存在");
        }
    }
    
    
    /**
     * 测试邮件是否正常
    */
    public static function testMail(){
        
        global $config;
        global $base_url;
        
        $res = send_mail("bp3系统测试邮件","这是一封测试邮件，来自您的站点：<a href='$base_url'>{$config['site']['title']}</a>");
        if ($res){
            build_success("邮件发送成功");
        }else{
            build_err("邮件发送失败");
        }
    }    
    
    
    /**
     * 检测fx索引是否存在
    */
    public static function checkMatch(){
        $struct = DB::sql("show create table bp3_cache_file")->selectArr();
        $text = $struct['Create Table'];
        if(strstr($text,"FULLTEXT KEY `server_filename` (`server_filename`)")){
            build_success();
        }else{
            build_err();
        }
    }
    
    
    /**
     * 创建ft索引
    */
    public static function createMatch(){
        $struct = DB::sql("show create table bp3_cache_file")->selectArr();
        $text = $struct['Create Table'];
        if(strstr($text,"FULLTEXT KEY `server_filename` (`server_filename`)")){
            build_err("全文检索索引已存在");
        }else{
            DB::sql("ALTER TABLE `bp3_cache_file` 
ADD FULLTEXT INDEX `server_filename`(`server_filename`)")->update();
            build_success("开始创建索引，请稍后查看成功状态");
        }
    }
    
    /**
     * 删除全文检索索引
    */
    public static function delMatch(){
        DB::sql("DROP INDEX server_filename ON bp3_cache_file")->update();
        build_success();
    }
    
    
    /**
     * 导入配置文件
    */
    public static function setconfig(){
        // 接收上传的config文件
        $temp_uri = "config_cache.php";
        move_uploaded_file($_FILES["file"]["tmp_name"],$temp_uri);
    
        // 获取该文件
        $config_cache = require($temp_uri);
    
        // 尝试合并
        $config = arr2_merge($config_cache,$base);
    
        // 尝试更新版本号（版本信息以base文件为准）
        $config['version'] = $base['version'];
    
        save_config();
    
        unlink($temp_uri);
    
        build_success();
    }
    
    
}