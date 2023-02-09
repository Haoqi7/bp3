<?php

/**
 * 基本内置函数，命名随意
 * 如果辅助函数只使用一次，则立刻定义为常量
 */

/* ******   一、处理目录和URL参数  ******* */
$now = $time = time();
!defined("TIME") && define("TIME",$time);
!defined("NOW") && define("NOW",$now);
/**
 * 1.1 获取网站所在的根目录
 */
function get_site_root()
{
    return $_SERVER['DOCUMENT_ROOT'];
}

!defined("SITE_ROOT") && define("SITE_ROOT", get_site_root());

/**
 * 1.2 获取网站真实的根目录Url
 * 注意，程序不一定安装在根目录，如果获取程序所在根目录，请使用get_base_url()
 */
function get_site_url()
{
    return (isset($_SERVER['HTTPS']) ? "https" : "http") . '://' . $_SERVER['HTTP_HOST'];
}

!defined("SITE_URL") && define("SITE_URL", get_site_url());


/**
 * 1.3 获取当前页面的Url绝对地址
 * @param bool $args 是否需要 .php 后的内容，默认为 false，也就是默认是 file_url 或者说 self_url，加 true 参数后得到完整的 page_url
 * @return string
 */
function get_page_url($args = false)
{
    // 返回完整URL
    if ($args) {
        return SITE_URL . $_SERVER['REQUEST_URI'];
    } // 省略URI
    else {
        return SITE_URL . $_SERVER['PHP_SELF'];  // 并非真正的pageUrl，而是当前页面路径，也就是不需要 .php 后面的内容
    }
}

!defined("FILE_URL") && define("FILE_URL", get_page_url());
!defined("SELF_URL") && define("SELF_URL", FILE_URL);
!defined("PAGE_URL") && define("PAGE_URL", get_page_url(true));
/**
 *  1.4 获取当前页面的目录的url地址
 * 如果指定当前所在目录与层级，可以使用返回指定层级的地址
 * 例如，参数为1时，返回上一层目录
 *       参数为2时，返回上两层目录
 * @param int $parent 指定获取目录层级
 * @return string 获取目录url
 */
function get_dir_url($parent = 0)
{
    $page_url = PAGE_URL;

    $dir_url = "";
    $arr = explode("/", $page_url);
    $count = count($arr) - $parent;
    for ($i = 0; $i < $count - 1; $i++) {
        // 拼接字段
        $dir_url .= $arr[$i];
        // 拼接分隔符
        if ($i < $count - 2) {
            $dir_url .= "/";
        }
    }

    return $dir_url;
}

!defined("DIR_URL") && define("DIR_URL", get_dir_url()); //当前目录URL

/**
 * 1.5 获取目录的物理路径
 * @param int $level 传入0则当前目录，传入1则上级目录，2上上级，以此类推
 * @param string $dir 文件或目录，如果不存在，则默认是当前执行脚本的所在目录
 * @return string 物理路径
 */
function get_dir_root(int $level = 0, string $dir = "")
{
    // 默认起始为当前目录
    if (empty($dir)) {
        $dir = $_SERVER['SCRIPT_FILENAME'];
    }
    // 层级为0
    if ($level == 0) {
        $ph_dir = dirname($dir);
    } // 指定层级
    else {
        $ph_dir = dirname($dir, $level + 1);
    }
    return $ph_dir;
}

!defined("DIR_ROOT") && define("DIR_ROOT", get_dir_root()); //当前目录物理路径

/**
 * 1.6 获取程序所在的根目录
 */
function get_base_root()
{
    return get_dir_root(1, __FILE__);
}

!defined("BASE_ROOT") && define("BASE_ROOT", get_base_root()); // 站点根目录
!defined("BP3_ROOT") && define("BP3_ROOT", BASE_ROOT); // 站点根目录

//错误日志
$logDir = BASE_ROOT."/log";
if(!is_dir($logDir)){
    mkdir($logDir,0777);
}
$errorLog = $logDir."/php_errors.log";  //错误日志
ini_set("error_log",$errorLog);
//错误代理函数【基础的错误处理】
function allErrorHandler($errno, $errstr, $errfile, $errline) {
    
    //$errno就是错误等级，只是int的格式
    $errors = array(
            E_ERROR => "Error",
            E_WARNING => "Warning",
            E_NOTICE => "Notice"
    );
    $error_name = $errors[$errno] ?? $errno;
    
    //检测文件夹
    $logDir = BASE_ROOT."/log/";
    //指定错误文件
    $errorFile = $logDir."php_errors.php";
    //尝试查找堆栈
    $c = debug_backtrace();
    //错误消息
    $errorFormat = array(
            'time'=> date('Y-m-d H:i:s'),
            'errno'=> $errno,
            'errorName' => $error_name,
            'errStr'=>mb_convert_encoding($errstr, "UTF-8"),
            'file' => substr($errfile,strlen(BASE_ROOT)),
            'line' => $errline,
            'ignore' => 0,
            'trace'=>preg_replace_callback ('/^ +/m', function ($m) {
       return str_repeat (' ', strlen ($m[0]) / 1); //默认4格缩进 / 1 【完整trace，转str是为了避免调用未定义变量从而出错，又能看到trace调用，如果需要可在js中转码，此时不会产生异常】
     }, json_encode ($c, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE))
        );
    
    $text = "<?php return ".var_export($errorFormat,true).";";
    file_put_contents($errorFile,$text);
    
    return false;  //返回false，继续默认的错误处理
}



/**
 * 1.6.1获取站点前缀目录
 */
