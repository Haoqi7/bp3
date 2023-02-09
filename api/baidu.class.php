<?php

/** 百度网盘函数封装
 * Class baidu
 */
class baidu extends api{

    /**
     * 业务模型，获取basic
     * @param $param
     * @return mixed basic数组
     */
    public static function m_basic($param){
        $access_token = $param['access_token'];
        $url = "https://pan.baidu.com/rest/2.0/xpan/nas?method=uinfo&access_token=$access_token";

        $result = easy_curl($url);
        $basic = self::m_decode(array('str'=>$result));
        // 会员类型，直接转中文
        $vipType = array(
            0=>"普通用户",
            1=>'普通会员',
            2=>'超级会员'
        );
        $basic['vip_type'] = $vipType[$basic['vip_type']];

        return $basic;
    }

    /**
     * 辅助函数，检测并解析响应数据，所有业务请求均应使用此函数
     * @param $param
     * @return false|string
     */
    public static function m_decode($param){
        $str = $param['str'];
        $to_arr = $param['to_arr'] ?? true;
        $die = $param['die'] ?? true;
        $arr = json_decode($str,true);
        if(empty($arr['errno'])){
            if($to_arr){
                return $arr;
            }else{
                return json_decode($str);
            }
        }
        else{
            $msg = array(
                "2"=>"参数错误",
                "-6"=>"身份验证失败",
                "-7"=>"文件或目录无权访问",
                "-9"=>"文件或目录不存在",
                "6"=>"不允许接入用户数据",
                "111"=>"access token 失效",
                "31034"=>"命中接口频控",
                "42000"=>"访问过于频繁",
                "42001"=>"rand校验失败",
                "42999"=>"功能下线",
                "9100"=>"一级封禁",
                "9200"=>"二级封禁",
                "9300"=>"三级封禁",
                "9400"=>"四级封禁",
                "9500"=>"五级封禁"
            );
            foreach ($msg as $k=>$v){
                if($arr['errno']==$k){
                    $arr['zh-CN'] = $v;
                }
            }
            build_err($arr,$die); // 输出增强后的响应信息
        }
    }

    /**
     * 生成目录树
     * @param $param
     * @return false|string
     */
    public static function tree($param){
        $encode_dir = $param['encode_dir'];
        $dir = urldecode($encode_dir);
        $access_token = $param['access_token'];
        $limit = $param['limit'];
        $start = $param['start'];
        $uk = $param['uk'] ?: 0;
        $db = $param['db'];
        $baseDb = $param['baseDb'] ?? 0;
        $tree_db_limit = 30000;  //限制3W条
        //使用数据库
        if($db==1 && $uk){
            $dir = $dir=="/" ? "/" : $dir."/";
            //sqlite查询sql
            if($baseDb){
                $list = DB::sqlite(BASE_ROOT."/temp/".$baseDb)->sqliteSql("SELECT `id`,`category`,`isdir`,`fid` 'fs_id',`local_mtime` 'local_ctime',`local_mtime`,(`parent_path`||`server_filename`)'path',`server_mtime` 'server_ctime',`server_mtime`,`server_filename`,`file_size` 'size','$uk' as 'uk' FROM cache_file where parent_path like :dir limit $tree_db_limit",array('dir'=>$dir."%"))->selectArrList();
            }
            //mysql查询
            else{
                $list =  DB::table("bp3_cache_file","select `id`,`category`,`isdir`,`fs_id`,`local_mtime` 'local_ctime',`local_mtime`,CONCAT(`parent_path`,`server_filename`)'path',`server_mtime` 'server_ctime',`server_mtime`,`server_filename`,`size`,`uk` from @table where uk=:uk and parent_path like :dir limit $tree_db_limit",array('dir'=>$dir.'%','uk'=>$uk))->selectArrList();
            }
            $data['list']=$list;
            return $data;
        }
        //api请求
        else{
            $url = "http://pan.baidu.com/rest/2.0/xpan/multimedia?method=listall&path=$encode_dir&access_token=$access_token&order=name&recursion=1&limit=$limit&start=$start";
            $result = easy_curl($url);
            $data = self::m_decode(array('str'=>$result));
            //把数据存入db
            self::asyncDb(array('data'=>$data,'uk'=>$uk));
            //返回data
            return $data;
        }

    }

