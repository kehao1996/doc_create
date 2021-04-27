<?php

if(!method_exists('searchDir')){
    function searchDir($path, &$files)
    {

        if (is_dir($path)) {
            $opendir = opendir($path); //打开一个路径
            while ($file = readdir($opendir)) { //读取路径
                if ($file != '.' && $file != '..') {
                    searchDir($path . '/' . $file, $files);
                }
            }
            closedir($opendir);
        }
        if (!is_dir($path)) {
            $files[] = $path;
        }
    }
}

//得到目录名
if(!method_exists('getDir')){
    function getDir($dir)
    {
        $files = array();
        searchDir($dir, $files);
        return $files;
    }
}
