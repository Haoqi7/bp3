<?php
// 文件管理
    require('../functions.php');
    force_login();  // 强制登录

    //数据库处理
    $config['useDb'] = $config['useDb'] ?? 0;
    $db = (int)$config['useDb'];
    $db = $_GET['db'] ?? $db;
    $bp3_tag->assign("db",$db);

    // ---多账户处理
    $u = isset($_GET['u']) ? (int)$_GET['u'] : 1;
    if($u<0){ $u= 1; }  // 如果为负数，也为1
    $disks = $config['disks'] ?? '';
    //存在多账户
    if(is_array($disks)){
        $disks_count = count($disks);
        // 尝试获取该账户
        $disks_u = $disks[$u] ?? null;
        if(is_array($disks_u)){
            // 取得多账户前置目录、及其token
            $pre_dir = $disks_u['pre_dir'] ?: "";
            $pre_dir_show = $disks_u['pre_dir_show'] ?: "";
            $access_token = $disks_u['access_token'];
            $uk = $disks_u['uk'];
        }
        // 多账户解析失败，默认u=1
        else{
            $u = 1;
        }
        $us_count = $disks_count+1;  // 多账户+主账户
        $us = array();
        
        $us[1] = isset($config['control']['show_name']) && !empty($config['control']['show_name']) ? $config['control']['show_name'] : "主账户";
        
        foreach ($disks as $kk => $disk){
            $us[$kk] = isset($disk['show_name']) && !empty($disk['show_name']) ? $disk['show_name'] : "账户".$kk;
        }
    }
    //不存在多账户
    else{
        $us_count = 1;
        $us = array(1=>"主账户");
    }
    $bp3_tag->assign("u",$u);
    $bp3_tag->assign("us",$us);
    $bp3_tag->assign("us_count",$us_count);

    // 多目录处理
    $tag = isset($_GET['tag']) ? (int)$_GET['tag'] : 1;
    if($tag<0){ $tag= 1; }  // 如果为负数，也为1
    $tags = explode("||",$pre_dir);
    $tags_count = count($tags);
    if($tag>$tags_count){ $tag=1; }  // 如果指定的目录不存在，也为1
    $dir = $tags[$tag-1];  // 取得对应的前置目录
    // 截取显示文件夹名称（如果自定义名称，则最终显示自定义名称）
    $dir_shows = $config['control']['pre_dir_show'] ?? '';
    if($u!=1){
        $dir_shows = $pre_dir_show ?: "";
    }
    $dir_shows = explode("||",$dir_shows);
    foreach ($tags as $k=>$v){
        $tags[$k] = substr($v,strrpos($v,"/")+1,strlen($v));
        $cus_dir_show = $dir_shows[$k] ?? null;
        if($cus_dir_show){
            $tags[$k] =$cus_dir_show;
        }
    }
    $bp3_tag->assign("tags_count",$tags_count);
    $bp3_tag->assign("tags",$tags);
    $bp3_tag->assign("tag",$tag);

    // --- end
    // 捕获dir查询参数
    if(isset($_GET['dir'])){
        $dir = $_GET['dir'];  // 如果存在dir，则覆盖tag的默认目录
    }
    $dir = $dir ?: "";
    $real_dir = "";  // 真实路径
    $pre_dir = "";   // 无论是否有前缀，指定了空，管理员页面能看到所有文件
    // 捕获查询参数
    $key = isset($_GET['s'])?$_GET['s']:null;
    // data数据，优先查询，然后是dir
    $data = array();
    $recursion = $_GET['recursion'] ?? 2;  //2、递归根目录，1.递归当前文件夹，0.当前文件夹不递归【这里的根目录是前台的根目录】
    $bp3_tag->assign("recursion",$recursion);
    $fileType = $_GET['fileType'] ?? 1;  //1.全部，2.文件夹，3.文件
    $bp3_tag->assign("fileType",$fileType);
    $orderType = $_GET['orderType'] ?? 1;  //1.默认，2.文件名升序，3.更新时间倒序
    $bp3_tag->assign("orderType",$orderType);
    //是否使用sqlite
    $baseDb = $config['baseDb'] ?? array();
    $baseDb = in_array($uk,$baseDb);
    if($baseDb){
        $baseDb = $config['bindDb'][$uk];
    }
    //是否禁用db【该账户】
    $unUseDb = $config['unUseDb'] ?? array();
    $unUseDb = in_array($uk,$unUseDb) ? 1 : 0;
    //分页
    $page = $_GET['page'] ?? 1;
    $pageSize = $_GET['pageSize'] ?? 20;

    if(isset($key)){
        if($recursion==2){
            $recursion = 1;
            $real_dir = "/";
            $dir = "/";
        }
        else{
            if(!$dir){ // 访问网页首页
                if($pre_dir==""){
                    $dir = "/";
                    $real_dir="/";
                }else{
                    $real_dir = $pre_dir;
                }
            }else{
                $real_dir = $pre_dir.$dir;
            }
        }
        $data = api(array('method'=>'m_file_search','module'=>'baidu','pre_dir'=>$real_dir,'page'=>$page,'pageSize'=>$pageSize,'key'=>$key,'access_token'=>$access_token,'uk'=>$uk,'db'=>$db,'baseDb'=>$baseDb,"recursion"=>$recursion,'fileType'=>$fileType,'orderType'=>$orderType));
    }else{
        //处理前台路径
        if(!$dir){ // 访问网页首页
            if($pre_dir==""){
                $dir = "/";
                $real_dir="/";
            }else{
                $real_dir = $pre_dir;
            }
        }else{
            $real_dir = $pre_dir.$dir;
        }
        $data = api(array('method'=>'m_file_list','module'=>'baidu','dir'=>$real_dir,'page'=>$page,'pageSize'=>$pageSize,'access_token'=>$access_token,'uk'=>$uk,'db'=>$db,'baseDb'=>$baseDb));
    }
    // 是否还有下一页(仅搜索接口）
    $has_more = isset($data['has_more'])?$data['has_more']:null;
    $pageInfo = $data['pageInfo'] ?? array();
    $bp3_tag->assign("pageInfo",json_encode($pageInfo));

    // 注册nav
    $nav = array();
    if($dir!=""){ // 非根目录，一个或多个
        $dirs = explode('/',$dir); // 取得路径
        $dir_path = '';  // 新的访问路径
        $dir_paths = [null,]; // 存储新路径组
        $dirs_count = count($dirs);
        if($dirs_count>1){
            for($i=1;$i<$dirs_count;$i++){
                if($dirs[$i]==""){
                    continue;
                }
                $dir_path.='/';
                $dir_path.=$dirs[$i];
                $dir_paths[$i] = $dir_path;
                $dir_link = urlencode($dir_path);
                $item = ["enc"=>$dir_link,"name"=>$dirs[$i]];
                array_push($nav,$item);
            }
        }
    }
    $bp3_tag->assign("nav",$nav);
    $bp3_tag->assign("key",$key);
    $bp3_tag->assign("dir",$dir);
    $bp3_tag->assign("enc_dir",urlencode($dir));

    // 处理data
//easy_dump($data);
    foreach ($data['list'] as & $row){
        if($row['isdir']==1){
            // 去掉前缀
            $path = substr($row['path'],strlen($pre_dir));
            // 编码后的地址
            $encode_path = urlencode($path);
            // 存储变更
            $row['path'] =  $path;
            $row['encode_path'] =  $encode_path;
        }else{
            // 显示大小
            $row['show_size'] = height_show_size($row['size']);
            // 去掉前缀的title
            $row['title'] = substr($row['path'],strlen($pre_dir));
            // 编码后的title
            $row['enc_title'] = urlencode($row['title']);
        }
    }
    $bp3_tag->assign("data",$data);

    display();
