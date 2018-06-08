<?php
/**
 * Created by PhpStorm.
 * User: Lccccc
 * Date: 2017/7/6
 * Time: 10:17
 */
namespace app\index\model;

use think\Config;
use think\Model;

class FileHandleModel extends Model
{
    /**
     * 转换文件
     * type:文件转换类型
     * 1:office转pdf、
     * 2:office转swf、
     * 3:office转png、
     * 4:office转pdf拆单页、
     * 5:pdf转swf、
     * 6:pdf转png、
     * 7:word转txt，
     * office支持：word,excel,ppt三种文档格式
     */
    public static function SwitchFile($file_url,$type){
        //使用第三方（聚合数据）转换文件
        $url = "http://v.juhe.cn/fileconvert/query";
        $file['url'] = 'http://'.$file_url;
        $file['type'] = $type;
        $file['key'] = "07177034955dae082dc6b4eb417ab4dc";
        $result = self::curl_post($url,$file);
        $result = json_decode($result,true);
        //下载文件到本地存放
        $files = self::get_http_file($result['result']['mes_path']);

        $files_data = self::unzip($files);
        return $files_data;
    }

    /**
     * @brief cURL模拟get请求获取目标地址返回值
     * @param $url          请求地址
     * @param int $timeout  请求有效时间
     * @return mixed
     */
    function curl_get($url,$timeout = 300){
        //验证函数是否存在
        if (! function_exists ( 'curl_init' )) {
            die('curl_init函数不存在');
        }
        //开启CURL
        $item = curl_init ();
        curl_setopt ( $item, CURLOPT_RETURNTRANSFER, true );    //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt ( $item, CURLOPT_HEADER, false );           //禁止页面头文件的信息作为数据流输出。
        curl_setopt ( $item, CURLOPT_URL, $url );               //需要获取的URL地址，也可以在curl_init()函数中设置。
        curl_setopt ( $item, CURLOPT_TIMEOUT, $timeout );       //设置cURL允许执行的最长秒数。
        curl_setopt ( $item, CURLOPT_SSL_VERIFYPEER, false );   //禁用后cURL将终止从服务端进行验证
        curl_setopt ( $item, CURLOPT_SSL_VERIFYHOST, false );   //禁用后cURL将终止从服务端进行验证(cURL为2)
        $data = curl_exec ( $item );                            //抓取数据
        @curl_close ( $item );                                  //释放句柄
        return $data;
    }

    /**
     * @brief cURL模拟post请求获取目标地址返回值
     * @param $url          请求地址
     * @param $fields       post发送的数据
     * @param int $timeout  请求有效时间
     * @return mixed
     */
    function curl_post($url,$fields,$timeout = 300){
        //验证函数是否存在
        if (! function_exists ( 'curl_init' )) {
            die('curl_init函数不存在');
        }
        //开启CURL
        $item = curl_init() ;
        curl_setopt ( $item, CURLOPT_RETURNTRANSFER, true );    //将curl_exec()获取的信息以文件流的形式返回，而不是直接输出。
        curl_setopt ( $item, CURLOPT_HEADER, false );           //禁止页面头文件的信息作为数据流输出。
        curl_setopt ( $item, CURLOPT_URL, $url );               //需要获取的URL地址，也可以在curl_init()函数中设置。
        curl_setopt ( $item, CURLOPT_POST,true) ;               // 启用时会发送一个常规的POST请求，类型为：application/x-www-form-urlencoded，就像表单提交的一样。
        curl_setopt ( $item, CURLOPT_POSTFIELDS,$fields);       // 在HTTP中的“POST”操作。如果要传送一个文件，需要一个@开头的文件名
        curl_setopt ( $item, CURLOPT_TIMEOUT, $timeout );       //设置cURL允许执行的最长秒数。
        curl_setopt ( $item, CURLOPT_SSL_VERIFYPEER, false );   //禁用后cURL将终止从服务端进行验证
        curl_setopt ( $item, CURLOPT_SSL_VERIFYHOST, false );   //禁用后cURL将终止从服务端进行验证(cURL为2)
        $data = curl_exec ( $item );                            //抓取数据
        @curl_close ( $item );                                  //释放句柄
        return $data;
    }

