<?php

require_once("../functions.php");

force_login();

$param = force_get_param("param");

$identify = json_decode($param,true);

$access_token = $identify['access_token'];

$basic = api(array('method'=>'m_basic','module'=>'baidu','access_token'=>$access_token));

$this_uk = $basic['uk'];

if($this_uk == $uk){  // 当前主账户，不合法

    $config['identify'] = $identify;

    pushError("绑定的账户不可以是主账户");
}
// 非主账户，存储在disks中
else{

    $mix = array_merge($identify,$basic);

    $config['disks'][$this_uk] = $mix;

}
save_config();
redirect(BASE_URL."/admin/disks.php");



