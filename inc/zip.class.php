<?php


/**
 * zip扩展类（兼容win，注意必须使用 / 而不能是 DIRECTORY_SEPARATOR，否则 win 打包后 linux 无法识别)
 * --------   对一个文件/目录打包，例如：  ---------------
 * // 对程序根目录打包：
 *   ExtendedZip::zipTree(get_base_root(), 'archive.zip', ZipArchive::CREATE);
 * // 对上级目录打包
 *   ExtendedZip::zipTree('../', 'archive.zip', ZipArchive::CREATE);
 */
class ExtendedZip extends ZipArchive {

    // Member function to add a whole file system subtree to the archive
    public function addTree($dirname, $local_name = '') {
        if ($local_name)
            $this->addEmptyDir($local_name);
        $this->_addTree($dirname, $local_name);
    }

    // 默认排除项
    protected $skips = ['.user.ini', '.git', '.idea', '.gitattributes','.gitignore', 'temp','test.php'];

    // 对某些文件名排除
    protected function addSkip(array $skips=array()){
        $def_skips = $this->skips;
        if(!empty($skips)){
            foreach ($skips as $k=>$v){
                $def_skips[] = $skips[$k];  // 把设置的每一项追加
            }
        }
        $this->skips = $def_skips;
    }

    // Internal function, to recurse
    protected function _addTree($dirname, $local_name) {
        $dir = opendir($dirname);
        while ($filename = readdir($dir)) {
            // Discard . and ..
            if ($filename == '.' || $filename == '..')
                continue;

            // zip打包排除项
            $break_falg = false;
            $skips = $this->skips;
            foreach ($skips as $k=>$v){
                if($filename == $skips[$k]){
                    $break_falg = true;
                    break;
                }
            }
            if($break_falg){
                continue;
            }

            // Proceed according to type
            $path = $dirname . '/' . $filename;
            $local_path = $local_name ? ($local_name . '/' . $filename) : $filename;
            if (is_dir($path)) {
                // Directory: add & recurse
                $this->addEmptyDir($local_path);
                $this->_addTree($path, $local_path);
            }
            else if (is_file($path)) {
                // File: just add
                $this->addFile($path, $local_path);
            }
        }
        closedir($dir);
    }

    // Helper function ，其中第一个参数为要打包的目录，第二个参数为压缩包名，第三个文件是文件的锁定标记，第四是子目录，第五指定忽略文件列表
    public static function zipTree($dirname, $zipFilename, $flags = 0, $local_name = '',array $skips=array()) {
        $zip = new self();
        $zip->open($zipFilename, $flags);
        $zip->addSkip($skips);
        $zip->addTree($dirname, $local_name);
        $zip->close();
    }
}
