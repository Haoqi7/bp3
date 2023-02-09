<?php

// windows 系统上的更新

require_once("../functions.php");

force_login();

$lock_file = TEMP_DIR.DIRECTORY_SEPARATOR."up_lock.php";  // 版本更新锁定文件

// 指定一个临时目录
$temp_dir = TEMP_DIR.DIRECTORY_SEPARATOR."update";  // 用于解压源码的临时文件夹

// 把zip解压
$zip = new \ZipArchive;
if(empty($temp_uri)){
    build_err("缺少源码zip文件");
}
$zip->open($temp_uri, \ZipArchive::CREATE);  // 指定$temp_uri是上传的压缩包名
$zip->extractTo($temp_dir);
$zip->close();

// 简单判断上传的文件是否合法
$check_dir = $temp_dir.DIRECTORY_SEPARATOR."bp3-main";
if(!is_dir($check_dir)){  // 不存在bp3-main文件夹

    del_dir($temp_dir);
    unlink($temp_uri);
    build_err("请从github下载bp3代码，现在上传的不是bp3代码");

}else{

    if(file_exists($lock_file)){

        build_err("更新失败，已经存在另一个正在执行的升级任务");

    }else{
        file_put_contents($lock_file,"1");  // 升级锁定

        // 开始进行文件覆盖
        $arr = ls_deep($check_dir,1);

        // 遍历第一层，注意update文件夹与config.php特殊处理
        foreach($arr as $key=>$value)
        {
            if($value['is_dir']==1){
                // 说明是文件夹
                // 使用特定函数，递归复制一个目录下的文件
                recurse_copy($check_dir.DIRECTORY_SEPARATOR.$value['name'],BP3_ROOT.DIRECTORY_SEPARATOR.$value['name'],array("noExt"=>file::$picExt)); // 从缓存目录覆盖到根目录

            }else{
                // 是文件
                if($value['name']=="config.php"){
                    continue;  // 不再允许导入zip时更新config，因为比较危险，且没有意义，如需导入config，请单独导入
                }
                elseif($value['name']=='conf_base.php'){  // 处理base文件

                    // 基础配置文件，单独处理
                    $base = require($check_dir.DIRECTORY_SEPARATOR."conf_base.php");  // 以新的base文件为准
                    // 新增base中独立项，但不会覆盖config原有项
                    $config = arr2_merge($config,$base);
                    // 手动指定更新版本号
                    $config['version'] = $base['version'];
                    $config['update_time'] = date("Y-m-d H:i:s");
                    $config['update_notice'] = 0;
                    $config['static_v'] = time();
                    // 存储合并后的新配置文件
                    save_config();
                    // 覆盖旧conf_base.php
                    copy($check_dir.DIRECTORY_SEPARATOR."conf_base.php",BP3_ROOT.DIRECTORY_SEPARATOR."conf_base.php"); // 从缓存目录覆盖到根目录
                }else{
                    //判断文件名，如果不是图片，直接覆盖
                    $ext = str::fileExtName($value['name']);
                    if(!in_array($ext,file::$picExt) || !file_exists(BP3_ROOT.DIRECTORY_SEPARATOR.$value['name'])){
                        // 全部覆盖
                        $src_name = $check_dir.DIRECTORY_SEPARATOR.$value['name'];  // 缓存目录
                        $dest_name = BP3_ROOT.DIRECTORY_SEPARATOR.$value['name'];  // 根目录
                        copy($src_name,$dest_name);  // 从缓存目录覆盖到根目录
                    }
                }
            }
        }
        // 删除缓存zip，删除解压临时文件夹, 删除锁定文件
        unlink($temp_uri);
        del_dir($temp_dir);
        unlink($lock_file);
        build_success("程序已更新完毕，可能看起来无变化，一般版本号会不同");
    }
}
