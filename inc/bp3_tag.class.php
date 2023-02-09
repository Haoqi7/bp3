<?php

require('smarty/Smarty.class.php');

class bp3_tag extends Smarty {

    function __construct()
    {
        parent::__construct();

        // 标签缓存目录，为 temp 根目录 / content_path / tag 目录 ， 如果目录不存在，自动创建多级目录
        define("BP3_TAG_TEMP",TEMP_DIR."/tag/".CONTENT_PATH);
        if(!file_exists(BP3_TAG_TEMP)){
            mkdir(BP3_TAG_TEMP,0777,true);
        }

        $this->left_delimiter = "{#";  // 起始标记
        $this->right_delimiter = "#}"; // 终止标记
        $this->caching = Smarty::CACHING_LIFETIME_CURRENT;

        define("BP3_TEMPLATE_ROOT_DEFAULT",BP3_ROOT.'/themes/default/');  // 默认主题模板根目录
        define("BP3_TEMPLATE_DIR_DEFAULT",BP3_ROOT.'/themes/default'.CONTENT_PATH.'/');  // 默认主题模板目录
        define("BP3_TEMPLATE_ROOT",BP3_ROOT.'/themes/'.THEME.'/');  // 当前主题模板根目录
        define("BP3_TEMPLATE_DIR",BP3_ROOT.'/themes/'.THEME.''.CONTENT_PATH.'/');  // 当前主题模板目录
        $this->setTemplateDir(BP3_TEMPLATE_DIR);
        $this->setCompileDir(BP3_TAG_TEMP.'/tag_templates_c/');
        $this->setConfigDir(BP3_TAG_TEMP.'/tag_configs/');
        $this->setCacheDir(BP3_TAG_TEMP.'/tag_cache/');

    }

}