    /**
     * 自动更新最新文件（增量）
     */
    public static function m_file_latest($param){
        global $config;
        if(!$config){
            build_err("没有配置文件");
        }
        $u = $param['u'] ?? 1;
        if($u!=1){
            $uk = $u;
            $access_token = $config['disks'][$uk]['access_token'];
        }else{
            $uk = $config['basic']['uk'];
            $access_token = $config['identify']['access_token'];
        }
        $useDb = $config['useDb'] ?? 0;
        if(!$useDb){
            return array("未开启数据库");  //没开启数据库
        }
        $asyncConfig = get_config("asyncDb");  //同步配置文件
        $lastNew = $asyncConfig['lastNew_'.$uk.'_time'] ?? time()-86400*7;  //首次抓取，7天前
        $lastPage = $asyncConfig['lastNew_'.$uk.'_page'] ?? 1;  //开始页数
        //大于30分钟，才会重新抓取
        $time = time()-60*30;
        //起始目录（个别账户，无法以/获取列表，这里给出一个参数空缺，可自定义设置）
        $dir = $param['dir'] ?? "/";
        $dir_enc = urlencode($dir);
        if($time>$lastNew){
            $limit = 10000;  //最大1w条
            $start = $limit*($lastPage-1);
            $url = "http://pan.baidu.com/rest/2.0/xpan/multimedia?method=listall&path=$dir_enc&access_token=$access_token&order=time&desc=1&recursion=1&mtime=$lastNew&start=$start&limit=$limit";
            $result = easy_curl($url);
            $data = json_decode($result,true);
            if($data['has_more']){
                $lastPage ++;
            }else{
                $lastPage = 1;
                $lastNew = time();  //如果是新的，则保存页数
            }
            $asyncConfig['lastNew_'.$uk.'_page'] = $lastPage;  //保存页数
            $asyncConfig['lastNew_'.$uk.'_time'] = $lastNew; //下次开始时间
            save_config("asyncDb",$asyncConfig);
            //把数据存入db
            self::asyncDb(array('data'=>$data,'uk'=>$uk));
        }
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 2;
        
        //返回最后两个id，因为新增文件肯定为最后，改文件名忽略不计，千万数据量时间排序会非常卡，id倒序则很快。
        return DB::table("bp3_cache_file","select `id`,`category`,`isdir`,`fs_id`,`local_mtime` 'local_ctime',`local_mtime`,CONCAT(`parent_path`,`server_filename`)'path',`server_mtime` 'server_ctime',`server_mtime`,`server_filename`,`size`,`uk` from @table where isdir=0 and uk=:uk order by id desc limit 2",array('uk'=>$uk))->selectArrList();
    }



    /**
     * 获取文件列表
     * @param $param
     * @return array[]|mixed
     */
    public static function m_file_list($param){
        $dir = $param['dir'];
        $access_token = $param['access_token'];
        $uk = $param['uk'] ?? 0;
        $db = $param['db'] ?? 0;
        $baseDb = $param['baseDb'] ?? 0;
        $unUseDb = $param['unUseDb'] ?? 0;
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 20;
        //使用数据库
        if($db==1 && $uk && !$unUseDb){
            $dir = $dir=="/" ? "/" : $dir."/";
            //sqlite查询sql
            if($baseDb){
                $list = DB::sqlite(BASE_ROOT."/temp/".$baseDb)->sqliteSql("SELECT `id`,`category`,`isdir`,`fid` 'fs_id',`local_mtime` 'local_ctime',`local_mtime`,(`parent_path`||`server_filename`)'path',`server_mtime` 'server_ctime',`server_mtime`,`server_filename`,`file_size` 'size','$uk' as 'uk' FROM cache_file where parent_path=:dir order by `isdir` desc, server_filename asc",array('dir'=>$dir))->selectArrList();
                return array("pageInfo"=>array("page"=>1,"pageSize"=>count($list),"totalCount"=>count($list),"totalPage"=>1),"list"=>$list);
            }
            //mysql查询
            else{
                $list = DB::table("bp3_cache_file","select `id`,`category`,`isdir`,`fs_id`,`local_mtime` 'local_ctime',`local_mtime`,CONCAT(`parent_path`,`server_filename`)'path',`server_mtime` 'server_ctime',`server_mtime`,`server_filename`,`size`,`uk` from @table where parent_path=:dir and uk=:uk order by `isdir` desc,server_filename asc",array('dir'=>$dir,'uk'=>$uk))->selectArrList();
                return array("pageInfo"=>array("page"=>1,"pageSize"=>count($list),"totalCount"=>count($list),"totalPage"=>1),"list"=>$list);
            }
        }
        //api请求
        else{
            $enc_dir = urlencode($dir);
            $url = "https://pan.baidu.com/rest/2.0/xpan/file?method=list&dir=$enc_dir&order=name&start=0&limit=10000&web=1&folder=0&access_token=$access_token&desc=0";
            $result = easy_curl($url);
            ob_start();
            $data = self::m_decode(array('str'=>$result,'die'=>false));
            $msg = ob_get_contents();
            ob_end_clean();
            if($msg){
                return array('list'=>array());
            }else{
                //把数据存入db
                self::asyncDb(array('data'=>$data,'uk'=>$uk));
                //返回data
                return $data;
            }
        }
    }

