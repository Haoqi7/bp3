<?php
    // 图片列表展示
    require_once("../functions.php");
    
    force_login();

    // 多账户解析token
    $u = $_GET['u'] ?: 1;
    if($u!=1){
        $access_token = $config['disks'][$u]['access_token'];
    }
    $bp3_tag->assign("u",$u);
    
    $path = force_get_param("path");
    
    // 一次至多查找$num张
    $encode_path = urlencode($path);

    $page = $_GET['page'] ?? 1;
    $pageSize = $_GET['pageSize'] ?? 50;
    $url = "http://pan.baidu.com/rest/2.0/xpan/file?parent_path=$encode_path&access_token=$access_token&web=1&recursion=1&method=imagelist&num=$pageSize&page=$page";
    
    $result = easy_curl($url);
    
    $json = json_decode($result,true);
    
    $info = $json['info'];
    
    $realNum = count($info);  // 实际数量
    
    $display_count = 0;

    // 图片过滤、分类统计
    $dataHouse = array();  // 数据
    foreach ($info as $info_i){
        $fileName = $info_i['server_filename'];
        $file_end = substr($fileName, strrpos($fileName, '.')+1);
        if($file_end=="png"||$file_end=="PNG"||$file_end=="jpg"||$file_end=="JPG"||$file_end=="jpeg"||$file_end=="JPEG"){

            $display_count ++;  // 取得的有效数量++
            // 计算当前图片日期
            
            $img_date = date("Y 年 m 月 d 日",$info_i['server_ctime']);

            $dataHouse[$img_date][] = $info_i;
        }
    }
    $bp3_tag->assign("page",$page);
    $bp3_tag->assign("pageSize",$pageSize);
    $firstPage = $page>2 ? 1 : ""; // 首页
    $bp3_tag->assign("firstPage",$firstPage);
    $prePage  = $page>1? $page-1 : "";  // 上一页
    $bp3_tag->assign("prePage",$prePage);
    $nextPage = $pageSize==$realNum? $page+1 : "";  // 下一页
    $bp3_tag->assign("nextPage",$nextPage);
    $bp3_tag->assign("realNum",$realNum);
    $bp3_tag->assign("display_count",$display_count);
    $bp3_tag->assign("path",$path);
    $bp3_tag->assign("encPath",$encode_path);
    $bp3_tag->assign("dataHouse",$dataHouse);
    $bp3_tag->assign("filter",count($dataHouse));
//    easy_dump($dataHouse);
    display();
    

