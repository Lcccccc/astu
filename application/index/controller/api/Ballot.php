<?php
namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\BallotModel;
use app\index\model\BallotOptionModel;
use app\index\model\LogModel;
use think\Db;
use think\Request;
use think\Session;
use think\Validate;

class Ballot extends Common
{
    /**
     * 发起投票
     * @return \think\response\Json
     */
    public function store()
    {
        $param = Request::instance()->param();
        $validate = new Validate(array(
            'title' => 'require',
            'end_date' => "require|date",
            'options' => 'require',
        ), array(
            'title.require' => '投票说明必须',
            'end_date.require' => '结束日期必须',
            'end_date.date' => "结束日期必须为日期格式",
            'options.require' => '选项必须',
        ));
        if (!$validate->check($param)) {
            return $this->errorCode(5001, $validate->getError());
        }

        Db::transaction(function () use ($param) {
            $user = Session::get('ext_user');
            //投票
            $ballot = BallotModel::create([
                'title' => $param['title'],
                'end_date' => $param['end_date'],
                'receid' => $param['receid'],
                'create_uid' => $user['id'],
                'create_uname' => $user['realname'],
                'create_time' => time(),
            ]);
            $options = explode(',', $param['options']);
            //投票选项
            foreach ($options as $item) {
                BallotOptionModel::create([
                    'mid' => $ballot['id'],
                    'name' => $item,
                ]);
            }
        });

        return $this->successCode();
    }

    /**
     * 投票
     * @return \think\response\Json
     */
    public function ballot()
    {
        $param = Request::instance()->param();
        $validate = new Validate(array(
            'id' => 'require',
            'mid' => 'require',
        ), array(
            'id.require' => '投票ID必须',
            'mid.require' => '投票选项必须',
        ));
        if (!$validate->check($param)) {
            return $this->errorCode(5001, $validate->getError());
        }
        $ballot = BallotModel::get($param['id']);
        $user = Session::get('ext_user');
        if (!empty($ballot['receid'])) {
            if (!in_array($user['id'], explode(',', $ballot['receid']))) {
                return $this->errorCode(5006);
            }
        }

        Db::transaction(function () use ($param) {
            //写入投票记录
            LogModel::store($param['id'], LogModel::BALLOT, LogModel::ACTION_VOTE);
            //记录票数
            LogModel::store($param['mid'], LogModel::BALLOT_OPTION, LogModel::ACTION_VOTE);

            $ballotOption = BallotOptionModel::get($param['mid']);
            $ballotOption->count = intval($ballotOption['count']) + 1;
            $ballotOption->save();
        });

        return $this->successCode();
    }

    /**
     * 投票列表
     * @return \think\response\Json
     */
    public function index()
    {
        $type = input('request.type');
        $user = Session::get('ext_user');
        //构建子查询
        $subQuery = Db::name('log')
            ->field(' COUNT(mid) AS `count`,mid,create_uid')
            ->where('tablename', LogModel::BALLOT)
            ->where('actionname', LogModel::ACTION_VOTE)
            ->where('create_uid', $user['id'])
            ->group('mid,create_uid')
            ->buildSql();

        $select = Db::name('ballot')->alias('b')
            ->join($subQuery . ' l', 'l.mid=b.id', 'LEFT')
            ->where(function ($query) use ($user) {
                $query->where('FIND_IN_SET(' . $user['id'] . ', b.receid)>0')
                    ->whereOr('b.receid', '');
            })->field('b.id,b.title,b.end_date,b.create_uid,b.create_uname,b.create_time,IFNULL(l.count, 0) AS count');
        if ($type == BallotModel::TYPE_BALLOT) {
            $select->where('l.count > 0');
        } elseif ($type == BallotModel::TYPE_NO_BALLOT) {
            $select->whereNull('l.count');
        } elseif ($type == BallotModel::TYPE_OWN) {
            $select->where('b.create_uid', $user['id']);
        }
        $ballot = $select->order('l.count')
            ->select();
        foreach ($ballot as &$item) {
            $item['create_time'] = date('Y-m-d H:i', $item['create_time']);
        }

        return $this->successCode($ballot);
    }

    /**
     * 投票详情
     * @return \think\response\Json
     */
    public function detail()
    {
        $id = input('request.id');
        if (empty($id)) {
            return $this->errorCode(5001, '投票ID必须');
        }
        $user = Session::get('ext_user');

        $ballot = BallotModel::get($id);
        if (!empty($ballot['receid']) && !in_array($user['id'], explode(',', $ballot['receid']))) {
            return $this->errorCode(5006);
        }

        $ballot['options'] = BallotOptionModel::all(['mid' => $id]);

        return $this->successCode($ballot);
    }
}