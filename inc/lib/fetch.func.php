<?php


class fetch
{

    //浏览器UA
    public static string $chrome_ua = "Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/103.0.5060.114 Safari/537.36 Edg/103.0.1264.49";


    /** 基础的 curl，默认 Get 请求
     * @param $url
     * @param array $param 参数
     * @return bool|string
     */
    public static function easy_curl($url, $param = array())
    {
        $ch = curl_init($url);
        // 通用设置
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        // 是否设置自定义请求头
        $requestHeader = $param['requestHeader'] ?? array();
        if ($requestHeader) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
        }
        // 是否为post
        $postData = $param['postData'] ?? array();
        if ($postData) {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
        }
        //是否设置cookie
        $cookie = $param['cookie'] ?? "";
        if($cookie){
            curl_setopt($ch, CURLOPT_COOKIE , $cookie);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }


    /** 响应头辅助函数
     * @param $curl -- 固定参数1，cURL的资源句柄
     * @param $headerLine -- 固定参数2，返回每一行header
     * @return int
     */
    public static function headerHandler($curl, $headerLine)
    {
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
     * 发送请求，同时获取响应头【数组】和响应体【字符串】。
    */
    public static function curl_with_head(){
        
        
        
        return array("head"=>array(),"body"=>"");
    }

    /**
     * 使用 curl 获取 响应头
     * @param string $url 请求URL
     * @param string[] $requestHeader 请求头
     * @return mixed
     */
    public static function easy_curl_head(string $url, $requestHeader = array("User-Agent: pan.baidu.com"))
    {
        $ch = curl_init($url);
        // 通用设置
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($ch, CURLOPT_NOBODY, true);  // 不要body，否则大文件会卡死
        curl_setopt($ch, CURLOPT_HEADERFUNCTION, "self::headerHandler"); // 设置header处理函数
        curl_setopt($ch, CURLINFO_HEADER_OUT, true);  // 从 PHP 5.1.3 开始可用。这个很关键，就是允许你查看请求header
        //是否自定义请求头
        if (!empty($requestHeader)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
        }
        curl_exec($ch);
        $header = curl_getinfo($ch, CURLINFO_HEADER_OUT); //取得请求头
        curl_close($ch);
        return $GLOBALS['G_HEADER'];
    }

    /** 使用 curl 从远程下载文件
     * @param $url -- 远程地址
     * @param array $requestHeader -- 自定义请求头
     * @param $filename -- 保存的文件名（如果不指定，则自动获取）
     * @param $destDir -- 保存的目录（如果不指定，则为当前文件夹）
     * @param $isRange -- 是否断点续传，支持断点续传，请在 Header 中手动指定 Range 具体数据
     */
    public static function easy_curl_down($url, $requestHeader = array(), $filename = null, $destDir = null, $isRange = false)
    {

        // 一些设置
        set_time_limit(0); // if the file is large set the timeout.
        // 是否自动获取文件名（并非所有响应都返回文件名）
        if (empty($filename)) {
            $respHeader = self::easy_curl_head($url, $requestHeader);
            $filenameLine = $respHeader['Content-Disposition'];
            $filenameArr = explode('"', $filenameLine);
            $filename = $filenameArr[1];
            if (empty($filename)) {
                die("自动获取文件名失败，请手动指定");
            }
        }
        // 保存的位置
        $saveFile = $filename;
        if (!empty($destDir)) {
            $saveFile = $destDir . "/" . $saveFile; //指定文件夹
        }

        $new_file = fopen($saveFile, "w") or die("cannot open" . $saveFile);

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_FILE, $new_file);  // 保存文件到服务器
        if ($isRange) { // 是否断点续传
            curl_setopt($ch, CURLOPT_FTPAPPEND, true);  // 写入文件时，是追加而不是覆盖
        }
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

        //是否自定义请求头
        if (!empty($requestHeader)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $requestHeader);
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
            echo $status["http_code"] == 200 ? "The File is Downloaded" : "The error code is : " . $status["http_code"];
        }
        //关闭资源
        curl_close($ch);
        fclose($new_file);
    }

    /** curl 下载进度条辅助函数
     * @param $ch -- 资源句柄
     * @param $downloadSize -- 预计下载总字节
     * @param $downloaded -- 当前下载总字节
     * @param $uploadSize -- 预计上传总字节
     * @param $uploaded -- 当前上传总字节
     */
    public static function progressHandler($ch, $downloadSize, $downloaded, $uploadSize, $uploaded)

    {
        $process = array(
            'downloadSize' => $downloadSize,
            'downLoaded' => $downloaded,
            'uploadSize' => $uploadSize,
            'uploaded' => $uploaded
        );
//    set_session('downLoadProgress',$process);
//    file_put_contents(TEMP_DIR."/progress.log",json_encode($process).PHP_EOL,FILE_APPEND);
    }

}