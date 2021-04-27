<?php
namespace Doc;
require_once 'conf.php';

class Doc {

    protected $name;
    protected $target_dir;
    protected $doc_idr;
    protected $return_status;  //返回状态1 返回base64字符 2保存文件到指定目录

    static $con_dir; //控制器
    static $file_dir;//文件路径
    static $methods; //所有类的方法
    static $refs; //ref实例化类

    static $class_format = [
        'name' => 1,
        'child' => 'method_format'
    ];
    static $method_format = [
        'method' => 1,
        'name' => 1,
        'param' => ['type','param','desc','is_must'],
        'return' => ['type','param','desc'],
        'desc' => 1,
        'url' => 1
    ];


    static $analysis_fields = [
        'child' => [],
        'className' => [
            'name' => '类名',
        ],
        'method' => [
            'name' => '方法',
        ],
        'author' => [
            'name' => '作者'
        ],
        'param' => [
            'name' => '参数'
        ],
        'link' => [
            'name' => '链接'
        ],
        'return' => [
            'name' => '返回结果'
        ],
        'custom' => [
            'name' => '自定义'
        ],
        'name' => [
            'name' => '方法名'
        ],
        'desc' => [
            'name' => '接口描述'
        ],
        'url' => [
            'name' => '接口地址'
        ]
    ]; //可解析领域


    public function __construct($name = '',$target_dir = '',$doc_idr = '',$return_status = 1)
    {
        $this->name = $name;
        $this->target_dir = $target_dir;
        $this->doc_idr = $doc_idr;
        $this->return_status = $return_status;
    }


    public function init(){
        if(!in_array($this->return_status,[1,2])) throw new DocException("return_status is not 12");
        if(empty($this->name)) throw new DocException("文件名为空");
        if(empty($this->target_dir)) throw new DocException("目标路径为空");
        if(empty($this->doc_idr)) throw new DocException("文档路径为空");
        $this->getDir();
        $this->getNotes();
        $class_formt = $this->createDoc();
        if($this->return_status == 1){ //返回结果
            return $class_formt;
        }elseif($this->return_status == 2){ //生成文档
        }
    }

    public function close(){
        self::$con_dir = [];
        self::$file_dir = [];
        self::$methods = [];
        self::$refs = [];
    }


    private function getDir(){
        $file_ary = getDir($this->doc_idr);
        foreach($file_ary as $file){
            $path_info = pathinfo($file);
            if($path_info['dirname'] == $this->doc_idr){
                $cont_file_name = 'Index';
            }else{
                $cont_file_name = substr($path_info['dirname'],strrpos($path_info['dirname'] ,'/') + 1);
            }

            if(isset(self::$con_dir[$cont_file_name]) && self::$con_dir[$cont_file_name]['dirname'] != $path_info['dirname']){
                $cont_file_name = $cont_file_name .'_' . time();
            }

            self::$con_dir[$cont_file_name]['dirname'] = $path_info['dirname'];
            self::$con_dir[$cont_file_name]['md5'] = md5($path_info['dirname']);

            $file_url = $path_info['dirname'] . '/' . $path_info['basename'];
            $namespace = '';
            if(is_file($file_url)){
                $fp = fopen($file_url,'r');
                while (!feof($fp)){
                    $content = fgets($fp);
                    if(substr(trim($content),0,9) == 'namespace'){
                        $namespace = trim(trim(trim($content,'namespace')),';') .'\\';
                        break;
                    }

                }
            }

            $path_info['classname'] = $path_info['filename'];
            $path_info['namespace'] = $namespace;
            self::$file_dir[md5($path_info['dirname'])][$path_info['namespace'] . $path_info['classname']] = $path_info;
        }

        $this->loadClass();
    }

    private function loadClass(){
        $con_dir = self::$con_dir;
        foreach($con_dir as $con_v){
            if(isset(self::$file_dir[$con_v['md5']])){
                foreach(self::$file_dir[$con_v['md5']] as $file_k => $file_v){
                    if(is_file($file_v['dirname'] . '/' . $file_v['basename'])){
                        $file_url = $file_v['dirname'] . '/' . $file_v['basename'];
                        require_once($file_url);
                    }else{
                        unset(self::$file_dir[$con_v['md5']]);
                    }
                }
            }
        }

        $this->getMethods();
    }

