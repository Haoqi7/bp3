<?php
/**
 * 后台基本帮助接口
 */
require_once("../functions.php");

force_login();

$method = force_get_param("method");

// 导出配置文件
if($method=="getconfig"){
    // 开始下载
    $filename = "../config.php";

    header("Cache-Control: public");
    header("Content-Description: File Transfer");
    header('Content-disposition: attachment; filename='.basename($filename)); //文件名
    header("Content-Type: application/zip"); //zip格式的
    header("Content-Transfer-Encoding: binary"); //告诉浏览器，这是二进制文件
    header('Content-Length: '. filesize($filename)); //告诉浏览器，文件大小
    @readfile($filename);  // 输出内容

}
// 导入配置文件
elseif($method=="setconfig"){
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
// 还原基础配置
elseif($method=="resetbasic"){
    $config = arr2_merge($base,$config);
    save_config();
    build_success();
}
// 重置系统（删除config）
elseif($method=="resetsys"){

    if (!unlink("../config.php")){
        build_err();
    }
    else{
        build_success();
    }
}
// 整站导出压缩包
elseif($method=="backup"){

    // 是否排除config
    $skip = $_GET['skip'];
    // 指定缓存文件名
    $filename = TEMP_DIR.DIRECTORY_SEPARATOR."bp3-main-back.zip";
    if($skip){
        $filename = TEMP_DIR.DIRECTORY_SEPARATOR."bp3-main.zip";  // 源码名称，用于简单区分是否包含config
    }
    $file_ctime = file_exists($filename)? filectime($filename) : 0;
    if((time() - $file_ctime)>3){  // 判断文件创建时间，在极短时间内不会重复创建
        // 整站备份，zip的子目录为bp3-main
        if(empty($skip) && !file_exists($filename)){
            ExtendedZip::zipTree(get_base_root(), $filename, ZipArchive::CREATE,"bp3-main");
        }
        elseif(!file_exists($filename)){
            ExtendedZip::zipTree(get_base_root(), $filename, ZipArchive::CREATE,"bp3-main",["config.php"]);
        }
        // 文件已存在，则覆盖该文件
        elseif(empty($skip)){
            ExtendedZip::zipTree(get_base_root(), $filename, ZipArchive::OVERWRITE,"bp3-main");
        }else{
            ExtendedZip::zipTree(get_base_root(), $filename, ZipArchive::OVERWRITE,"bp3-main",["config.php"]);
        }
    }
    easy_read_file($filename,true);
    unlink($filename);

}
// 保存配置文件
elseif($method=="savesettings"){
    $check = true;

    $config['site']['title'] = $_POST['s1'];
    $config['site']['subtitle'] = $_POST['s2'];
    $config['user']['name'] = $_POST['s3'];
    $config['user']['pwd'] = $_POST['s4'];
    $config['user']['lock'] = $_POST['s5'];
    $config['connect']['app_id'] = $_POST['s6'];
    $config['connect']['secret_key'] = $_POST['s7'];
    $config['connect']['redirect_uri'] = $_POST['s8'];
    $config['control']['pre_dir'] = str_split_format($_POST['s9'],"||"); // 前置目录
    $config['control']['pre_dir_show'] = str_split_format($_POST['s9s'],"||"); // 显示名称
    $config['control']['pre_dir_icon'] = str_split_format($_POST['s9i'],"||"); // 显示名称
    $config['site']['blog'] = $_POST['s10'];
    $config['site']['github'] = $_POST['s11'];
    $config['baidu']['baidu_account'] = $_POST['s12'];
    $config['baidu']['baidu_pwd'] = $_POST['s13'];
    $config['control']['close_dlink'] = isset($_POST['s14']) ? ($_POST['s14']=="on" ? 0 : 1) : 1;
    $config['control']['close_dload'] = isset($_POST['s15']) ? ($_POST['s15']=="on" ? 0 : 1) : 1;
    $config['control']['open_grant'] = isset($_POST['s16']) ? ($_POST['s16']=="on" ? 1 : 0) : 0;
    $config['identify']['grant_url'] = $_POST['s17'];
    $config['control']['grant_type'] = $_POST['s17s'];
    $config['control']['open_grant2'] = isset($_POST['s18']) ? ($_POST['s18']=="on" ? 1 : 0) : 0;
    $config['control']['open_session'] = isset($_POST['s19']) ? ($_POST['s19']=="on" ? 1 : 0) : 0;
    $config['site']['description'] = $_POST['s20'];
    $config['site']['keywords'] = $_POST['s21'];
    $config['inner']['app_id'] = $_POST['s22'];
    $config['inner']['secret_key'] = $_POST['s23'];
    $config['control']['update_type'] = $_POST['s24'];
    $config['control']['update_url'] = $_POST['s24u'];
//    $config['control']['dn_limit'] = $_POST['s25'];
//    $config['control']['dn_speed'] = $_POST['s26'];
//    if(!is_numeric($_POST['s26'])){
//        $check = false;
//    }
    $config['control']['theme'] = $_POST['s27'];
    $config['mail']['user'] = $_POST['s28'];  // 发送用户
    $config['mail']['pass'] = $_POST['s29'];  // 应用密钥
    $config['mail']['server'] = $_POST['s30'];  // 邮件服务器
    $config['mail']['port'] = $_POST['s31'];  // 邮件端口
    $config['mail']['receiver'] = $_POST['s32'];  // 收件人
    $config['mail']['refresh'] = isset($_POST['s33']) ? ($_POST['s33']=="on" ? 1 : 0) : 0;  // 刷新失败提示
    // 隐藏文件列表
    $config['control']['hideFile'] = str_split_format($_POST['s34']);
    // 隐藏文件夹列表
    $config['control']['hideDir'] = str_split_format($_POST['s35'],"||");
    //百度网页版前缀
    $config['baidu']['listPre'] = $_POST['s36'];
    $config['baidu']['searchPre'] = $_POST['s37'];
    //debug模式
    $config['debug'] = isset($_POST['s38']) ? ($_POST['s38']=="on" ? 1 : 0) : 0;
    //静态文件地址
    $config['static_url'] = $_POST['s39'];
    $config['useDb'] = isset($_POST['s40']) ? ($_POST['s40']=="on" ? 1 : 0) : 0;
    $config['useDbPre'] = isset($_POST['s41']) ? ($_POST['s41']=="on" ? 1 : 0) : 0;
    $config['noRefresh'] = isset($_POST['s42']) ? ($_POST['s41']=="on" ? 1 : 0) : 0;
    $config['dn_adv'] = $_POST['s44'];
    $config['dbMatch'] = isset($_POST['s45']) ? ($_POST['s45']=="on" ? 1 : 0) : 0;
    $config['allSearch'] = isset($_POST['s46']) ? ($_POST['s46']=="on" ? 1 : 0) : 0;
    $config['loadThumb'] = isset($_POST['s48']) ? ($_POST['s48']=="on" ? 1 : 0) : 0;
    $config['siteNotice'] = $_POST['s49'] ?? '';
    $config['siteNoticeLink'] = $_POST['s50'] ?? '';
    $config['hideIndexNavbar'] = (int)($_POST['s51'] ?? 0);
    // 数据校验
    if($check){
        save_config();
        build_success();
    }else{
        build_err("请填写正确的数据格式！");
    }

}
//数据库设置
elseif($method=="dbSettings"){
    //接收数据
    $config['dbConfig']['db'] = 'mysql';
    $config['dbConfig']['host'] = $_POST['host'];
    $config['dbConfig']['port'] = (int)$_POST['port'];
    $config['dbConfig']['dbname'] = $_POST['dbname'];
    $config['dbConfig']['user'] = $_POST['user'];
    $config['dbConfig']['pwd'] = $_POST['pwd'];
    $config['dbConfig']['charset'] = 'utf8';
    $config['bindDb'] = $_POST['bindDb'] ?? array();
    $config['baseDb'] = $_POST['baseDb'] ?? array();
    $config['unUseDb'] = $_POST['unUseDb'] ?? array();
    //保存到配置文件
    save_config();
    build_success("保存成功");
}
//测试数据库连接
elseif($method=="testDb"){
    $bp3_dbConfig['db'] = 'mysql';
    $bp3_dbConfig['host'] = $_POST['host'];
    $bp3_dbConfig['port'] = (int)$_POST['port'];
    $bp3_dbConfig['dbname'] = $_POST['dbname'];
    $bp3_dbConfig['user'] = $_POST['user'];
    $bp3_dbConfig['pwd'] = $_POST['pwd'];
    $bp3_dbConfig['charset'] = 'utf8';
    $res = DB::db()->dbVersion();
    build_success("连接成功，数据库版本：".$res);
}
//检测sqlite版本
elseif($method=="testSqlite"){
    $ver = SQLite3::version();
    $min_sqlite3_v = 3028000;
    if($ver['versionNumber']<$min_sqlite3_v){
        build_err(array("errno"=>1,"state"=>"500","errmsg"=>"服务器sqlite3版本太低【当前版本{$ver['versionNumber']}，小于最低要求{$min_sqlite3_v}】，参考：<a target='_blank' href='https://www.5252.online/archives/925.htm'>https://www.5252.online/archives/925.htm</a>"));
    }else{
        build_success("当前版本符合，版本号：".$ver['versionNumber']."，最低要求：".$min_sqlite3_v);
    }
}
//初始化数据表
elseif($method=="createTable"){
    
    //set global innodb_file_format = BARRACUDA;
    $res = DB::sql("show variables like '%innodb_file_format';")->selectArr();
    if($res['Value']!="Barracuda"){
        build_err("请添加设置： innodb_file_format = BARRACUDA ，或使用root账户");
    }
    //set global innodb_large_prefix = ON;
    $res = DB::sql("show variables like 'innodb_large_prefix';")->selectArr();
    if($res['Value']!="ON"){
        build_err("请添加设置： innodb_large_prefix = ON ，或使用root账户");
    }
    //如果表存在，先 drop
    DB::table("bp3_cache_file")->drop();
    //创建表
    DB::getInstance()->setSql("CREATE TABLE `bp3_cache_file`  (
  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文件ID',
  `category` smallint(2) NOT NULL COMMENT '文件类型',
  `isdir` smallint(1) NOT NULL COMMENT '是否文件夹',
  `fs_id` bigint(20) NOT NULL COMMENT '百度fsid',
  `local_ctime` bigint(20) NOT NULL COMMENT '客户端创建时间',
  `local_mtime` bigint(20) NOT NULL COMMENT '客户端修改时间',
  `md5` varchar(36) NOT NULL DEFAULT '' COMMENT '文件md5',
  `server_ctime` bigint(20) NOT NULL COMMENT '服务器创建时间',
  `server_mtime` bigint(20) NOT NULL COMMENT '服务器修改时间',
  `server_filename` varchar(340) NOT NULL COMMENT '文件名',
  `size` bigint(20) NOT NULL COMMENT '文件大小',
  `uk` bigint(10) NOT NULL COMMENT '用户百度uk',
  `parent_path` varchar(1024) NOT NULL COMMENT '文件父目录',
  PRIMARY KEY (`id`),
  INDEX `uk`(`uk`),
  INDEX `isdir`(`isdir`),
  INDEX `server_mtime`(`server_mtime`),
  INDEX `parent_path`(`parent_path`),
  UNIQUE uf(`uk`,`fs_id`),
  FULLTEXT `server_filename`(`server_filename`)
) ENGINE = InnoDB CHARACTER SET = utf8 COLLATE = utf8_general_ci row_format=dynamic COMMENT = '文件数据表';")->create();
    $res = DB::table("bp3_cache_file")->exist();
    if($res){
        build_success("表创建成功");
    }else{
        build_err("表创建失败，请检测MySQL版本");
    }
}
//检测表是否存在
elseif($method=="checkTableExist"){
    $res = DB::table("bp3_cache_file")->exist();
    if($res){
        build_success("表已经存在");
    }
    else{
        build_err("表bp3_cache_file不存在，请创建");
    }
}
//同步数据
elseif($method=="async"){
    //同步的uk
    $uk = force_post_param('uk');
    //同步的数据库名
    $db = force_post_param("db");

    //页数
    $page = $_POST['page'] ?: 1;
    $pageSize = $_POST['pageSize'] ?: 50;

    //调用api
    $res = api(array('method'=>'async','module'=>'baidu','uk'=>$uk,'db'=>$db,'page'=>$page,'pageSize'=>$pageSize));

    //返回数据
    build_success($res);die;
}
else{
    build_err("无效method");
}