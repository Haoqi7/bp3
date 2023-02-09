<?php

class file{

    public static $picExt = array("ico","png","jpg","jpeg");

    //递归删除一个非空目录
    public static function deldir($dir) {
        $dh = opendir($dir);
        while ( $file = readdir($dh) ) {
            if ( $file != '.' && $file != '..' ) {
                $fullpath = $dir.'/'.$file;
                if ( !is_dir($fullpath) ) {
                    unlink($fullpath);
                } else {
                    self::deldir($fullpath);
                }
            }
        }
        return rmdir($dir);
    }

    /**
     * 检测文件编码
     * @param string $file 文件路径
     * @return string|null 返回 编码名 或 null
     */
    public static function detect_encoding($file) {
        $list = array('UTF-8', 'GBK', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1');
        $str = file_get_contents($file);
        foreach ($list as $item) {
            $tmp = mb_convert_encoding($str, $item, $item);
            if (md5($tmp) == md5($str)) {
                return $item;
            }
        }
        return null;
    }

    /**
     * 自动解析编码读入文件
     * @param string $file 文件路径
     * @param string $charset 读取编码
     * @return string 返回读取内容
     */
    public static function auto_read($file, $charset='UTF-8') {
//        $list = mb_list_encodings(); //可以获取所有受支持的编码，但是太多了，只使用常用的几种
        $list = array('UTF-8', 'GBK', 'UTF-16LE', 'UTF-16BE', 'ISO-8859-1');
        if(filesize($file)>5*1024*1024){ // 5mb 大概10万行，超过这个数绝不正常
            return "文件太大了，不可预览";
        }
        $str = file_get_contents($file);
        foreach ($list as $item) {
            $tmp = mb_convert_encoding($str, $item, $item);
            if (md5($tmp) == md5($str)) {
                return mb_convert_encoding($str, $charset, $item);
            }
        }
        return "";
    }
    
    /**
     * 5.1 递归遍历列出文件（兼容win）
     * @param string $dir 要列出的文件夹，可使用相对路径或绝对路径
     * @param string $level 如果传递了数字，会指定多少遍历层数，从1开始
     * @param string $type 指定文件或文件夹 dir || file ，默认不限
     * @return array 如果是文件夹，则is_dir=1,且存在son属性，文件则is_dir=0
     */
    public static function ls_deep(string $dir, $param = array())
    {
        $files = array();
        $level = $param['level'] ?? "max"; //层级，max则无限遍历
        $type  = $param['type'] ?? ""; // 文件类别筛选 ？ dir || file
        if (@$handle = opendir($dir)) { //注意这里要加一个@，不然会有warning错误提示：）
            while (($file = readdir($handle)) !== false) {
                if ($file != ".." && $file != ".") { //排除当前目录和父级目录
                    // 文件夹
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) { //如果是子文件夹，就进行递归
                        if ($level == "max") {  // 无限遍历
                            $arr = ["is_dir" => 1, "name" => $file, "son" => self::ls_deep($dir . DIRECTORY_SEPARATOR . $file, array("level"=>"max","type"=>$type))];
                        } else {  // 必须是数字
                            if (is_numeric($level)) {
                                // 判断当前级数还有下级
                                if ($level > 1) {
                                    $arr = ["is_dir" => 1, "name" => $file, "son" => self::ls_deep($dir . DIRECTORY_SEPARATOR . $file, array("level"=>$level - 1,"type"=>$type))];
                                } else { // 最后一级了
                                    $arr = ["is_dir" => 1, "name" => $file];
                                }
                            }
                        }
                    } // 文件
                    else {
                        $arr = ["is_dir" => 0, "name" => $file];
                    }
                    if( ($type=="file" || $type=="") && $arr['is_dir']==0 ||  ($type=="dir" || $type=="") && $arr['is_dir']==1){
                        $files[] = $arr;
                    }
                }
            }
            closedir($handle);
            return $files;
        } else {
            return array();
        }
    }
    
    /**
     * 递归获取文件【仅文件，不要文件夹】
    */
    public static function ls_deep_file(string $dir,$param=array()){
        $files = array();
        $level = $param['level'] ?? "max"; //层级，max则无限遍历
        if (@$handle = opendir($dir)) { //注意这里要加一个@，不然会有warning错误提示：）
            while (($file = readdir($handle)) !== false) {
                if ($file != ".." && $file != ".") { //排除当前目录和父级目录
                    // 文件夹
                    if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) { //如果是子文件夹，就进行递归
                        if ($level == "max") {  // 无限遍历
                            $sonFiles = self::ls_deep_file($dir . DIRECTORY_SEPARATOR . $file, array("level"=>"max"));
                            $files = array_merge($files,$sonFiles);
                        } else {  // 必须是数字
                            if (is_numeric($level)) {
                                // 判断当前级数还有下级
                                if ($level > 1) {
                                    $sonFiles = self::ls_deep_file($dir . DIRECTORY_SEPARATOR . $file, array("level"=>$level - 1));
                                    $files = array_merge($files,$sonFiles);
                                }
                                //只要文件，所有最后一级目录不遍历
                            }
                        }
                    } // 文件
                    else {
                        $files[] = $dir. "/" .$file;  //直接存文件名
                    }
                }
            }
            closedir($handle);
            return $files;
        } else {
            return array();
        }
    }
}