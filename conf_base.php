<?php return array (
  'version'=>"v1.3.8",
  'user' => 
  array (
    'lock' => '3',
    'chance' => '3',
    'name' => 'bp3',
    'pwd' => 'bp3',
  ),
  'site' => 
  array (
    'title' => 'bp3',
    'subtitle' => '免费、开源的网盘程序',
    'blog' => 'https://www.52dixiaowo.com',
    'github' => 'https://github.com/zhufenghua1998/bp3',
    'description' => 'bp3是一款开源的百度网盘开发者接口程序，解锁更多炫酷的技能。',
    'keywords' => '开源,bp3,网盘,百度网盘,直链,百度网盘直链,百度网盘目录树,文件列表,文件搜索,文件下载',
  ),
  'control' => 
  array (
    'pre_dir' => '',
    'close_dlink' => 0,
    'close_dload' => 1,
    'open_grant' => 1,
    'open_grant2' => 1,
    'open_session' => 1,
    'grant_type' =>'url',
    'update_type' => 'cn',
    'update_url' => 'https://gitee.com/zhufenghua1998/bp3/repository/archive/main.zip',
    'dn_limit' => 0,
    'dn_speed' => 10240,
    'theme' => 'default',
    'hideFile' => '',
    'hideDir' => '',
    'pre_dir_show' =>''
  ),
  'inner'=>array(
    'app_id' => 'NtcmMLFqq4Vf0IKBlVIDFGXAqjuYpvWN',
    'secret_key' => 'K0Y8zrTmHX98APHRlSqSAQl5N8rtS2kz',
    'redirect_uri' => 'oob',
   ),
  'baidu' => array (
    'listPre' => 'https://pan.baidu.com/disk/main?from=homeFlow#/index?category=all&path=',
    'searchPre' => 'https://pan.baidu.com/disk/main?from=homeFlow#/index?category=all&search=',
  ),
  'useDb' => 0,
  'useDbPre' => 0,
  'mail' => 
  array (
    'user' => '',
    'pass' => '',
    'server' => '',
    'port' => '',
    'receiver' => '',
    'refresh' => '',
  ),
  'dn_adv'=>'你没有下载权限。【这是自定义示例页面，若有疑问请添加QQ交流群：1150064636】',
  'barnerHeight'=>150,
  'siteNotice' => '这是一个默认公告，如不需要请在后台修改。'
);