function get_site_prefix(){
    // 获取当前物理根目录
    $cur_ph_dir = BASE_ROOT;
    // 取得网站物理根目录
    $base_ph_dir = SITE_ROOT;
    // 长度一致，说明部署在根目录下，返回 site_url 即可
    if (strlen($cur_ph_dir) == strlen($base_ph_dir)) {
        return "";
    }
    // 取得site_url + 子目录的目录名 ，就是程序根目录 url
    else {
        $sub_url = substr($cur_ph_dir, strlen($base_ph_dir));
        $sub_url = str_replace("\\", "/", $sub_url); // 兼容win
        return $sub_url;
    }
}
!defined("SITE_PREFIX") && define("SITE_PREFIX", get_site_prefix()); // 站点前缀

/**
 * 1.7 获取部署的根目录
 * 首先必须指定当前页面与程序根目录的关系（上一层）
 * 从而经过一系列操作后得到程序根目录url
 */
function get_base_url()
{

    // 获取当前物理根目录
    $cur_ph_dir = BASE_ROOT;
    // 取得网站物理根目录
    $base_ph_dir = SITE_ROOT;
    // 长度一致，说明部署在根目录下，返回 site_url 即可
    if (strlen($cur_ph_dir) == strlen($base_ph_dir)) {
        return SITE_URL;
    } // 取得site_url + 子目录的目录名 ，就是程序根目录 url
    else {
        $sub_url = substr($cur_ph_dir, strlen($base_ph_dir));
        $sub_url = str_replace("\\", "/", $sub_url); // 兼容win
        // 加入到网站真实url后，形成网站根目录url
        return SITE_URL . $sub_url;
    }

}

!defined("BASE_URL") && define("BASE_URL", get_base_url());
!defined("BP3_URL") && define("BP3_URL", BASE_URL);

/**
 * 1.8 获取指定文件的url（相对程序根目录）
 * @param string $file
 * @return string 指定文件的url
 */
function get_file_url(string $file)
{
    return BASE_URL . $file;
}

/* ***  第二大类 简单参数处理   ** */

/**
 * 2.1 格式化调试变量
 * @param @name 要调试的变量
 * @param bool $json 是否使用 json 格式输出，默认false
 */
function easy_dump($name,$json=false)
{
    echo "<pre>";
    if($json){
        echo json_encode($name);
    }
    else{
        print_r($name);
    }
    echo "</pre>";
}

/**
 * 2.2 打印可换行的消息
 * @param $msg
 */
function easy_echo($msg)
{
    echo "<p>";
    echo $msg;
    echo "</p>";
}

/**
 * 2.3 构建并输出错误消息（应存在错误项errno，通常为-1）
 * @param array|string|null $msg
 * @param bool $die 是否停止解析，默认true
 */
function build_err($msg = null, bool $die = true)
{
    $time = date("Y-m-d H:i:s");
    $info = "error!";
    //数组处理
    if (is_array($msg)) {
        if (!isset($msg['errno'])) {
            $msg['errno'] = -1;
        }
        if (!isset($msg['errmsg'])) {
            $msg['errmsg'] = $info;
        }
        if (!isset($msg['time'])) {
            $msg['time'] = $time;
        }
        if (!isset($msg['state'])) {
            $msg['state'] = 500;
        }
        echo json_encode($msg, JSON_UNESCAPED_UNICODE);
    } //字符串处理
    elseif (is_string($msg)) {
        echo json_encode(array("errno" => -1, "errmsg" => $msg, 'time' => $time, 'state'=>500), JSON_UNESCAPED_UNICODE);
    } //默认值
    elseif (empty($msg)) {
        echo json_encode(array("errno" => -1, "errmsg" => $info, 'time' => $time, 'state'=>500), JSON_UNESCAPED_UNICODE);
    }
    //是否终止
    if ($die) {
        die;
    }
}

/**
 * 2.3.1 构造错误信息，并 die
 * @param null $msg
 */
function die_error($msg=null){
    build_err($msg,true);
}

/**
 * 2.4 创建一个请求正确响应消息
 * @param array|string|null $msg
 * @param bool $die
 */
function build_success($msg = null, $die = true)
{
    $time = date("Y-m-d H:i:s");
    $msg_arr = array();
    // 默认处理
    if (empty($msg)) {
        $msg_arr = ["errno" => 0, "errmsg" => "success", 'time' => $time, 'state'=>200];
    } // 字符串处理
    elseif (is_string($msg)) {
        $msg_arr = ["errno" => 0, "errmsg" => $msg, 'time' => $time,'state'=>200];
    } // 数组处理
    elseif (is_array($msg)) {
        if (!isset($msg['errno'])) {
            $msg['errno'] = 0;
        }
        if (!isset($msg['errmsg'])) {
            $msg['errmsg'] = "success";
        }
        if (!isset($msg['time'])) {
            $msg['time'] = $time;
        }
        if (!isset($msg['state'])) {
            $msg['state'] = 200;
        }
        $msg_arr = $msg;
    }
    echo json_encode($msg_arr, JSON_UNESCAPED_UNICODE);
    //是否终止
    if ($die) {
        die;
    }
}

/**
 * 2.5 强制传递指定get参数
 * @param string $param_name
 * @return mixed
 */
function force_get_param(string $param_name)
{
    $param = $_GET[$param_name];
    if (isset($param)) {
        return $param;
    } else {
        build_err("缺少get参数：" . $param_name);
    }
}

/**
 * 2.6 强制传递指定post参数
 * @param string $param_name 参数名称（字符串）
 * 例如，必须传递名为path的post参数，则：$path = force_post_param("path");
 * @return mixed
 */
function force_post_param(string $param_name)
{
    $param = $_POST[$param_name];
    if (isset($param)) {
        return $param;
    } else {
        build_err("缺少post参数：" . $param_name);
    }
}


/**
 * 2.7 高可用显示文件大小
 * @param int $byte 字节
 * @return string $h_str 高可用大小字符串
 */
