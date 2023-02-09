<?php
    require_once("../functions.php");

    force_login();  // 强制登录

    $db = (int)$config['useDb'];
    $db = $_GET['db'] ?: $db;

    // 多账户解析token
    $u = $_GET['u'] ?: 1;
    if($u!=1){
        $access_token = $config['disks'][$u]['access_token'];
        $uk = $u;
    }

    //是否使用sqlite
    $baseDb = $config['baseDb'] ?? array();
    $baseDb = in_array($uk,$baseDb);
    if($baseDb){
        $baseDb = $config['bindDb'][$uk];
    }

    $base_dir = force_get_param("base_dir");
    // 单次查找10000条
    $limit = 10000;

    // 最大目录层级3层（仅在数据量超过限定数量时，此限制生效，如果数量在可控范围内则无效）
    $limit_deep = 3;

    $is_beyond_deep = false;

    $encode_dir = urlencode($base_dir);
    $bp3_tag->assign("base_dir",$base_dir);
    $has_more = true;
    $page = 1;
    $curTree = get_session('tree') ?? array();
    if(isset($curTree['page']) && $curTree['page']){
        $page = (int)(get_session('tree')['page'])+1;  // 如果进行到一半触发频控，则接着下一页，默认第一页
    }

    while ($has_more){

        $start = ($page-1) * $limit;  // 起始位置
        // 缓存数据
        $arr = api(array('method'=>'tree','module'=>'baidu','encode_dir'=>$encode_dir,'access_token'=>$access_token,'limit'=>$limit,'start'=>$start,'uk'=>$uk,'db'=>$db,'baseDb'=>$baseDb));
        $curTree = get_session('tree');
        $curTree[$page] = $arr;
        $curTree['page'] = $page;
        set_session('tree',$curTree);
        // 是否还有数据
        $has_more = (bool)$arr['has_more'];
        if($has_more){
            $page ++ ;
        }
        // 最多支持循环几次？
        if($page>3){  // 支持 n * limit 条，推荐为 3w 条，数据过大会超过 php 默认的 128m 内存
            break;
        }
    }
    // 拼接数据
    $arr = & $curTree[1]['list'];
    for($i=2; $i<=$curTree['page']; $i++){
        $arr = array_merge($curTree[$i]['list'],$arr);
    }
    // 删除缓存
    set_session('tree',null);

    $record = count($arr);
    $bp3_tag->assign("record",$record);  // 取得的记录数
    $bp3_tag->assign("has_more",$has_more);  // 取得的记录数

    $pathStr = explode("/",$base_dir); // 分割根目录
    $base_dir_count = count($pathStr);  // 根目录层级，只记录数量，如 /apps/share，这里取得 2
    $lastPath = $pathStr[count($pathStr)-1];  // 取得目录名称，用于展示，如 /apps/share ，则这里取得 share

    $bp3_tag->assign("lastPath",$lastPath);
    $all_size = 0;  // 根目录大小
    
    // 提取目录
    $dir_arr = [];  // 这个数组，存储所有目录

    $base_dir_info = ['name'=>$lastPath,'deep'=>1,'size'=>0];
    $dir_arr[$base_dir] = $base_dir_info;  // 根目录，深度为 1， 遍历总列表
    $activeData = array();  // 动态库
    $activeData[1][$base_dir] = $base_dir_info;

    foreach ($arr as $row){
        if($row['isdir']){
            // 生成子目录info
            $deep = count(explode("/",$row['path']))-$base_dir_count+1;  // 计算子目录层级数，深度动态计算得到

            if($has_more && $deep>$limit_deep){  // 如果还有更多数据，且大于限定层级，说明数据量非常大，丢弃部分数据否则容易造成卡死
                $is_beyond_deep = true;
                continue;
            }

            $dir_info = ['name'=>$row['server_filename'],'deep'=>$deep,'size'=>0,'parent'=>0];
            
            $dir_arr[$row['path']] = $dir_info;

            $activeData[$deep][$row['path']] = $dir_info;
        }
    }
    // 前面得到的数据，没有层级关系，现在开始为每个目录寻找父目录，并记录最大层次
    $max_dir=0;
    foreach($dir_arr as $key=>$value)
    {
        // 并记录最大层次
        if($max_dir<$value['deep']){
            $max_dir=$value['deep'];
        }
        // 初始化大小为0
        $dir_arr[$key]['size']=0;
        // 2层以下的父目录均为base_dir
        if($value['deep']<=2){
            $dir_arr[$key]['parent'] = $base_dir;
        }
        // 3层以上的父目录，需要手动寻找上一层
        else{
            // 遍历找出上一层的所有目录
              $before_arr=$activeData[$value['deep']-1];
            // 遍历该目录，如果该目录中任意一个被当前包含，则说明是当前父目录
            foreach($before_arr as $key3=>$value3)
            {
                if(strpos($key,$key3)!==false){
                    $dir_arr[$key]['parent'] = $key3;
                }
            }
        }
        // 修正activeData
        $activeData[$value['deep']][$key]['parent'] = $dir_arr[$key]['parent'];
    }

    // 开始给目录，按递归方式排序
    $dir_sort = [];
    // 根层
    $dir_sort[$base_dir] = $dir_arr[$base_dir];
    // 第二层开始，递归排序
    $start_loop = 2;
    $arrForDeep = isset($activeData[$start_loop]) ? $activeData[$start_loop] : null;
    if(!empty($arrForDeep)){
        foreach ($arrForDeep as $k=>$v){
            $dir_sort[$k] = $v;  // 添加到 sort 中
            newSort($start_loop+1,$k,$dir_sort,$max_dir);
        }
    }


    // 新排序算法，递归添加层数
    function newSort($start_loop,$parent,& $dir_sort,$max_dir){
        // 如果已遍历所有层级
        if($start_loop>$max_dir){
            return;
        }
        global $activeData;
        // 取出下一级
        $nextArr = $activeData[$start_loop];
        if(!empty($nextArr)){
            foreach ($nextArr as $key => $val){
                if($val['parent'] == $parent){
                    $dir_sort[$key] = $val;
                    newSort($start_loop+1,$key,$dir_sort,$max_dir);
                }
            }
        }
    }

    // 目录排序完毕
    $dir_arr = & $dir_sort;

    // 给文件夹添加文件，并计算文件夹大小
    foreach ($arr as $row){
        
        if(!$row['isdir']){
            $deep = count(explode("/",$row['path']))-$base_dir_count+1;  // 计算子目录层级数，深度动态计算得到
            if($has_more && $deep>$limit_deep+1){ // 层级太深的，丢弃；
                continue;
            }
            // 累计根目录文件夹总大小
            $all_size += $row['size'];
            // 查找文件最后一个 / ，以识别它所在的目录
            $index = strrpos($row['path'],"/");
            $dir_path = substr($row['path'],0,$index);
            // 根据它所在的目录，累计其文件夹大小
            $dir_arr[$dir_path]['size'] += $row['size'];
            // 根据所在目录，把文件添加到其list属性中
            $dir_arr[$dir_path]['list'][$row['server_filename']] = $row['size'];
            
        }
    }

    // 递归算法，从最末层次开始，累计父文件夹大小
    fixedDir($dir_arr,$max_dir,$max_dir);
    function fixedDir($dir_arr,$current,$max_dir){
        global $dir_arr;
        if($current<1){
            return;
        }else{
            // 让当前层次的所有大小添加到其父目录中去
            foreach($dir_arr as $key=>$value)
            {
                if($value['deep']==$current && $value['parent']!=$key){
                    $dir_arr[$value['parent']]['size'] += $value['size'];
                }
            }
        }
        fixedDir($dir_arr,$current-1,$max_dir);
    }
    $bp3_tag->assign("all_size",$all_size); // 根文件夹总大小
    $bp3_tag->assign("max_dir",$max_dir);  // 最高层次
    $bp3_tag->assign("is_beyond_deep",$is_beyond_deep);  // 最高层次
    $bp3_tag->assign("data",$dir_arr);  // 数据
    display();