    private function getMethods(){
        $con_dir = self::$con_dir;
        foreach($con_dir as $con_v){
            if(isset(self::$file_dir[$con_v['md5']])){
                foreach(self::$file_dir[$con_v['md5']] as $file_k => $file_v){

                    $ref = isset(self::$refs[$file_k]) ? self::$refs[$file_k] : null;

                    if(empty($ref)){
                        $ref = new \ReflectionClass($file_k);
                        self::$refs[$file_k] = $ref;
                    }


                    $methods = $ref->getMethods();
                    foreach($methods as $method){
                        if(strtolower($file_k) == strtolower($method->class)){
                            self::$methods[$file_k]['method'][] = $method->name;
                        }
                    }
                }
            }
        }
    }

    //解析类获取注释
    private function getNotes(){
        if(empty(self::$refs)) return $this->returnMsg(0,'ref is empty');
        if(empty(self::$methods)) return $this->returnMsg(0,'methods is empty');

        foreach(self::$refs as $class_name =>  $ref){
            foreach(self::$methods[$class_name]['method'] as $method_k=>$method){
                $notes = $ref->getMethod($method)->getDocComment();
                $notes_data = $this->analysisNotes($notes);
                self::$methods[$class_name]['notes_data'][$method_k] = $notes_data;
            }
        }
    }

    //生产文档
    private function createDoc(){

        foreach(self::$con_dir as $con_k => $con_v){
            if(isset(self::$file_dir[$con_v['md5']])){
                foreach(self::$file_dir[$con_v['md5']] as $file_k => $file_v){
                    if(isset(self::$methods[$file_k])){
                        $class_format[$file_v['classname']] = [
                            'name' => $file_v['classname'],
                            'child' => []
                        ];
                        $methods = self::$methods[$file_k]['method']; //方法
                        $notes_data = self::$methods[$file_k]['notes_data'];//注释
                        $child = [];
                        foreach($methods as  $method_k => $method){
                            if(isset($notes_data[$method_k]) && !empty($notes_data[$method_k])){
                                $method_notes = []; //方法格式
                                foreach($notes_data[$method_k] as $notes_v){
                                    if(isset(self::$method_format[trim($notes_v[0],'@')])){
                                        $method_val = self::$method_format[trim($notes_v[0],'@')];
                                        if(is_int($method_val)){
                                            $method_notes[trim($notes_v[0],'@')] = $notes_v[$method_val];
                                        }elseif(is_array($method_val)){
                                            $data = [];
                                            foreach($method_val as $mv_k => $mv){
                                                $data[$mv] = $notes_v[$mv_k + 1] ?? '';
                                            }
                                            $method_notes[trim($notes_v[0],'@')][] = $data;
                                        }
                                    }
                                }
                                $child[$method] = $method_notes;
                            }
                        }
                        $class_format[$file_v['classname']]['child'] = $child;
                    }
                }

            }
        }
        if(empty($class_format)){
            throw new DocException('解析失败,请检查程序');
        }else{
            $class_format = base64_encode(json_encode($class_format));
            return $class_format;
        }
    }


    //解析注释
    private  function analysisNotes($notes){
//        var_dump($notes);
        $return_data = [];
        preg_match_all('/@[a-zA-Z]+ .+/',$notes,$result);
        $result_list = current($result);

        foreach($result_list as $result_info){
            $result_info = preg_replace('/ +/',' ',$result_info);
            $data = explode(' ',$result_info);
            if(array_key_exists(trim($data[0],'@'),self::$analysis_fields)){ //符合要求的解析
                $return_data[] = $data;
            }
        }
        return $return_data;
    }

    private function returnMsg($status,$msg,$data = []){
        return [
            'code' => $status,
            'msg' => $msg,
            'data' => $data
        ];
    }
}
