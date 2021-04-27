## Composer install
step1:composer config repo.packagist composer  https://packagist.org/  
step2:composer require kehao/doc_create @dev

## Operation
        require_once 'vendor/autoload.php';
        
        $name = '名称';
        $target_dir = 'F:\code\test'; //生成文档目标目录
        $con = 'F:\code\test'; //解析目录 
        
        try {
            $doc = new \Doc\doc('test',$target_dir,$con,1);
            $base64 = $doc->init();
            var_dump($base64);exit;
        }catch (\Doc\DocException $e){
            var_dump($e->getMessage());
        };
        
## Analysis
    
    Class:
    
    Method:
    @method 方法名
    @name   方法中文名
    @param int id 主键id 是否必传1/0
    @return int code 返回参数200成功201失败
    @desc 描述
    @url url地址     