    /**
     * 获取远程文件下载到本地目录
     * @param $url @远程文件地址
     * @return bool|mixed|string
     */
    function get_http_file($url){
        $file = 'uploads/' .date('Ymd').'/'.basename("$url");
        $timeout = 100;
        $file = empty($file) ? pathinfo($url,PATHINFO_BASENAME) : $file;
        $dir = pathinfo($file,PATHINFO_DIRNAME);
        !is_dir($dir) && @mkdir($dir,0755,true);
        $url = str_replace(" ","%20",$url);

        if(function_exists('curl_init')) {
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $temp = curl_exec($ch);
            if(@file_put_contents($file, $temp) && !curl_error($ch)) {
                return $file;
            } else {
                return false;
            }
        } else {
            $opts = array(
                "http"=>array(
                    "method"=>"GET",
                    "header"=>"",
                    "timeout"=>$timeout)
            );
            $context = stream_context_create($opts);
            if(@copy($url, $file, $context)) {
                //$http_response_header
                return $file;
            } else {
                return false;
            }
        }
    }

    /**
     * 解压文件到指定目录
     *
     * @param   string   zip压缩文件的路径
     * @param   string   解压文件的目的路径
     * @param   boolean  是否以压缩文件的名字创建目标文件夹
     * @param   boolean  是否重写已经存在的文件
     *
     * @return  boolean  返回成功 或失败
     */
    public static function unzip($src_file, $dest_dir=false, $create_zip_name_dir=true, $overwrite=true){
        header("content-type:text/html;charset=utf8");
        $return_data['status'] = 1;
        $return_data['img'] = array();
        if ($zip = zip_open($src_file)){
            if ($zip){
                $splitter = ($create_zip_name_dir === true) ? "." : "/";
                if($dest_dir === false){
                    $dest_dir = substr($src_file, 0, strrpos($src_file, $splitter))."/";
                }

                // 如果不存在 创建目标解压目录
                self::create_dirs($dest_dir);

                // 对每个文件进行解压
                while ($zip_entry = zip_read($zip)){
                    // 文件不在根目录
                    $pos_last_slash = strrpos(zip_entry_name($zip_entry), "/");
                    if ($pos_last_slash !== false){
                        // 创建目录 在末尾带 /
                        self::create_dirs($dest_dir.substr(zip_entry_name($zip_entry), 0, $pos_last_slash+1));
                    }

                    // 打开包
                    if (zip_entry_open($zip,$zip_entry,"r")){

                        // 文件名保存在磁盘上
                        $file_name = $dest_dir.zip_entry_name($zip_entry);

                        // 检查文件是否需要重写
                        if ($overwrite === true || $overwrite === false && !is_file($file_name)){
                            // 读取压缩文件的内容
                            $fstream = zip_entry_read($zip_entry, zip_entry_filesize($zip_entry));

                            @file_put_contents($file_name, $fstream);
                            // 设置权限
                            chmod($file_name, 0777);
                            //echo "save: ".$file_name."<br />";
                            $return_data['img'][] = $_SERVER['HTTP_HOST'].'/'.$file_name;
                        }

                        // 关闭入口
                        zip_entry_close($zip_entry);
                    }
                }
                // 关闭压缩包
                zip_close($zip);
            }
        }else{
            return false;
        }
        return $return_data;
    }

    /**
     * 创建目录
     */
    public function create_dirs($path){
        if (!is_dir($path)){
            $directory_path = "";
            $directories = explode("/",$path);
            array_pop($directories);

            foreach($directories as $directory){
                $directory_path .= $directory."/";
                if (!is_dir($directory_path)){
                    mkdir($directory_path);
                    chmod($directory_path, 0777);
                }
            }
        }
    }



}