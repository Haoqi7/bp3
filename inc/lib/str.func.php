<?php

class str{

    /**
     * 判断字符串是否为邮箱
     * @param string $email
     * @return bool
     */
    public static function testEmail(string $email){
        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return true;
        }else{
            return false;
        }
    }

    /**
     * 匹配一个字符串是否以子字符串开头
     * @param string $str 原字符串
     * @param string $needle 子字符串
     * @return bool
     */
    public static function startWith(string $str,string $needle){
        if(substr($str,0,strlen($needle)) == $needle){
            return true;
        }else{
            return false;
        }
    }

    /**
     * 匹配一个字符串是否以子字符串结尾
     * @param string $str 原字符串
     * @param string $needle 子字符串
     * @return bool
     */
    public static function endWith(string $str, string $needle){
        if(substr($str,0,-strlen($needle)) == $needle){
            return true;
        }else{
            return false;
        }
    }
    
    /**
     * 获取最后一个 / 后的内容，通常是获取文件名
     */ 
    public static function lastFileName($filePath){
        $filePath = str_replace("\\","/",$filePath); //先尝试替换反斜杠
        $index = strrpos($filePath,"/");
        if($index===false){ //没找到
            $index = 0;
        }else{  // 找到了，加1，排除/本身
            $index += 1;
        }
        return substr($filePath,$index);
    }
    
    /**
     * 获取文件扩展名
     */ 
    public static function fileExtName($filePath){
        //获取最后一个 . 的内容
        $index = strrpos($filePath,".");
        if($index===false){ //没找到
            return "";
        }else{  // 找到了，加1，排除.本身
            $index += 1;
            return substr($filePath,$index);
        }
    }
    
    /**
     * 自动转换编码，默认UTF-8
    */
    public static function autoCharCode($str,$char="UTF-8"){
        return mb_convert_encoding($str, $char);
    }
    
}