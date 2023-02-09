<?php
    // 注销
    require_once("../functions.php");
    // session_destroy();
    set_session($user,null);
    set_cookie($user,'',time()-1);
    redirect($base_url);