function height_show_size($byte)
{
    $byte = (double)$byte;
    $h_str = '';
    if ($byte < 1024) {
        $h_str = $byte . 'B';
    } elseif ($byte < 1048576) {
        $num = $byte / 1024;
        $h_str = number_format($num, 2) . 'kB';
    } elseif ($byte < 1073741824) {
        $num = $byte / 1048576;
        $h_str = number_format($num, 2) . 'MB';
    } elseif ($byte < 1099511627776) {
        $num = $byte / 1073741824;
        $h_str = number_format($num, 2) . "GB";
    } elseif ($byte < 1125899906842624) {
        $num = $byte / 1099511627776;
        $h_str = number_format($num, 2) . "TB";
    } elseif ($byte < 1152921504606846976) {
        $num = $byte / 1125899906842624;
        $h_str = number_format($num, 2) . "PB";
    } else {  // 最大，计算为 EB ，已知 php int 类型最大为 8.00 EB
        $num = $byte / 1152921504606846976;
        $h_str = number_format($num, 2) . "EB";
    }
    return $h_str;
}


/**
 * 2.8 快速重定向（302）
 * @param string $url 需要重定向的地址 (完整的http，或者相对地址如../)
 * @param int $lazy_time 延迟单位毫秒，如果未传值则0毫秒
 */
function redirect(string $url, int $lazy_time = 0)
{
    !empty($lazy_time) && usleep($lazy_time);

    header("Location: $url");
}

// 检测网站目录是否可写
if(!is_writeable(BASE_ROOT)){
    easy_echo("警告：当前程序根目录无写入权限");
}

// 缓存根目录
!defined("TEMP_DIR") && define("TEMP_DIR",BP3_ROOT."/temp");
if(!file_exists(TEMP_DIR)){
    mkdir(TEMP_DIR);
}

/* ******  一些js简单处理     ******** */

/**
 * 3.1 快速重定向到指定地址
 * @param string $url 需要重定向的地址 (完整的http，或者相对地址如../)
 * @param int $lazy_time 延迟单位毫秒，如果未传值则200毫秒
 */
function js_location(string $url, $lazy_time = 200)
{
    echo("<script>setTimeout('window.location=\'$url\'', $lazy_time )</script>");
}

/**
 * 3.2 js输出提示
 * @param string $message
 */
function js_alert(string $message)
{
    echo "<script>alert('$message')</script>";
}


/* *****     四、一些登录或session处理      ******* */
// 开启session
session_start([
    'cookie_lifetime' => 86400,
    'read_and_close'  => true,
]);


/**
 * 4.1 强制登录
 * @param string $user 需要检测的session，默认使用全局变量
 */
// 定义登录管理员使用的session
$user = "user";
// 登录地址url
$login_url = get_file_url("/login.php");
function force_login($param=array())
{
    $user = $param['user'] ?? "user";
    $scope = $param['scope'] ?? '';
    $tryLogin = $param['tryLogin'] ?? '';
    if (!get_session($user)) {
        //尝试读取cookie
        $c = get_cookie($user);
        if($c){
            set_session($user,$c);
        }
        if($tryLogin){
            return false;
        }else{
            if(SELF_URL==BASE_URL."/api/ajax.php"){
                build_err("请先登录，才能执行此操作。");
            }
            // 指定 scope 不为 api 时，重定向
            elseif($scope!="api"){
                global $login_url;
                redirect($login_url . "?redirect=" . urlencode(PAGE_URL));
            }
        }
    }
}
//限制api必须登录
function api_login(){
    force_login(array('scope'=>'api'));
}




/*     五、一些文件操作    */

/**
 * 5.1 递归遍历列出文件（兼容win）
 * @param string $dir 要列出的文件夹，可使用相对路径或绝对路径
 * @param string $level 如果传递了数字，会指定多少遍历层数，从1开始
 * @return array 如果是文件夹，则is_dir=1,且存在son属性，文件则is_dir=0
 */
function ls_deep(string $dir, $level = "max")
{
    $files = array();
    if (@$handle = opendir($dir)) { //注意这里要加一个@，不然会有warning错误提示：）
        while (($file = readdir($handle)) !== false) {
            if ($file != ".." && $file != ".") { //排除当前目录和父级目录
                // 文件夹
                if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) { //如果是子文件夹，就进行递归
                    if ($level == "max") {  // 无限遍历
                        $arr = ["is_dir" => 1, "name" => $file, "son" => ls_deep($dir . DIRECTORY_SEPARATOR . $file, "max")];
                    } else {  // 必须是数字
                        if (is_numeric($level)) {
                            // 判断当前级数还有下级
                            if ($level > 1) {
                                $arr = ["is_dir" => 1, "name" => $file, "son" => ls_deep($dir . DIRECTORY_SEPARATOR . $file, $level - 1)];
                            } else { // 最后一级了
                                $arr = ["is_dir" => 1, "name" => $file];
                            }
                        }
                    }
                } // 文件
                else {
                    $arr = ["is_dir" => 0, "name" => $file];
                }
                $files[] = $arr;
            }
        }
        closedir($handle);
        return $files;
    } else {
        return array();
    }
}


/**
 * 5.2 递归复制文件夹（兼容win)
 * @param string $src 原文件夹
 * @param string $dst 新文件夹
 */
