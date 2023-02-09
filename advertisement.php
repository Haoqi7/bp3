<?php

    require('./functions.php');
    
    
    $bp3_tag->assign("adv",$config['dn_adv'] ?? $base['dn_adv']);
    
    display();