    //把数据存入数据库
    public static function asyncDb($param){
        api_login();
        global $config;
        $uk = $param['uk'];
        if(!$config || !$config['useDb'] || !$uk){
            return;
        }
        $data = $param['data'] ?: array();
        $data = $data['list'] ?: array();
        $chunks = array_chunk($data, 1000);
        foreach ($chunks as $data_i){
            //每小块1000条，一次执行
            $sql = "INSERT INTO `bp3_cache_file` (`category`, `isdir`, `fs_id`, `local_ctime`, `local_mtime`, `server_ctime`, `server_mtime`, `server_filename`, `size`, `uk`,`parent_path`) VALUES";
            foreach ($data_i as $item){
                if(isset($item['path']) && $item['path']){
                    $item['parent_path'] = substr($item['path'],0,strrpos($item['path'],"/")+1);
                }
                //生成数据
                $item['uk'] = $uk;
                //插入或更新数据
                $sql .= "(";
                $sql .= $item['category'].",";
                $sql .= $item['isdir'].",";
                $sql .= $item['fs_id'].",";
                $sql .= $item['local_ctime'].",";
                $sql .= $item['local_mtime'].",";
                $sql .= $item['server_ctime'].",";
                $sql .= $item['server_mtime'].",";
                $sql .= "'".addslashes($item['server_filename'])."',";
                $sql .= $item['size'].",";
                $sql .= $item['uk'].",";
                $sql .= "'".addslashes($item['parent_path'])."'";
                $sql .= "),";
            }
            $sql = substr($sql, 0, -1);
            $sql .= " on duplicate key update `parent_path`=values(`parent_path`),`server_filename`=values(`server_filename`),`local_mtime`=values(`local_mtime`),`server_mtime`=values(`server_mtime`)";
            DB::sql($sql)->update();
        }
        return true;
    }

    /**
     * 从 sqlite 数据，同步数据到 mysql（全量）
     * @param $param
     */
    public static function async($param){
        $uk = $param['uk'];
        //数据库名称（该文件在 /temp/ 目录下）
        $db = BASE_ROOT."/temp/".$param['db'];
        if(empty($param['db']) || !file_exists($db)){
            return array("errno"=>1,'errno'=>500,'errmsg'=>"数据库文件不存在");
        }
        $page = (int)$param['page'] ?: 1;
        $pageSize = (int)$param['pageSize'] ?: 50;
        //是否存在sqlite3环境？
        if (!class_exists('SQLite3')) {
            return array("errno"=>1,"state"=>"500","errmsg"=>"不存在sqlite3环境");
        }
        //检测sqlite3版本是否太低？
        $ver = SQLite3::version();
        $min_sqlite3_v = 3028000;
        if($ver['versionNumber']<$min_sqlite3_v){
            return array("errno"=>1,"state"=>"500","errmsg"=>"服务器sqlite3版本太低【当前版本{$ver['versionNumber']}，小于最低要求{$min_sqlite3_v}】，参考：<a target='_blank' href='https://www.5252.online/archives/925.htm'>https://www.5252.online/archives/925.htm</a>");
        }
        //从sqlite中获取数据
        $max_id = (int)DB::sqlite($db)->sqliteSql("select max(id) from cache_file")->selectOne();
        $start = ($page-1)*$pageSize;
        $end = $start + $pageSize;
        $totalPage = ceil($max_id / $pageSize);
        $list = DB::sqlite($db)->sqliteSql("SELECT `id`,`category`,`isdir`,`fid` 'fs_id',`local_mtime` 'local_ctime',`local_mtime`,`parent_path`,`server_mtime` 'server_ctime',`server_mtime`,`server_filename`,`file_size` 'size','$uk' as 'uk' FROM cache_file where `id`>$start and `id`<=$end")->selectArrList();
        $data['list'] = $list;
        $data['pageInfo']['page'] = $page;
        $data['pageInfo']['pageSize'] = $pageSize;
        $data['pageInfo']['totalCount'] = $max_id;
        $data['pageInfo']['totalPage'] = $totalPage;
        $data['pageInfo']['size'] = count($list);  //当前多少条
        if($data){
            if($data['pageInfo']['size']>0){
                $res = self::asyncDb(array('uk'=>$uk,'data'=>$data));
                if(!$res){
                    return array("errno"=>1,"state"=>"500","errmsg"=>"无法导入mysql，请确认已经打开数据库配置并初始化表");
                }else{
                     return array("errno"=>0,"errmsg"=>"数据导入成功，导入数量：{$pageSize}条","pageInfo"=>$data['pageInfo']);
                }
            }
            else{
                return array("errno"=>0,"errmsg"=>"数据导入成功，导入数量：{$pageSize}条","pageInfo"=>$data['pageInfo']);
            }
        }
        else{
            return array("errno"=>1,"state"=>"500","errmsg"=>"数据库没有查到数据");
        }
    }


