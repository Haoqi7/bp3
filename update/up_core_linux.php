<?php
// linux系统上的更新（目录分隔符为 / ）
require_once("../functions.php");

force_login();


$lock_file = TEMP_DIR."/"."up_lock.php";  // 版本更新锁定文件

if(empty($temp_uri)){
    build_err("缺少源码zip文件");
}

$zip = new \ZipArchive;
$zip->open($temp_uri, \ZipArchive::CREATE);

$file_name = $zip->getNameIndex(0);
// 指定一个临时目录
$temp_dir = TEMP_DIR."/update";  // 用于解压源码的临时文件夹

// 简单判断上传的文件是否合法
if($file_name != "bp3-main/"){
    // 一般来说，仅包含bp3-main的文件夹的压缩包（名字为 bp3-main/）
    $zip->close();
    unlink($temp_uri);
    build_err("请从github下载bp3代码，现在上传的不是bp3代码");
}else{

    if(file_exists($lock_file)){

        build_err("更新失败，已经存在另一个正在执行的升级任务");

    }else{
        file_put_contents($lock_file,"1");

        // 解压到缓存文件夹
        $zip->extractTo($temp_dir);
        $zip->close();

        $temp_path = $temp_dir."/bp3-main";  // 解压后必定得到此目录，文件在此目录下

        // 开始进行文件覆盖
        $arr = ls_deep($temp_path,1);

        // 遍历第一层，注意update文件夹与config.php特殊处理
        foreach($arr as $key=>$value)
        {
            if($value['is_dir']==1){
                // 说明是文件夹

                // 从原文件夹 bp3-main/dir 到 ../dir 的所有文件全部覆盖
                // 使用特定函数，递归复制一个目录下的文件
                recurse_copy($temp_path."/".$value['name'],BP3_ROOT."/".$value['name'],array("noExt"=>file::$picExt));

            }else{
                // 是文件
                if($value['name']=="config.php"){
                    continue;  // 不再允许导入zip时更新config，因为比较危险，且没有意义，如需导入config，请单独导入
                }elseif($value['name']=='conf_base.php'){
                    // 基础配置文件，单独处理
                    $base = require($temp_path."/conf_base.php");
                    // 新增base中独立项，但不会覆盖config原有项
                    $config = arr2_merge($config,$base);;
                    // 手动指定更新版本号
                    $config['version'] = $base['version'];
                    $config['update_time'] = date("Y-m-d H:i:s");
                    $config['update_notice'] = 0;
                    $config['static_v'] = time();
                    // 存储合并后的新配置文件
                    save_config();

                    // 覆盖旧conf_base.php
                    copy($temp_path."/"."conf_base.php",BP3_ROOT."/"."conf_base.php"); // 从缓存目录覆盖到根目录
                }else{
                    
                    //判断文件名，如果不是图片，直接覆盖
                    $ext = str::fileExtName($value['name']);
                    if(!in_array($ext,file::$picExt) || !file_exists(BP3_ROOT."/".$value['name'])){
                        // 全部覆盖
                        $src_name = $temp_path.'/'.$value['name'];
                        $dest_name = BP3_ROOT."/".$value['name'];
                        copy($src_name,$dest_name);
                    }
                }
            }
        }
        // 删除bp3-main.zip，删除bp3-main文件夹, 删除锁定文件
        unlink($temp_uri);
        del_dir($temp_dir);
        unlink($lock_file);
        build_success("程序已更新完毕，可能看起来无变化，一般版本号会不同");
    }
}
