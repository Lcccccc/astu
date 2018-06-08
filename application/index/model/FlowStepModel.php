<?php
namespace app\index\model;
use Symfony\Component\Yaml\Tests\DumperTest;
use think\Config;
use think\Db;
use think\Model;
use think\Session;

class FlowStepModel extends Model
{
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'flow_step';
        parent::__construct($data);
    }

    /**
     * 获取当前审核用户ID
     * @param string $code 模块
     * @param int $curStep 当前审核级别
     * @return array
     */
    public static function getCurApproveUser($code, $curStep)
    {
        /**
         * 当前审核人员
         */
        $appUser = Db::name('flow')->alias('f')
            ->join('__FLOW_STEP__ fs', 'fs.flow_id=f.id', 'LEFT')
            ->join('__USER__ u', 'u.groupid=fs.user_group_id', 'LEFT')
            ->where('f.code', $code)
            ->where('fs.step', $curStep)
            ->field('u.id')
            ->select();
        return array_column($appUser, 'id');
    }


    /**
     * 获取模块流程起始步骤
     * @param $code
     * @return string
     */
    public static function getNextStep($code,$now_step='')
    {
        /**
         * 当前审核人员
         */
        $where['f.code'] = $code;
        if($now_step){
            $where['fs.step'] = array('GT',$now_step);
        }
        $min_step = Db::name('flow')->alias('f')
            ->join('__FLOW_STEP__ fs', 'fs.flow_id=f.id', 'LEFT')
            ->where($where)
            ->field('min(step) min_step')
            ->find();
        if($min_step['min_step']){
            return $min_step['min_step'];
        }else{
            return 0;
        }
    }


}