<?php

include_once("../inc/fun_core.php");

//返回原生sql五种方式
//$res = DB::getInstance()->setSql("select * from @user where utype=:utype and uname=:uname" , [1, '张伟'],'user')->getSql(); // 实例生成
//$res = DB::sql("select * from @user where utype=:utype and uname=:uname" , [1, '张伟'],'user')->getSql(); //静态生成
//$res = DB::mockSql("select * from @user where utype=:utype and uname=:uname" , [1, '张伟'],'user');  // 只返回sql
//$res = DB::table('user',"select * from @user where utype=:utype and uname=:uname",[1, '张伟'])->getSql(); //习惯先写表名
//$res = DB::getInstance()->setTable('user',"select * from @user where utype=:utype and uname=:uname",[1, '张伟'])->getSql(); //实例生成，另一种写法
//echo($res);


//几种常见的查询
//$res = DB::getInstance()->setSql("select * from user")->selectOne();
//$res = DB::getInstance()->setSql("select * from user")->selectArr();
//$res = DB::getInstance()->setSql("select * from user")->selectArrList();
//$res = DB::getInstance()->setSql("select * from user")->selectPage(2,3);
//$res = DB::getInstance()->setSql("select * from user")->selectPageList(1,3);
//$res = DB::getInstance()->setSql("select * from user")->selectPageInfo(1,3);
//$res = DB::getInstance()->setSql("select * from user")->count();
//var_dump($res);
//
//增加、更新和删除
//$res = DB::getInstance()->setSql("update user set upwd=1 where uno=3")->update();
//$res = DB::getInstance()->setSql("insert into user(uname,upwd) values('test','123456')")->lastId();
//$res = DB::getInstance()->setSql("select * from user")->insert();
//$res = DB::getInstance()->setSql("select * from user")->delete();
//$res = DB::getInstance()->setSql("CREATE TABLE `bp3_cache_file`  (
//  `id` bigint(20) UNSIGNED NOT NULL AUTO_INCREMENT COMMENT '文件ID',
//  `category` smallint(2) NOT NULL COMMENT '文件类型',
//  `isdir` smallint(1) NOT NULL COMMENT '是否文件夹',
//  `fs_id` bigint(20) NOT NULL COMMENT '百度fsid',
//  `local_ctime` int(10) NOT NULL COMMENT '客户端创建时间',
//  `local_mtime` int(10) NOT NULL COMMENT '客户端修改时间',
//  `md5` varchar(36) NOT NULL COMMENT '文件md5',
//  `path` varchar(1024) NOT NULL COMMENT '文件目录',
//  `server_ctime` int(10) NOT NULL COMMENT '服务器创建时间',
//  `server_mtime` int(10) NOT NULL COMMENT '服务器修改时间',
//  `server_filename` varchar(255) NOT NULL COMMENT '文件名',
//  `size` bigint(20) NOT NULL COMMENT '文件大小',
//  `uk` int(10) NOT NULL COMMENT '用户百度uk',
//  PRIMARY KEY (`id`),
//  INDEX `uk`(`uk`),
//  INDEX `server_mtime`(`server_mtime`)
//) ENGINE = MyISAM CHARACTER SET = utf8mb4 COLLATE = utf8mb4_general_ci COMMENT = '文件数据表';")->create();
//$parent_path = strrpos("/网络安全","/");
//$parent_path = substr("/apps/share/汇编",0,11);



//事务管理
//DB::begin();  //开启事务
//var_dump(DB::isBegin()); ;  //当前是否开启事务
//DB::commit();  //提交事务
//DB::rollback();  //事务回滚

//表相关
$sqliteRes = DB::sqlite(BP3_ROOT."/temp/BaiduYunRecentV0.db")->sqliteSql("select * from version")->selectArr();
$res = DB::table('option')->exist();//表是否存在
//$res = DB::table('bp3_cache_file')->drop();//删除表
//$res = DB::table('question')->tableSize();//表大小
//$res = DB::table()->selectAll();//查询表所有数据
//$res = DB::table()->selectById();//根据ID查询一条数据
//$res = DB::table()->deleteById();//根据ID删除一条数据
//$res = DB::table()->showCreate();//查询表的结构
//$res = DB::table()->showColumns();//查询表的字段

//数据库相关
//$res = DB::db()->dbVersion();//获取数据库版本
//$res = DB::db()->dbSize();//获取数据库大小
//$res = DB::db()->showTables(true);//获取数据库的所有表
var_dump($sqliteRes);
var_dump($res);