function recurse_copy(string $src, string $dst,array $param=array())
{
    $dir = opendir($src);
    if(!is_dir($dst)){
        @mkdir($dst);
    }
    $noExt = $param['noExt'] ?? array();  //忽略覆盖的后缀，只要是这些后缀，不会覆盖
    while (false !== ($file = readdir($dir))) {
        if (($file != '.') && ($file != '..')) {
            if (is_dir($src . DIRECTORY_SEPARATOR . $file)) {
                recurse_copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file, $param);
            } else {
                $index = strrpos($file,".");
                if($index===false){ //没找到
                    $fileExt = "";
                }else{  // 找到了，加1，排除.本身
                    $index += 1;
                    $fileExt = substr($file,$index);
                }
                //不在忽略的后缀中，或文件不存在则会复制过去。忽略的后缀不覆盖，文件存在也不覆盖
                if(!in_array($fileExt,$noExt) || !file_exists($dst . DIRECTORY_SEPARATOR . $file)){
                    copy($src . DIRECTORY_SEPARATOR . $file, $dst . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
    }
    closedir($dir);
}


/**
 * 5.3 递归删除文件夹及其文件（兼容win)
 * @param string $dir 删除的目录
 * @return bool
 */
function del_dir(string $dir)
{
    //先删除目录下的文件：
    $dh = opendir($dir);
    while ($file = readdir($dh)) {
        if ($file != "." && $file != "..") {
            $full_path = $dir . DIRECTORY_SEPARATOR . $file;
            if (!is_dir($full_path)) {
                unlink($full_path);
            } else {
                del_dir($full_path);
            }
        }
    }
    closedir($dh);
    //删除当前文件夹：
    if (rmdir($dir)) {
        return true;
    } else {
        return false;
    }
}


/**
 * 5.4 递归合并2个数组，程序中主要用于合并config与conf_base
 * @param array $old 旧数组
 * @param array $append 待添加数组
 * @return array 合并后的数据
 */
function arr2_merge(array $old, array $append)
{

    $new_arr = $old; // 默认是旧数据中的内容

    // 遍历新数组并添加未存在的数据
    foreach ($append as $key => $value) {

        // 还是数组，继续递归
        if (is_array($append[$key])) {
            // 校验旧数组是否存在对应子项
            if (isset($old[$key])) {
                $new_arr[$key] = arr2_merge($old[$key], $append[$key]);
            } else {
                $new_arr[$key] = arr2_merge(array(), $append[$key]);
            }
        } // 普通元素，判断是否存在，如果不存在，则添加
        else {

            if (!array_key_exists($key, $old)) {
                $new_arr[$key] = $value;
            } else {
                $new_arr[$key] = $old[$key];
            }
        }
    }

    return $new_arr;
}

//定义所有的配置文件（对应 k -- v （array 中的 path 为文件路径，cache 是否自动缓存）
$configArr = array(
    'name' => '_cache_item_',
    'time' => '_cache_time_',
    'item' =>array(
        'base'=>array('path'=>'/conf_base.php'),
        'main'=>array('path'=>'/config.php','cache'=>true),
        'errorSql'=>array('path'=>'/log/errorSql.php'),
        'apiFailLog'=>array('path'=>'/log/apiFailLog.php'),
        'asyncDb'=>array('path'=>'/config/asyncDb.config.php'),
    )
);

/**
 * 获取(生成)内存前缀
 * @return string
 */
function get_session_prefix(){
    $str = ""; // 空字符串头
    //存在站点前缀，则把目录名称，拼接在字符串中间
    if(SITE_PREFIX){
        //去掉第一个/
        $str = substr(SITE_PREFIX,1,strlen(SITE_PREFIX));
        //例如 aa/bb ，则生成 aa_bb
        $str = str_replace("/","_",$str);
        //再加上 _
        $str .= "_";
    }
    $str .= "bp3_"; // bp3_ 尾
    return $str;
}

//定义内存前缀
!defined("SESSION_PREFIX") && define("SESSION_PREFIX",get_session_prefix());
//cookie前缀（和session保持一致）
!defined("COOKIE_PREFIX") && define("COOKIE_PREFIX",SESSION_PREFIX);

/**
 * 操作session（不要直接操作，而是用此方法操作具体的值）
 * @param $type
 * @param $name
 * @param null $value
 * @return mixed
 */
function op_session($type,$name, $value = null){
    //拼接名称
    $session_name = SESSION_PREFIX.$name;
    //取session
    if($type=="get"){
        if(isset($_SESSION[$session_name])){
            return $_SESSION[$session_name];
        }else{
            return null;
        }
    }
    //存session
    elseif($type=="set"){
        session_start();
        $_SESSION[$session_name] = $value;
        session_write_close();
    }
}

/**
 * 取某个session
 * @param $name
 * @return mixed
 */
function get_session($name){
    return op_session("get",$name);
}

/**
 * 设置某个session
 * @param $name
 * @param $value
 */
function set_session($name,$value){
    op_session("set",$name,$value);
}

/**
 * 操作cookie
 * @param $type
 * @param $name
 * @param $value
 * @param $expire
 * @return mixed
 */
function op_cookie($type,$name, $value = '', $expire=null){
    //拼接名称
    $cookie_name = COOKIE_PREFIX.$name;
    //取cookie
    if($type=="get"){
        if(isset($_COOKIE[$cookie_name])){
            return $_COOKIE[$cookie_name];
        }else{
            return null;
        }
    }
    //存cookie
    elseif($type=="set"){
        //默认路径，只在当前程序根目录下有效（不允许修改，否则可能出现问题）
        $path = SITE_PREFIX."/";
        //默认时间 1 小时
        $expire = $expire ?: time()+3600;
        setcookie($cookie_name,$value,$expire,$path);
    }
}


/**
 * 取某个cookie
 * @param $name
 * @return mixed
 */
function get_cookie($name){
    return op_cookie("get",$name);
}

/**
 * 设置某个session
 * @param $name
 * @param $value
 * @param null $expire (过期时间）
 */
function set_cookie($name,$value,$expire=null){
    op_cookie("set",$name,$value,$expire);
}



/**
 * 5.5 获取配置文件（默认获取用户配置文件）
 * @param string $name 配置项
 * @param bool $force 是否强制从文件读取
 * @return mixed
 */
function get_config(string $name="main", bool $force = false)
{
    // 全局配置文件
    global $configArr;
    if(empty($configArr['item'][$name])){
        build_err("配置项不存在：get_config");
    }
    $config_item = $configArr['item'][$name];
    // 取出并拼接配置文件路径
    $config_path = $config_item['path'];
    $file_path = BASE_ROOT.$config_path;
    // 是否缓存
    $cache = isset($config_item['cache']) && $config_item['cache'];
    // 缓存项的内存名(  prefix . __cache_item__ . main  )
    $cache_name = SESSION_PREFIX.$configArr['name'].$name;
    // 文件系统的最后修改时间
    if(file_exists($file_path)){
        $file_last_time = filemtime($file_path);
    }else{
        $file_last_time = 0;
    }
    // 缓存中记录的上次修改内存名(  prefix . __cache_time__ . main  )
    $cache_last_name = SESSION_PREFIX.$configArr['time'].$name;
    // 如果开启了缓存，并且已经被缓存，并且不是强制刷新，并且内存中的保存的修改时间和文件的实际修改时间一致（防止缓存失效，并自动刷新）
    if ($cache && get_session($cache_name) && !$force && get_session($cache_last_name) ==$file_last_time) {
//        easy_echo($name."：从缓存中读取");
        return get_session($cache_name);
    } else {
        // 配置项默认值
        if($name=="main"){
            $config = ['user' => [], 'site' => [], 'control' => [], 'inner' => [], 'identify' => [], 'connect' => [], 'baidu' => [], 'basic' => [], 'account' => []];
        }else{
            $config = array();
        }
        // 如果文件存在，则从文件中读取
        if (file_exists($file_path)) {
            $config = require($file_path);
//            easy_echo($name."：从文件中读取");
        }
        // 是否存入session缓存
        if ($cache) {
            set_session($cache_name,$config);
            set_session($cache_last_name,$file_last_time);
        }
        return $config;
    }
}


/**
 * 5.6 保存config配置（数组）
 * @param string $name
 * @param array|null $config
 * @return false|int
 */
function save_config($name = "main", array $config = null)
{
    $name = $name ?: "main";
    // 配置项是否存在
    global $configArr;
    if(empty($configArr['item'][$name])){
        build_err("配置项不存在：save_config");
    }
    // 默认保存的数据内容
    if (is_null($config)) {
        // 主配置文件，忽略时从 global 读取，并进行校验
        if($name=="main"){
            global $config;
            // 不为空、必须为数组、应该存在版本号（用于校验数据是否合法）
            if(empty($config) || !is_array($config) || empty($config['version'])){
                build_err("参数不合法：save_config");
            }
        }
        // 其他配置文件，不允许为空
        else{
            build_err("缺少参数：save_config");
        }
    }
    // 配置项数据额外处理
    if($name=="main"){
        $config['change_time'] = date("Y-m-d H:i:s");  // 记录文件的修改时间到文件内容中
    }
    // 取得配置文件项
    $config_item = $configArr['item'][$name];
    // 文件保存的路径
    $config_path = $config_item['path'];
    $save_path = BASE_ROOT.$config_path;
    // 保存到文件中
    $text = '<?php return ' . var_export($config, true) . ';';
    $dir_name = dirname($save_path);
    if(!is_dir($dir_name)){
        mkdir($dir_name,0777,true);
    }
    $res = file_put_contents($save_path,$text);  // 返回成功写入的字节数，失败false
    // 是否缓存，如果缓存，则更新缓存内容
    $is_cache = $config_item['cache'] ?? 0;
    if($res && $is_cache){
        // 缓存项目名和时间名
        $cache_name = SESSION_PREFIX.$configArr['name'].$name;
        $cache_last_name = SESSION_PREFIX.$configArr['time'].$name;
        // 更新到缓存中
        set_session($cache_name,$config);
        $save_time = filemtime ($save_path);
        set_session($cache_last_name,$save_time);// 记录保存的时间
    }
    return $res;
}

/**
 * 5.7 安装检测
 * @param string $config_path 主配置文件目录（自动拼接程序目录）
 * @param array $install_paths 未安装前，允许访问的目录，第一个页面为安装页面
 * @return bool 返回true已安装，若未安装，先判断当前页面是否允许，允许返回false，不允许则自动重定向
 */
function check_install(string $config_path, array $install_paths)
{
    // 如果检测不到配置文件
    if (!file_exists(BASE_ROOT . $config_path)) {
        $redirect = true;
        // 遍历当前页面，是否属于其中的一个页面，如果是则不做任何处理
        foreach ($install_paths as $v) {
            if (FILE_URL == BASE_URL . $v) {
                $redirect = false;
            }
        }
        //说明当前页面不是，应该进行重定向
        if ($redirect) {
            redirect(BASE_URL . $install_paths[0]);
        }
        return false;
    }
    return true;
}


/**
 * 5.8 检测是否存在某个session，默认检测管理员session
 * @param string $user
 * @return bool 存在返回true
 */
function check_session(string $user = "")
{
    if (empty($user)) {
        global $user;
    }
    if (get_session($user)) {
        return true;
    } else {
        return false;
    }
}


/*    六、IP处理等  */

/**
 * 6.1 获取服务器IP
 */
function get_server_ip()
{
    $server_hostname = $_SERVER['SERVER_NAME'];
    return gethostbyname($server_hostname);
}

/**
 * 6.2 获取客户端IP
 */
function get_remote_ip()
{
    //客户端IP 或 NONE
    $ip = false;
    if (!empty($_SERVER["HTTP_CLIENT_IP"])) {
        $ip = $_SERVER["HTTP_CLIENT_IP"];
    }
    //多重代理服务器下的客户端真实IP地址（可能伪造）,如果没有使用代理，此字段为空
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ips = explode(", ", $_SERVER['HTTP_X_FORWARDED_FOR']);
        if ($ip) {
            array_unshift($ips, $ip);
            $ip = FALSE;
        }
        for ($i = 0; $i < count($ips); $i++) {
            if (!preg_match("/^(10│172.16│192.168)./i", $ips[$i])) {
                $ip = $ips[$i];
                break;
            }
        }
    }
    return ($ip ? $ip : $_SERVER['REMOTE_ADDR']);
}

/**
 * 6.3 强制获取一个session域的数据
 * @param string $param
 * @return mixed
 */
function force_session_param(string $param)
{
    if (get_session($param)) {
        return get_session($param);
    } else {
        build_err("缺少session参数：" . $param);
    }
}


/**
 * 6.4 自动获取页面 template
 */
function getTemplate()
{
    $tpl = "";
    // 1.获取最后一个 / 后的内容
    $page_url = FILE_URL;
    $arr = explode("/", $page_url);
    if ($arr <= 0) {
        return "";
    }
    $page_url = end($arr);
    // 获取第一个 . 前的内容
    $arr = explode(".", $page_url);
    if ($arr <= 0) {
        return "";
    }
    $page_url = current($arr);
    // 指定 .html
    $tpl = $page_url . ".html";
    return $tpl;
}

/**
 * 6.5 显示主题页面
 * @param string $template_dir 模板目录，自动补全前后斜杠
 * @param string $template 模板文件，自动补全 .html 后缀
 */
function display($template_dir = "", $template = "")
{
    // 尝试显示主题模板
    global $bp3_tag;
    global $config;
    global $base;
    try {
        $tpl = getTemplate();
        $bp3_tag->assign("dir_url", DIR_URL);
        $bp3_tag->assign("base_url", BASE_URL);
        $bp3_tag->assign("cfg_static_url", CFG_STATIC_URL);
        $bp3_tag->assign("page_url_args", PAGE_URL);
        $bp3_tag->assign("isMobile", IS_MOBILE);
        $bp3_tag->assign("config", $config);
        $bp3_tag->assign("base", $base);
        $bp3_tag->force_compile = true; // 默认不缓存
        // 目录与模板默认参数
        $template_dir = $template_dir ?: "";
        $tpl = empty($template) ? $tpl : $template . ".html";
        // 如果指定了目录、优先查找2个目录是否存在上述模板（指定目录（当前主题、默认主题））
        if ($tpl != "" && $template_dir != "" && file_exists(BP3_TEMPLATE_ROOT . $template_dir . "/" . $tpl)) {

            $bp3_tag->setTemplateDir(BP3_TEMPLATE_ROOT . $template_dir); // 当前主题、指定目录
            $bp3_tag->display($tpl);
        } elseif ($tpl != "" && $template_dir != "" && file_exists(BP3_TEMPLATE_ROOT_DEFAULT . $template_dir . "/" . $tpl)) {

            $bp3_tag->setTemplateDir(BP3_TEMPLATE_ROOT . $template_dir); // 默认主题、指定目录
            $bp3_tag->display($tpl);
        } // 判断2个默认目录（当前主题、默认主题）是否存在模板
        elseif ($tpl != "" && file_exists(BP3_TEMPLATE_DIR . $tpl)) {  // 当前主题、默认模板

            $bp3_tag->display($tpl);
        } elseif ($tpl != "" && file_exists(BP3_TEMPLATE_DIR_DEFAULT . $tpl)) {

            $bp3_tag->setTemplateDir(BP3_TEMPLATE_DIR_DEFAULT);  // 默认主题、默认模板
            $bp3_tag->display($tpl);
        }
    } catch (SmartyException $e) {
        // 模板不存在
    } catch (Exception $e) {
        // 不做任何处理
    }
}

/**
 * 6.6 判断是否为手机端
 * @return bool
 */
function isMobile()
{
    $useragent = strtolower($_SERVER['HTTP_USER_AGENT']);
    if (preg_match("/iphone|ios|android|mini|mobile|mobi|Nokia|Symbian|iPod|iPad|Windows\s+Phone|MQQBrowser|wp7|wp8|UCBrowser7|UCWEB|360\s+Aphone\s+Browser|AppleWebKit/", $useragent)) {
        return true;
    } else {
        return false;
    }
}

!defined("IS_MOBILE") && define("IS_MOBILE", isMobile());

/**
 * 6.7 获取主题列表
 */
function lsThemes()
{
    $themes = ls_deep(THEME_DIR, 1);
    if (count($themes) < 1) {
        build_err("前台主题丢失，无任何主题");
    } else {
        return array_column($themes, "name");
    }
}

/**
 * 6.8 判断当前是否为admin目录
 */
function isAdmin()
{
    $page_url = FILE_URL;  // 当前页面url
    $page_url_length = strlen($page_url);  // 页面url长度
    $base_url = BASE_URL;  // 根目录url
    $base_url_length = strlen($base_url); // 根目录url长度
    if ($page_url_length > $base_url_length + 7 && substr($page_url, $base_url_length, 7) == "/admin/") {  // 7 为 /admin/ 的长度
        return true;
    } else {
        return false;
    }
}

/**
 * 6.9 判断系统的类型，如果不是win，则统一标识为linux
 */
function os_type()
{
    $os_name = PHP_OS;
    if (strpos($os_name, "WIN") !== false) {
        return "win";
    } else {
        return "linux";
    }
}

/**
 * 6.10 单线程下载文件，把服务器文件下载给浏览器
 * @param string $filename 需要下载的本地文件地址，可相对或绝对地址
 * @param bool $flock 是否读取锁定
 */
function easy_read_file(string $filename, bool $flock = false)
{
    $filename = realpath($filename);
    // 计算文件大小
    $file_size = filesize($filename);
    // 直接下载
    header("Content-type: application/octet-stream");
    header("Content-Disposition: attachment; filename=" . basename($filename));
    header('Content-Length: ' . $file_size);
    header('Content-Transfer-Encoding: binary');
    $handle = fopen($filename, "rb");
    $buffer = 1024;
    $canRead = $file_size;  // 剩余可读取长度
    if ($flock) {
        flock($handle, LOCK_SH);  // 读取独占锁定
    }
    while ($canRead > 0) {
        // 剩余可读，小于buffer，把剩余可读取出来即可
        if ($canRead < $buffer) {
            echo fread($handle, $canRead);
            // 剩余可读为0
            $canRead = 0;
        } else {
            // 读取一部分
            echo fread($handle, $buffer);
            // 剩余可读减少一个buffer
            $canRead -= $buffer;
        }
    }
    fclose($handle);
}

/**
 * 6.11 生成随机密码
 * @param int $length
 * @return string
 */
function rand_pwd(int $length = 8)
{
    $charHouse = "abcdefghjkmnpqrstuvwxyzABCDEFGHJKMNPQRSTUVWXYZ123456789";
    $arr = str_split($charHouse);  // 分割字符串
    $rand_arr = array_rand($arr, $length);  // 获取随机键名
    $pwd = "";
    foreach ($rand_arr as $i) {
        $pwd .= $arr[$i];
    }
    return $pwd;
}

/**
 * 6.12 发送邮件
 * @param $subject -- 邮件主题， text
 * @param $body -- 邮件内容， html
 * @return bool
 */
function send_mail($subject, $body)
{
    // 检测配置
    global $config;
    $mailConfig = $config['mail'];
    if (empty($mailConfig) || empty($mailConfig['user']) || empty($mailConfig['pass']) || empty($mailConfig['server']) || empty($mailConfig['port']) || empty($mailConfig['receiver'])) {
        return false;
    } else {
        // 引入邮件类
        include_once(BASE_ROOT . "/inc/mail.class.php");
        // 创建实例
        $mail = new mail($mailConfig);
        // 发送邮件
        return $mail->send($subject, $body);
    }
}

/**
 * 6.13.1 获取所有的请求头，apache下自带，在 nginx 下 php小于74版本不存在
 */
if (!function_exists('getallheaders')) {
    function getallheaders()
    {
        $headers = [];
        foreach ($_SERVER as $name => $value) {
            if (substr($name, 0, 5) == 'HTTP_') {
                $headers[str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($name, 5)))))] = $value;
            }
        }
        return $headers;
    }
}
!defined("requestHeader") && define("requestHeader",getallheaders());


