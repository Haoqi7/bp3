<?php
require_once("./functions.php");

$config = $base;

$username = $_POST['username'] ?? null;
$password = $_POST['password'] ?? null;
$app = $_POST['app'] ?? null;
$secret = $_POST['secret'] ?? null;
$redirect = get_file_url("/grant/callback.php");
$grant_url = get_file_url("/grant/");

if (!empty($username) && !$install) {

    $init = true;
    $config['init'] = $init;
    $config['user']['name'] = $username;
    $config['user']['pwd'] = $password;
    $config['connect']['app_id'] = $app;
    $config['connect']['secret_key'] = $secret;
    $config['connect']['redirect_uri'] = $redirect;
    $config['identify']['grant_url'] = $grant_url;

    save_config();

    js_alert('提交成功！正在前往登录页面...');
    js_location($login_url);
} else if (!empty($username)) {
    js_alert('你已经配置过了，如果需要重新配置，请把config.php文件删掉');
    js_location($login_url);
}
$bp3_tag->assign("redirect",$redirect);
display();