    /**
     * 搜索文件
     * @param $param
     * @return mixed
     */
    public static function m_file_search($param){
        $pre_dir = $param['pre_dir'];
        $page = $param['page'] ?? 1;
        $pageSize = $param['pageSize'] ?? 20;
        $key = $param['key'];
        $access_token = $param['access_token'];
        $uk = $param['uk'] ?? 0;
        $db = $param['db'] ?? 0;
        $baseDb = $param['baseDb'] ?? 0;
        $unUseDb = $param['unUseDb'] ?? 0;
        $recursion = $param['recursion'] ?? 1;
        $fileType = $param['fileType'] ?? 1;  //1.全部，2.文件夹，3.文件
        $orderType = $param['orderType'] ?? 1;  //1.默认，2.文件名升序，3.更新时间倒序
        global $config;
        $match = $config['dbMatch'] ?? 0;  //全文检索模式
        $allSearch = $config['allSearch'] ?? 0;  //混合搜索
        //使用数据库
        if($db==1 && $uk && !$unUseDb){
            $where = "";
            if($fileType==2){
                $where .= " and `isdir`=1";
            }
            elseif($fileType==3){
                $where .= " and `isdir`=0";
            }
            $orderby = "";
            if($orderType==2){
                $orderby .= " order by `server_filename` asc";
            }
            elseif($orderType==3){
                $orderby .= " order by `server_mtime` desc";
            }
            // 搜索条件
            $dbParam = array();
            $dbParam['uk'] = $uk;
            if($recursion){
                //非根目录
                if($pre_dir!="/"){
                    $where .= " and `parent_path` like :dir";
                    $dbParam['dir'] = $pre_dir.'/%';
                }
            }
            //非递归
            else{
                //直接等于
                $where .= " and `parent_path`= :dir";
                $dbParam['dir'] = $pre_dir . "/";
            }
            if($baseDb){
                $dbParam['key'] = '%'.$key.'%';
            }else{
                //全文检索
                if($match){
                    $dbParam['key'] = "+'".$key."'*";
                }
                //like模式
                else{
                    $dbParam['key'] = '%'.$key.'%';
                }
            }
            
            //sqlite查询sql
            if($baseDb){
                //递归
                return DB::sqlite(BASE_ROOT."/temp/".$baseDb)->sqliteSql("SELECT `id`,`category`,`isdir`,`fid` 'fs_id',`local_mtime` 'local_ctime',`local_mtime`,(`parent_path`||`server_filename`)'path',`server_mtime` 'server_ctime',`server_mtime`,`server_filename`,`file_size` 'size','$uk' as 'uk' FROM cache_file where server_filename like :key $where $orderby",$dbParam)->selectPage($page,$pageSize);
            }
            //mysql查询
            else{
                if(empty($allSearch)){
                    $where .= " and uk=:uk";
                }
                if($match){
                    $pageSize = 1000;
                    $list = DB::table("bp3_cache_file","select `id`,`category`,`isdir`,`fs_id`,`local_mtime` 'local_ctime',`local_mtime`,CONCAT(`parent_path`,`server_filename`)'path',`server_mtime` 'server_ctime',`server_mtime`,`server_filename`,`size`,`uk` from @table where 1=1 $where and match(server_filename) AGAINST(:key in boolean mode) $orderby limit $pageSize",$dbParam)->selectArrList();
                    $data = array("list"=>$list,"pageInfo"=>array("page"=>$page,"pageSize"=>$pageSize,"totalPage"=>1,"totalCount"=>count($list)));
                }else{
                    $data = DB::table("bp3_cache_file","select `id`,`category`,`isdir`,`fs_id`,`local_mtime` 'local_ctime',`local_mtime`,CONCAT(`parent_path`,`server_filename`)'path',`server_mtime` 'server_ctime',`server_mtime`,`server_filename`,`size`,`uk` from @table where 1=1 $where and server_filename like :key $orderby",$dbParam)->selectPage($page,$pageSize);
                }
                return $data;
            }
        }
        //api请求
        else{
            $enc_dir = urlencode($pre_dir);
            $url = "http://pan.baidu.com/rest/2.0/xpan/file?dir=$enc_dir&access_token=$access_token&web=1&recursion=$recursion&page=$page&num=20&method=search&key=$key";
            $result = easy_curl($url);
            ob_start();
            $data = self::m_decode(array('str'=>$result,'die'=>false));
            $msg = ob_get_contents();
            ob_end_clean();
            if($msg){
                return array('list'=>array());
            }else{
                //把数据存入db
                self::asyncDb(array('data'=>$data,'uk'=>$uk));
                //返回data
                return $data;
            }
        }
    }