/**
 * 6.13.2 判断是否为ajax请求
 */
function isAjax(){
    if(isset(requestHeader['X-Requested-With']) && requestHeader['X-Requested-With']){
        return true;
    }else{
        return false;
    }
}
!defined("IsAjax") && define("IsAjax",isAjax());


/**
 * 6.14 包含一个文件夹，且可以指定文件夹深度
 * @param string $dir
 * @param string $level
 */
function include_dir(string $dir, $level = "max")
{
    if (@$handle = opendir($dir)) { //注意这里要加一个@，不然会有warning错误提示：）
        while (($file = readdir($handle)) !== false) {
            if ($file != ".." && $file != ".") { //排除当前目录和父级目录
                if (is_dir($dir . DIRECTORY_SEPARATOR . $file)) { //如果是子文件夹，就进行递归
                    if ($level == "max") {  // 无限遍历
                        include_dir($dir . DIRECTORY_SEPARATOR . $file, "max");
                    } else {  // 必须是数字
                        if (is_numeric($level)) {
                            // 判断当前不是最后一级则继续包含
                            if ($level > 1) {
                                include_dir(dir . DIRECTORY_SEPARATOR . $file, $level - 1);
                            }
                        }
                    }
                } else { //文件
                    include_once($dir . DIRECTORY_SEPARATOR . $file);
                }
            }
        }
    }
}

