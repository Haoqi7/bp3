<?php

/**
http请求函数
 */

/** 1
 * 快速 file_get_content()
 * @param string $url 目标url
 * @param array|null $opt 请求配置
 * @return false|string 请求结果字符串
 */
function easy_file_get_content(string $url,array $opt=null){
    $opt = empty($opt)? easy_build_opt() : $opt;
    $result = file_get_contents($url,false,stream_context_create($opt));
    err_msg_file_get_content($opt,$http_response_header);
    return $result;
}

/** 2
 * 如果file_get_content失败，则输出提示
 * 无需参数，
 * @param array|null $opts 请求头信息
 * @param array|null $response 请求响应信息
 * @param string|null $user 用户信息
 */
function err_msg_file_get_content(array $opts=null,array $response=null,string $user=null){

    // 如果未传递$response，尝试获取global的$http_response_header变量
    if(isset($response)){
        $http_response_header = $response;
    }else{
        global  $http_response_header;
    }
    // 校验程序是否传递了http返回变量
    if(!is_array($http_response_header)){
        build_err("不存在响应头信息");
    }
    if(empty($user)){
        global $user;
    }
    // 校验程序是否传递了user变量
    if(!check_session($user)){
        // 请求失败且未登录
        if(!check_http_code($http_response_header[0])){
            build_err("http请求失败");
        }
    }else if(!check_http_code($http_response_header[0])){
        // 请求失败，但已登录
        build_err("http请求失败",false);
        easy_dump(error_get_last());
        if($opts){
            easy_echo("以下为请求头信息：");
            easy_dump($opts);
        }
        easy_echo("以下为响应头信息：");
        easy_dump($http_response_header);
        die;
    }
}

/** 3
 * 创建file_get_content或fopen的opt
 *
 * @param string $method 指定请求方法，默认GET
 * @param string|array $content 指定请求参数键值对
 * @param array $header 指定请求头数组（注意只能是一维字符串数组，每个一条），默认使用使用百度网盘ua
 * 例如  ['User-Agent:pan.baidu.com','Cookie:age=12'])
 * @return array
 */
function easy_build_opt(string $method="GET",$content=null, array $header=["User-Agent:pan.baidu.com"]){

    if(!empty($content)){
        // 是否为数组？
        if(is_array($content)){
            $content = http_build_query($content);
        }
    }

    return [
        'http'=>[
            'method'=>$method,
            'header'=>$header,
            'content'=>$content,
        ],
        'ssl'=>[
            'verify_peer' => false,
            'verify_peer_name' => false,
        ],
    ];
}

/** 4
 * 设置内置允许的状态码
 * @param string $response_line 返回的请求行
 * @return bool 是否为允许的响应状态码，如果true为允许，false为失败
 */
function check_http_code(string $response_line){
    $arr = explode(" ",$response_line);
    $code = (int)$arr[1]; // 响应状态码
    $check = [200,302];   // 合法的状态码，不在这其中的为不合法.
    foreach ($check as $k=>$v){
        if($v==$code){
            return true;
        }
    }
    return false;
}

/** 5
 * 获取重定向地址
 * @param array $response_header
 * @return mixed|string
 */
function get_http_redirect(array $response_header){
    foreach ($response_header as $k=>$v){
        $split = explode(": ",$v);
        if(count($split)>1){
            if($split[0]=="Location"){
                return $split[1];
            }
        }
    }
    return "";
}

/** 6
 * 快速进行fopen
 * @param string $filename
 * @param string $mode
 * @param array|null $opt
 * @return false|resource
 */
function easy_fopen(string $filename,string $mode,array $opt=null){
    $opt = empty($opt)? easy_build_opt() : $opt;

    return @fopen($filename,$mode,false,stream_context_create($opt));
}

//浏览器UA
define("CHROME_UA","Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.36 Edg/103.0.1264.49");
/** 基础的 curl，默认 Get 请求
 * @param $url
 * @param array $param 参数
 * @return bool|string
 */
function easy_curl($url,$param=array()){
    $ch = curl_init($url);
    // 通用设置
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    // 是否设置自定义请求头
    $requestHeader = $param['requestHeader'] ?? array("User-Agent: pan.baidu.com");
    if($requestHeader){
        curl_setopt($ch,CURLOPT_HTTPHEADER,$requestHeader);
    }
    // 是否为post
    $postData = $param['postData'] ?? null;
    if($postData){
        curl_setopt($ch,CURLOPT_POST,true);
        curl_setopt($ch,CURLOPT_POSTFIELDS,$postData);
    }
    $result = curl_exec($ch);
    curl_close($ch);
    return $result;
}


