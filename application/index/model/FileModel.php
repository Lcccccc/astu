<?php
namespace app\index\model;

use think\Config;
use think\Model;

class FileModel extends Model
{
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'files';
        parent::__construct($data);
    }

    /**
     *  获取文件信息
     * @param $aid
     * @return false|\PDOStatement|string|\think\Collection
     */
    public static function getFile($aid)
    {
        $files = self::whereIn('id', $aid)
            ->field('id,filename,filesize,fileext,filepath')
            ->select();

        return $files;
    }

    /**
     * 上传文件
     * @return string
     */
    public static function upload($filename='file'){
        // 获取表单上传文件 例如上传了001.jpg
        $file = request()->file($filename);
        // 移动到框架应用根目录/public/uploads/ 目录下
//        $info = $file->move(ROOT_PATH . 'public' . DS . 'uploads');
        if($file){
            $info = $file->validate(['size'=>80015678,'ext'=>'jpg,png,gif,pdf,xsl,xslx,txt,pptx,doc,docx,ppt'])->move(ROOT_PATH . '/public/uploads');
            if($info){
                // 成功上传后 获取上传信息
                return '/uploads/'.$info->getSaveName();
            }else{
                // 上传失败获取错误信息
                return $file->getError();
            }
        }
        return '';
    }

}