/**
 * 6.15 包含多个文件， 提示：可以使用 get_included_files() 取得被include和require的文件列表
 * @param array $files_arr 文件或文件夹，可以是字符串或字符串数组
 * @param array $param 参数列表
 */
function include_files($files_arr=array(), $param = array())
{
    $dir_deep = $param['dir_deep'] ?: 1;
    foreach ($files_arr as $k => $v) {
        if (is_dir($v)) {
            include_dir($v,$dir_deep);
        } else {
            include_once($v);
        }
    }
}

// api 基类
class api{
    public function __construct()
    {
        !defined("IsApi") && define("IsApi",true);
    }
}

/**
 * 6.16 调用 api 接口的方法
 * @param $param -- 统一参数封装
 * @return mixed
 */
function api($param = array())
{

    $method = $param['method'];
    $module = $param['module'];

    $classPath = BASE_ROOT . "/api/" . $module . ".class.php";
    if (!file_exists($classPath)) {
        build_err("模块不存在");
    }
    include_once($classPath);
    $param = array_merge($_POST, $_GET, $param); // 如果参数同名，后面会覆盖前面
    // 判断 module 类的静态方法是否存在
    if (!method_exists($module, $method)) {
        build_err("方法不存在");
    }
    // 当前是否ajax请求
    $isAjax = FILE_URL == BASE_URL . "/api/ajax.php";
    // 调用静态方法并获取返回值
    $res = $module::$method($param);
    // 如果访问 ajax.php，会直接输出结果并终止。
    if ($isAjax) {
        // 没有返回值
        if (empty($res)) {
            $res_str = json_encode(array("bp3_api" => "not result", "description" => "this request has empty response!"));
        } else {
            // 返回数组？
            if (is_array($res)) {
                $res_str = json_encode($res);
            } // 返回其他，如字符串， 则原样
            else {
                $res_str = $res;
            }
        }
        //如果 GET 中携带了callback和_参数，说明是jsonp请求，直接输出 jsonp 格式
        if(isset($_GET['callback']) && $_GET['callback'] && isset($_GET['_']) && $_GET['_']){
            echo $_GET['callback']."(".$res_str.")";
        }
        // 如果是访问页面，默认使用dump美化，除非传递 pretty，会取消美化
        elseif (!IsAjax && !isset($param['pretty'])) {
            easy_dump($res);
        }
        // ajax请求时原样输出
        else {
            echo $res_str;
        }
        // 停止脚本
        die;
    }
    // 程序调用，则直接返回 api 结果
    else {
        return $res;
    }
}

