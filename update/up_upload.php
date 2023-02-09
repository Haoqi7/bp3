<?php

    require_once("../functions.php");
    
    force_login();
    
    
    // 保存上传的文件
    $temp_uri = TEMP_DIR.DIRECTORY_SEPARATOR."bp3-main.zip";
    move_uploaded_file($_FILES["file"]["tmp_name"],$temp_uri);

    // 调用自动更新代码
    $type = os_type();

    require("./up_core_$type.php");

