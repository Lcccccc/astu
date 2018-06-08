<?php
namespace app\index\model;
use think\Config;
use think\Model;

class FlowModel extends Model
{
    public function __construct($data = [])
    {
        $this->table = Config::get('database.prefix') . 'flow';
        parent::__construct($data);
    }

    public function flowLogs()
    {
        return $this->hasMany('FlowLogModel', 'flow_id', 'id');
    }

    public function flowSteps()
    {
        return $this->hasMany('FlowStepModel', 'flow_id', 'id');
    }
}