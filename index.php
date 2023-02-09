<?php
    require('./functions.php');
    
    force_login(array("user"=>$user,"tryLogin"=>1));
    
    $tagType = $_GET['tagType'] ?? 'dir';
    
    // 多账户处理
    $u = isset($_GET['u']) ? (int)$_GET['u'] : $uk; //【用uk而不要用index，因为index可能切换顺序而导致数据不正确，而uk则可以始终不变】
    if($u<0){ $u= $uk; }  // 如果为负数，也为uk
    $disks = $config['disks'] ?? '';
    $hideFile = $config['control']['hideFile'] ?? "";
    $hideDir = $config['control']['hideDir'] ?? "";
    $hideDirs[$uk] = $hideDir;
    $hideFiles[$uk] = $hideFile;
    $pre_dirs[$uk] = $pre_dir;
    $pre_dir_shows[$uk] = $config['control']['pre_dir_show'] && $config['control']['pre_dir_show'] ? $config['control']['pre_dir_show'] :  ( isset($config['control']['show_name']) && $config['control']['show_name'] ? $config['control']['show_name'] : "主账户");
    $pre_dir_icon[$uk] = $config['control']['pre_dir_icon'] && $config['control']['pre_dir_icon'] ? $config['control']['pre_dir_icon'] : "";
    //存在多账户
    if(is_array($disks)){
        $disks_count = count($disks);
        // 尝试获取该账户
        $disks_u = $disks[$u] ?? null;
        if(is_array($disks_u)){
            // 取得多账户前置目录、及其token
            $pre_dir = $disks_u['pre_dir'] ?? "";
            $pre_dir_show = $disks_u['pre_dir_show'] ?? "";
            $access_token = $disks_u['access_token'];
            $hideFile = $disks_u['hideFile'] ?? '';
            $hideDir = $disks_u['hideDir'] ?? '';
            $uk = $disks_u['uk'];
        }
        // 多账户解析失败，默认u=uk
        else{
            $u = $uk;
        }
        $us_count = $disks_count+1;  // 多账户+主账户
        $us = array();
        
        $us[$uk] = isset($config['control']['show_name']) && !empty($config['control']['show_name']) ? $config['control']['show_name'] : "主账户";
        
        foreach ($disks as $kk => $disk){
            $us[$kk] = isset($disk['show_name']) && !empty($disk['show_name']) ? $disk['show_name'] : "账户".$kk;
            
            //获取所有的隐藏目录和文件列表
            $hideDirs[$kk] = $disk['hideDir'] ?? '';
            $hideFiles[$kk] = $disk['hideFile'] ?? '';
            $pre_dirs[$kk] = $disk['pre_dir'] ?? "";
            $pre_dir_shows[$kk] = $disk['pre_dir_show'] ?? "";
            $pre_dir_icon[$kk] = $disk['pre_dir_icon'] ?? "";
        }
    }
    //不存在多账户
    else{
        $us_count = $uk;
        $us = array($uk=>"主账户");
    }
    $bp3_tag->assign("u",$u);
    $bp3_tag->assign("us",$us);
    $bp3_tag->assign("us_count",$us_count);

    // 多目录处理
    $tag = isset($_GET['tag']) ? (int)$_GET['tag'] : 1;
    if($tag<0){ $tag= 1; }  // 如果为负数，也为1
    //取出所有账户的所有目录
    $allPredirs = array();  //格式：array ( u=> array('$name1','$name2') )
    $allDirs_count = 0; //计算所有的目录
    foreach ($pre_dirs as $pre_dirs_k => $pre_dirs_v){
        
        //非根目录，可能有多个
        if(!empty($pre_dirs_v)){
            
            $pre_dirs_v_arr = explode("||",$pre_dirs_v); //所有的前置目录
            $pre_dir_shows_v_arr = explode("||",$pre_dir_shows[$pre_dirs_k]); //前置目录对应的自定义名称
            $pre_dir_icon_v_arr = explode("||",$pre_dir_icon[$pre_dirs_k]); //前置目录对应的自定义名称
            
            //遍历判断名称是否存在，如果不存在则截取目录名
            $newNames = array();
            foreach ($pre_dirs_v_arr as $pre_dirs_v_arr_k => $pre_dirs_v_arr_v){
                $allDirs_count++;
                //该项有名称
                if(isset($pre_dir_shows_v_arr[$pre_dirs_v_arr_k]) && $pre_dir_shows_v_arr[$pre_dirs_v_arr_k]){
                    $newNames[$pre_dirs_v_arr_k] = array(
                        'name'=>$pre_dir_shows_v_arr[$pre_dirs_v_arr_k],
                        'icon'=> BASE_URL . ( isset($pre_dir_icon_v_arr[$pre_dirs_v_arr_k]) && $pre_dir_icon_v_arr[$pre_dirs_v_arr_k] ? $pre_dir_icon_v_arr[$pre_dirs_v_arr_k] : "/static/img/icon/folder.png" )
                    );
                }
                //截取目录名称作为该项名称
                else{
                    $newNames[$pre_dirs_v_arr_k] = array(
                        'name'=>substr($pre_dirs_v_arr_v,strrpos($pre_dirs_v_arr_v,"/")+1,strlen($pre_dirs_v_arr_v)),
                        'icon'=>BASE_URL. ( isset($pre_dir_icon_v_arr[$pre_dirs_v_arr_k]) && $pre_dir_icon_v_arr[$pre_dirs_v_arr_k] ? $pre_dir_icon_v_arr[$pre_dirs_v_arr_k] : "/static/img/icon/folder.png" )
                    );
                }
            }
            
            $allPredirs[$pre_dirs_k] = $newNames;
        }
        //仅根目录
        else{ 
            $showname = isset($pre_dir_shows[$pre_dirs_k]) && $pre_dir_shows[$pre_dirs_k] ? $pre_dir_shows[$pre_dirs_k] : $us[$pre_dirs_k];
            $allPredirs[$pre_dirs_k] = array( array("name"=>$showname, "icon"=>BASE_URL. ( isset($pre_dir_icon[$pre_dirs_k]) && $pre_dir_icon[$pre_dirs_k] ? $pre_dir_icon[$pre_dirs_k] : "/static/img/icon/folder.png") ) );
            $allDirs_count ++;
        }
        
    }
    $bp3_tag->assign("allPredirs",$allPredirs);
    $bp3_tag->assign("allDirs_count",$allDirs_count);
    
    
    $tags = explode("||",$pre_dir);
    $tags_count = count($tags);
    if($tag>$tags_count){ $tag=1; }  // 如果指定的目录不存在，也为1
    $pre_dir = $tags[$tag-1];  // 取得对应的前置目录
    // 截取显示文件夹名称（如果自定义名称，则最终显示自定义名称）
    $config['control']['pre_dir_show'] = $config['control']['pre_dir_show'] ?? "";
    $dir_shows = $config['control']['pre_dir_show'];
    if($u!=1){
        $dir_shows = $pre_dir_show ?? "";
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
    //是否使用数据库
    $config['useDb'] = $config['useDb'] ?? 0;
    if($config['useDb'] && $config['useDbPre']){
        $db = 1;
    }else{
        $db = 0;
    }
    // 捕获dir查询参数
    $dir = isset($_GET['dir']) ? $_GET['dir'] : ''; // 少了前缀
    // 捕获查询参数
    $key = $_GET['s'] ?? null;
    // 捕获分页参数
    $page = $_GET['page'] ?? 1;
    $pageSize = $_GET['pageSize'] ?? 20;
    //是否使用sqlite
    $baseDb = $config['baseDb'] ?? array();
    $baseDb = in_array($uk,$baseDb);
    if($baseDb){
        $baseDb = $config['bindDb'][$uk];
    }
    //是否禁用db【该账户】
    $unUseDb = $config['unUseDb'] ?? array();
    $unUseDb = in_array($uk,$unUseDb) ? 1 : 0;
    $recursion = $_GET['recursion'] ?? 2;  //2、递归根目录，1.递归当前文件夹，0.当前文件夹不递归【这里的根目录是前台的根目录】
    $bp3_tag->assign("recursion",$recursion);
    $fileType = $_GET['fileType'] ?? 1;  //1.全部，2.文件夹，3.文件
    $bp3_tag->assign("fileType",$fileType);
    $orderType = $_GET['orderType'] ?? 1;  //1.默认，2.文件名升序，3.更新时间倒序
    $bp3_tag->assign("orderType",$orderType);
    if($db && !$unUseDb){
        $bp3_tag->assign("db",1);
    }else{
        $bp3_tag->assign("db",0);
    }
    $allSearch = $config['allSearch'] ?? 0;  //混合搜索
    if($db && !$unUseDb && !$baseDb && $allSearch){ //allSearch时，前置目录无效
        $pre_dir = "";
        $real_dir = "/";
    }
    // data数据，优先查询，然后是dir
    if($key){
        //根目录全部，直接变成pre_dir
        if($recursion==2){ 
            if($pre_dir==""){
                $dir = "/";
                $real_dir="/";
            }else{
                $real_dir = $pre_dir;
            }
            $recursion = 1;
        }
        //当前目录全部、或当前目录？
        else{
            $recursion = $recursion ? 1 : 0;
            if(!$dir){
                // 前台目录为空
                if($pre_dir==""){
                    $dir = "/";
                    $real_dir="/";
                }
                // 取得前台目录
                else{
                    $real_dir = $pre_dir;
                }
            }
            // 传递了dir
            else{
                $real_dir = $pre_dir.$dir;
            }
        }
        
        if($tagType=='dir'){
            $data = api(array('method'=>'m_file_search','module'=>'baidu','recursion'=>$recursion,'pre_dir'=>$real_dir,'page'=>$page,'pageSize'=>$pageSize,'key'=>$key,'access_token'=>$access_token,'db'=>$db,'uk'=>$uk,'baseDb'=>$baseDb,'unUseDb'=>$unUseDb,'recursion'=>$recursion,'fileType'=>$fileType,'orderType'=>$orderType));
        }else{
            $data = array('list'=>array(),'pageInfo'=>array('totalCount'=>0,'totalPage'=>1,'page'=>1,'pageSize'=>20));
        }
    }else{
        //处理得到真实的dir
        //没有传递dir
        if(!$dir){
            // 前台目录为空
            if($pre_dir==""){
                $dir = "/";
                $real_dir="/";
            }
            // 取得前台目录
            else{
                $real_dir = $pre_dir;
            }
        }
        // 传递了dir
        else{
            $real_dir = $pre_dir.$dir;
        }
        
        if($tagType=='dir'){
            $data = api(array('method'=>'m_file_list','module'=>'baidu','dir'=>$real_dir,'page'=>$page,'pageSize'=>$pageSize,'access_token'=>$access_token,'db'=>$db,'uk'=>$uk,'baseDb'=>$baseDb,'unUseDb'=>$unUseDb));
        }else{
            $data = array('list'=>array(),'pageInfo'=>array('totalCount'=>0,'totalPage'=>1,'page'=>1,'pageSize'=>20));
        }
    }
    //前台应该展示的“真实路径”
    $pre_real_dir = substr($real_dir,strlen($pre_dir));
    $bp3_tag->assign("dir",$pre_real_dir);
    // 是否还有下一页(仅搜索接口）
    $has_more = $data['has_more'] ?? null;
    $pageInfo = $data['pageInfo'] ?? array();
    $bp3_tag->assign("pageInfo",json_encode($pageInfo));

    // 当前目录非空，生成导航数组
    if($pre_real_dir!=""){
        $dirs = explode('/',$pre_real_dir); // 取得路径
        $dir_path = '';  // 新的访问路径
        $dir_paths = [null,]; // 存储新路径组
        for($i=1;$i<count($dirs);$i++){
            if($dirs[$i]==""){
                continue;
            }
            $dir_path.='/';
            $dir_path.=$dirs[$i];
            $dir_paths[$i] = $dir_path;
            $dir_link = urlencode($dir_path);
            $item = ["enc"=>$dir_link,"name"=>$dirs[$i]];
            $nav[] = $item;
        }
    }
    $nav = $nav ?? array();
    $bp3_tag->assign("nav",$nav);
    $bp3_tag->assign("nav_count",count($nav));

    $bp3_tag->assign("key",$key);
    $loadThumb = isset($config['loadThumb']) ? $config['loadThumb'] : 1;  //是否加载缩略图？
    // 处理data
    $hideFile = explode(",",$hideFile);
    if(!empty($hideDir)){
        $hideDir = explode("||",$hideDir);
    }
    foreach ($data['list'] as $k=> & $row){
        
        if(isset($row['uk'])){
            $hideFile = $hideFiles[$row['uk']] ?? '';
            $hideDir = $hideDirs[$row['uk']] ?? '';
            $hideFile = explode(",",$hideFile);
            if(!empty($hideDir)){
                $hideDir = explode("||",$hideDir);
            }
        }
        
        // 隐藏文件夹列表
        if(!empty($hideDir)){
            $flag = false;
            foreach ($hideDir as $val){
                if(substr($row['path'],0,strlen($val)) == $val){
                    $flag = true;
                    break;
                }
            }
            if($flag){
                unset($data['list'][$k]);
                continue;
            }
        }
        if($row['isdir']==1){
            // 去掉前缀
            $path = substr($row['path'],strlen($pre_dir));
            // 编码后的地址
            $encode_path = urlencode($path);
            // 存储变更
            $row['path'] =  $path;
            $row['encode_path'] =  $encode_path;
        }else{
            // 隐藏文件列表
            if(!empty($hideFile) && in_array($row['fs_id'],$hideFile)){
                unset($data['list'][$k]);
                continue;
            }
            // 显示大小
            $row['show_size'] = height_show_size($row['size']);
            // 去掉前缀的title
            $row['title'] = substr($row['path'],strlen($pre_dir));
            //强制加uk
            $row['uk'] = $row['uk'] ?? $u;
            //根据文件的后缀，选择对应的icon
            $iconPath = CFG_STATIC_URL."/img/icon/file.png";
            $fileExt = str::fileExtName($row['path']);
            if($fileExt=="zip" || $fileExt=="rar" || $fileExt=="7z" || $fileExt=="jar"){
                $iconPath = CFG_STATIC_URL."/img/icon/zip.png";
            }
            elseif($fileExt=="mp3" || $fileExt=="flac"){
                $iconPath = CFG_STATIC_URL."/img/icon/mp3.png";
            }
            elseif($fileExt=="pdf"){
                $iconPath = CFG_STATIC_URL."/img/icon/pdf.png";
            }
            elseif($fileExt=="doc" || $fileExt=="docx"){
                $iconPath = CFG_STATIC_URL."/img/icon/word.png";
            }
            elseif($fileExt=="xls" || $fileExt=="xlsx"){
                $iconPath = CFG_STATIC_URL."/img/icon/xls.png";
            }
            elseif($fileExt=="ppt" || $fileExt=="pptx"){
                $iconPath = CFG_STATIC_URL."/img/icon/ppt.png";
            }
            elseif($fileExt=="txt" || $fileExt=="md" || $fileExt=="py" || $fileExt=="php" || $fileExt=="js" || $fileExt=="css" || $fileExt=="html"){
                $iconPath = CFG_STATIC_URL."/img/icon/code.png";
            }
            elseif($fileExt=="exe"){
                $iconPath = CFG_STATIC_URL."/img/icon/exe.png";
            }
            $row['icon'] = $iconPath;
            if(!$loadThumb){
                unset($row['thumbs']);
            }
        }
    }

    $bp3_tag->assign("close_dload",$close_dload);
    $bp3_tag->assign("close_dlink",$close_dlink);
    $bp3_tag->assign("data",$data);
    $bp3_tag->assign("login_url",$login_url);
    
    //轮播图
    $barners = isset($config['barners']) && $config['barners'] ? $config['barners'] : array();
    if(empty($barners)){
        $barners = array(
            [
                'path' => '/static/img/carousel/1.png',
                'title' => "$title - $subtitle",
                'link' => ''
            ],
            [
                'path' => '/static/img/carousel/2.png',
                'title' => "$title - $subtitle",
                'link' => ''
            ],
            [
                'path' => '/static/img/carousel/3.png',
                'title' => "$title - $subtitle",
                'link' => ''
            ]
        );
    }
    
    $bp3_tag->assign("barners",$barners);
    
    $bp3_tag->assign("hideIndexNavbar",$config['hideIndexNavbar'] ?? 0);
    
    $bp3_tag->assign("tagType",$tagType);
    
    //取出所有的page
    $pageIndex = 1;
    $pageList = array();
    $pageDir = BASE_ROOT."/temp/pages/";
    $pageContent = '';
    $selectPageIndex = $_GET['tag'] ?? '1';
    while(file_exists($pageDir."$pageIndex.html")){
        $pageImg = file_exists($pageDir."$pageIndex.png") ? BASE_URL."/temp/pages/$pageIndex.png" : BASE_URL.'/static/img/icon/folder.png';
        $pageName = file_exists($pageDir."$pageIndex.txt") ? file_get_contents($pageDir."$pageIndex.txt") : '页面'.$pageIndex;
        $pageList[$pageIndex] = array('name'=>$pageName,"icon"=>$pageImg);
        if($pageIndex == $selectPageIndex){
            $pageContent = $bp3_tag->fetch($pageDir."$pageIndex.html");
        }
        $pageIndex++;
    }
    $bp3_tag->assign("pageList",$pageList);
    $bp3_tag->assign("pageContent",$pageContent);
    
    //取出所有的links
    $linkDir = BASE_ROOT."/temp/links/";
    $linkList = array();
    $linkIndex = 1;
    while(file_exists($linkDir."$linkIndex.json")){
        $linkItem = file_get_contents($linkDir."$linkIndex.json");
        $linkItem = json_decode($linkItem,true);
        $linkList[] = $linkItem;
        $linkIndex ++;
    }
    $bp3_tag->assign("linkList",$linkList);

    display();