    /**
     * 查询文件信息
     * @param $param
     * @return mixed
     */
    public static function m_file_info($param){
        $access_token = $param['access_token'];
        $fsid = $param['fsid'];
        $url = "http://pan.baidu.com/rest/2.0/xpan/multimedia?method=filemetas&access_token=$access_token&fsids=[$fsid]&thumb=1&dlink=1&extra=1";
        $result =  easy_curl($url);

        return self::m_decode(array('str'=>$result));
    }

    /** 3
     * 使用code换取identify
     * @param $param
     * @return mixed
     */
    public static function m_callback($param){

        $code = $param['code'];
        $appKey = $param['appKey'];
        $secret = $param['secret'];
        $redirect = $param['redirect'];
        $state = $param['state'];
        $grant_url = $param['grant_url'];
        $refresh_url = $param['refresh_url'];
        $url = "https://openapi.baidu.com/oauth/2.0/token?grant_type=authorization_code&code=$code&client_id=$appKey&client_secret=$secret&redirect_uri=$redirect&state=$state";
        $result = easy_curl($url);
        $identify = self::m_decode(array('str'=>$result));
        $identify['conn_time'] = NOW; // 添加时间
        $identify['grant_url'] = $grant_url; //授权地址
        $identify['refresh_url'] = $refresh_url; //刷新地址

        return $identify;
    }

    /**
     * 查询文件重定向后的下载地址
     * @param $param
     * @return mixed|string 重定向后的下载地址
     */
    public static function m_redirect_dlink($param){
        $dlink = $param['dlink'];
        //取得headers
        $get_headers = easy_curl_head($dlink);
        //取得最后一个location
        $locations = $get_headers['Location'];
        if(is_array($locations)){
            return $locations[0];
        }else{
            return $locations;
        }
    }

    /**
     * 刷新identify
     * @param $param
     * @return mixed
     */
    public static function m_refresh($param){
        $refresh_token = $param['refresh_token'];
        $app_key = $param['app_key'];
        $secret = $param['secret'];
        $grant_url = $param['grant_url'];
        $refresh_url = $param['refresh_url'];
        $url = "https://openapi.baidu.com/oauth/2.0/token?grant_type=refresh_token&refresh_token=$refresh_token&client_id=$app_key&client_secret=$secret";
        $result =  easy_curl($url);
        $identify = json_decode($result,true);
        global $time;
        $identify['conn_time'] = $time; // 添加时间
        $identify['grant_url'] = $grant_url; //授权地址
        $identify['refresh_url'] = $refresh_url; //刷新地址

        return json_encode($identify);
    }