/**
 * 6.17 版本检测，多个方法，只检测不合法版本
 * @param $min -- 最低版本
 * @param $max -- 最高版本
 * @param $list -- 受支持的版本
 */
function versionCheck($min, $max, $list = array())
{

    // 过滤低版本
    if (version_compare(PHP_VERSION, $min, "<")) {
        easy_echo("您使用的PHP版本太低了");
    } // 过滤高版本
    elseif (version_compare(PHP_VERSION, $max, ">")) {
        easy_echo("您使用的PHP版本太高了");
    }
    // 受支持的版本列表，取前3位（如 7.4 ）
    $version = substr(PHP_VERSION, 0, 3);
    $ok = false;
    foreach ($list as $v) {
        if ($version == $v) {
            $ok = true;
            break;
        }
    }
    if (!$ok) {
        $str = join(", ", $list);
//        easy_echo("当前PHP版本{$version}，并未经过完整测试，已测试版本列表[{$str}]，如需忽略当前警告，请登录后台设置。");
    }
}

// 版本检测
versionCheck(7.0, 8.2, array("7.2", "7.3"));

/**
 * 分割字符串规范化
 * @param $str
 * @param string $split 规定以什么分割，默认 , （有的情况不适合 , 此时手动指定其他分隔符，目录分割推荐用 || ）
 * @return string
 */