/** 响应头辅助函数
 * @param $curl -- 固定参数1，cURL的资源句柄
 * @param $headerLine  -- 固定参数2，返回每一行header
 * @return int
 */
function headerHandler($curl, $headerLine) {
    $len = strlen($headerLine);
    // HTTP响应头是以:分隔key和value的
    $split = explode(':', $headerLine, 2);
    if (count($split) > 1) {
        $key = trim($split[0]);
        $value = trim($split[1]);
        // 将响应头的key和value存放在全局变量里
        $GLOBALS['G_HEADER'][$key] = $value;
    }
    return $len;
}

/**
 * 使用 curl 获取 响应头
 * @param string $url 请求URL
 * @param string[] $requestHeader 请求头
 * @return mixed
 */
function easy_curl_head(string $url,$requestHeader=array("User-Agent: pan.baidu.com")){
    $ch = curl_init($url);
    // 通用设置
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($ch, CURLOPT_NOBODY, true);  // 不要body，否则大文件会卡死
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, "headerHandler"); // 设置header处理函数
    curl_setopt($ch, CURLINFO_HEADER_OUT, true);  // 从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
    //是否自定义请求头
    if(!empty($requestHeader)){
        curl_setopt($ch,CURLOPT_HTTPHEADER,$requestHeader);
    }
    curl_exec($ch);
    $header = curl_getinfo($ch, CURLINFO_HEADER_OUT); //取得请求头
    curl_close($ch);
    return $GLOBALS['G_HEADER'] ?? array();
}

/** 使用 curl 从远程下载文件
 * @param $url -- 远程地址
 * @param array $requestHeader -- 自定义请求头
 * @param $filename -- 保存的文件名（如果不指定，则自动获取）
 * @param $destDir -- 保存的目录（如果不指定，则为当前文件夹）
 * @param $isRange -- 是否断点续传，支持断点续传，请在 Header 中手动指定 Range 具体数据
 */
function easy_curl_down($url,$requestHeader=array(),$filename=null,$destDir=null,$isRange=false){

    // 一些设置
    set_time_limit(0); // if the file is large set the timeout.
    // 是否自动获取文件名（并非所有响应都返回文件名）
    if(empty($filename)){
        $respHeader = easy_curl_head($url,$requestHeader);
        $filenameLine = $respHeader['Content-Disposition'];
        $filenameArr = explode('"',$filenameLine);
        $filename = $filenameArr[1];
        if(empty($filename)){
            die("自动获取文件名失败，请手动指定");
        }
    }
    // 保存的位置
    $saveFile = $filename;
    if(!empty($destDir)){
        $saveFile = $destDir."/".$saveFile; //指定文件夹
    }

    $new_file = fopen($saveFile, "w") or die("cannot open" . $saveFile);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_FILE, $new_file);  // 保存文件到服务器
    if($isRange){ // 是否断点续传
        curl_setopt($ch,CURLOPT_FTPAPPEND,true);  // 写入文件时，是追加而不是覆盖
    }
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    //是否自定义请求头
    if(!empty($requestHeader)){
        curl_setopt($ch,CURLOPT_HTTPHEADER,$requestHeader);
    }

    //下载进度条函数
    curl_setopt($ch, CURLOPT_NOPROGRESS, false); //开启进度条
    curl_setopt($ch, CURLOPT_PROGRESSFUNCTION, 'progressHandler');

    //开始下载
    curl_exec($ch);
    if (curl_errno($ch)) {
        echo "the cURL error is : " . curl_error($ch);
    } else {
        $status = curl_getinfo($ch);
        echo $status["http_code"] == 200 ? "The File is Downloaded" : "The error code is : " . $status["http_code"] ;
    }
    //关闭资源
    curl_close($ch);
    fclose($new_file);
}

/** curl 下载进度条辅助函数
 * @param $ch -- 资源句柄
 * @param $downloadSize -- 预计下载总字节
 * @param $downloaded -- 当前下载总字节
 * @param $uploadSize  -- 预计上传总字节
 * @param $uploaded  -- 当前上传总字节
 */
function progressHandler($ch, $downloadSize, $downloaded, $uploadSize, $uploaded)

{
    $process = array(
        'downloadSize'=>$downloadSize,
        'downLoaded'=>$downloaded,
        'uploadSize'=>$uploadSize,
        'uploaded'=>$uploaded
    );
    // set_session('downLoadProgress',$process);
//    file_put_contents(TEMP_DIR."/progress.log",json_encode($process).PHP_EOL,FILE_APPEND);
}