    /**
     * 获取token，并且尝试自动刷新
     * @param $param
     * @return mixed token
     */
    public static function m_token_refresh($param){
        api_login();
        $config = $param['config'];
        $force = $param['force'] ?? false;
        $grant = $param['grant'];
        $grant2 = $param['grant2'];
        $grant_refresh = $param['grant_refresh'];
        $grant2_refresh = $param['grant2_refresh'];
        global $time;
        if(isset($config['identify']) && isset($config['identify']['access_token'])){
            $pass_time = $time - $config['identify']['conn_time'];
            $express_time = $config['identify']['expires_in']-$pass_time;
            if($express_time<1728000 || $force){ //有效期小于20天，自动刷新token，或指定刷新，则自动刷新token

                $refresh_url = $config['identify']['refresh_url'];
                $refresh_token = $config['identify']['refresh_token'];
                // 使用免app
                if($refresh_url==$grant_refresh){
                    $appKey = $config['connect']['app_id'];
                    $secret = $config['connect']['secret_key'];
                    $param = self::m_refresh(array('refresh_token'=>$refresh_token,'app_key'=>$appKey,'secret'=>$secret,'grant_url'=>$grant,'refresh_url'=>$refresh_url));
                }
                // 使用内置app
                elseif($refresh_url==$grant2_refresh){
                    $appKey = $config['inner']['app_id'];
                    $secret = $config['inner']['secret_key'];
                    $param = self::m_refresh(array('refresh_token'=>$refresh_token,'app_key'=>$appKey,'secret'=>$secret,'grant_url'=>$grant2,'refresh_url'=>$refresh_url));
                }
                // 自定义url（程序外，则请求url）
                else{
                    $urlKey = "?";
                    if(strpos($refresh_url,$urlKey)){
                        $urlKey = "&";
                    }
                    $url = $refresh_url. $urlKey. "refresh_token=$refresh_token";
                    $param = easy_curl($url);
                }
                $identify = json_decode($param,true);
                if(isset($identify['access_token']) && $identify['access_token']){  // 刷新token时，正常应该会得到access_token，得不到则不正常
                    $config['identify'] = $identify; // 更新身份信息
                    save_config(null,$config);  // 保存
                }else{
                    // 消息处理
                    // 如果开启了邮件提醒
                    if(is_array($config['mail']) && $config['mail']['refresh']=="1"){
                        $file_log = TEMP_DIR."/refresh.log";
                        $err_text = json_encode($identify);
                        if(date('Y-m-d',filemtime($file_log)) != date('Y-m-d')){  // 文件不是今日修改
                            // 今日首次发送
                            $base_url = BASE_URL;
                            $is_email = send_mail("token刷新失败通知","您的站点：<b>{$config['site']['title']}</a>，token自动刷新失效，请及时处理并排查原因，站点地址：<a href='$base_url'>$base_url</a>，如不需要此提醒，请在后台设置中关闭。<pre><code>$err_text</code></pre>");
                            if($is_email){
                                file_put_contents($file_log,"邮件发送成功：".date("Y-m-d h:i:s"));
                            }else{
                                file_put_contents($file_log,"邮件发送失败：".date("Y-m-d h:i:s").PHP_EOL.$err_text);
                            }
                        }
                    }
                    if(empty($identify)){
                        $identify = array("error_code"=>1,"msg"=>"token刷新失败，请重新授权");
                    }
                    // 返回错误
                    build_err($identify,false);  // 输出报错，但不终止脚本
                }
            }
            return $config['identify']['access_token'];
        }else{
            return "";
        }
    }

    /**
     * 创建文件或文件夹
     * @param $param
     * @return false|string
     */
    public static function m_create($param){
        $isdir = $param['isdir'];
        $access_token = $param['access_token'];
        $ser_dir = $param['ser_dir'];   //起始文件夹【远程】
        $ser_name = $param['ser_name'];  //文件名【远程】
        $local_path = $param['local_path'] ?? '';
        $uploadid = $param['uploadid'] ?? '';
        $block_list = $param['block_list'] ?? '';
        $url = "http://pan.baidu.com/rest/2.0/xpan/file?method=create&access_token=$access_token";
        $path = urlencode($ser_dir."/".$ser_name); //整个进行url编码，合并成为新的路径
        // 创建文件夹
        if($isdir){
            $result = easy_curl($url,array("postData"=>'path='.$path.'&size=0&isdir=1&autoinit=1&rtype=1'));
            return self::m_decode(array('str'=>$result));
        }
        // 创建文件
        else{
            $size = filesize($local_path);
            $result = easy_curl($url,array('postData'=>'path='.$path.'&size='.$size.'&isdir=0&autoinit=1&rtype=1&uploadid='.$uploadid."&block_list=".$block_list));
            return self::m_decode(array('str'=>$result));
        }
    }
}