function str_split_format($str,$split=","){
    $str = trim($str);  //去掉前后空格
    if($split==","){
        $str = str_replace("，",",",$str);  // 替换中文，为英文,
    }
    $str = explode($split,$str); // 转为数组
    $str = array_filter($str);  // 去掉空字符串元素
    return join($split,$str);  // 重新拼接为字符串
}

// 错误信息栈
!defined("ErrorList") && define("ErrorList",SESSION_PREFIX.'_errorList');
/**
 * 压栈一个错误信息
 * @param $msg
 */
function pushError($msg){
    $msgList = get_session(ErrorList) ?? array();
    $msgList[] = $msg;
    set_session(ErrorList,$msgList); 
}

/**
 * 弹栈一个错误信息
 */
function popError(){
    $msgList = get_session(ErrorList) ?? null;
    if(is_array($msgList)){
        $msg =  array_shift($msgList);
        set_session(ErrorList,$msgList);
        return $msg;
    }
    // 栈为空
    else{
        return false;
    }
}
//取得基础配置文件
$base = get_config("base");

//配置信息
$config = get_config("main");


//添加CSS文件
function addCss($file){
    return addStaticFile($file,"css");
}
//添加Js文件
function addJs($file){
    return addStaticFile($file,"js");
}

//添加静态文件
function addStaticFile($file,$type){
    // 开发状态下忽略缓存
    global $config;
    if(empty($config)){
        $config = get_config();
    }
    $static_v = $config['static_v'] ?? 1;
    $static_version = DEBUG ? time() : $static_v;
    if($type=="css"){
        return '<link href="'.CFG_STATIC_URL.$file.'?v='.$static_version.'" rel="stylesheet">';
    }
    elseif($type=="js"){
        return '<script src="'.CFG_STATIC_URL.$file.'?v='.$static_version.'"></script>';
    }
}


include_once("DB.class.php");
$GLOBALS['bp3_dbConfig'] = $config['dbConfig'] ?? array();


/**
 * 提取二维数组的一个或多个列（如果为一个值，则和 array_column 一模一样）
 * @param $input -- 原数组
 * @param null $column_keys
 * @param null $index_key
 * @return array
 */
function array_columns($input, $column_keys=null, $index_key=null){
    $result = array();

    //多个字符串用,分割 (可以直接传递array)
    if(is_string($column_keys)){
        $column_keys = explode(',', $column_keys);
    }
    //默认值
    $keys = $column_keys ?: array();

    if($input){
        foreach($input as $k=>$v){
            // 指定返回列
            if($keys){
                $tmp = array();
                //多列时带列名的数组
                if(count($keys)>1){
                    foreach($keys as $key){
                        $tmp[$key] = $v[$key];
                    }
                }
                //没有列名的基本元素（确保和 array_column 返回值一致）
                else{
                    $tmp = $v[$keys[0]];
                }
            }
            //返回所有列
            else{
                $tmp = $v;
            }
            //指定键名
            if(isset($index_key)){
                $result[$v[$index_key]] = $tmp;
            }
            //默认索引键名
            else{
                $result[] = $tmp;
            }
        }
    }

    return $result;
}

//注册函数库【追加到头部】
spl_autoload_register(function ($class_name) {  //会自动带上命名空间，即：namespace\class
    $path = BASE_ROOT."/inc/lib/".$class_name.".func.php";
    if(file_exists($path)){
        require $path;
    }
},true,true);

