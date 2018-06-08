<?php

namespace app\index\controller\api;

use app\index\controller\Common;
use app\index\model\UserModel;
use think\Config;
use think\Request;
use think\Session;
use think\Validate;

class Login extends Common
{
    /**
     * 用户登录
     * @return \think\response\Json
     */
    public function login()
    {
        $params = Request::instance()->param();
        if (empty($params['token'])) {
            $validate = new Validate(array(
                'username' => 'require',
                'password' => 'require',
            ), array(
                'username.require' => '用户名不能为空',
                'password.require' => '密码不能为空',
            ));
            if (!$validate->check($params)) {
                return $this->errorCode('5001', $validate->getError());
            }
        }
        list($ret, $data) = (new UserModel())->checkLoginUser($params);
        if ($ret === false) {
            return $this->errorCode($data);
        }
        Session::set('ext_user', $data);
        return $this->successCode($data);
    }

    /**
     * 修改密码
     * @return \think\response\Json
     */
    public function updatePwd()
    {
        $params = Request::instance()->param();
        $validate = new Validate(array(
            'id' => 'require',
            'old_password' => 'require',
            'password' => 'require',
            'confirm_password' => 'require',
        ), array(
            'id.require' => '用户ID不能为空',
            'old_password.require' => '原密码不能为空',
            'password.require' => '密码不能为空',
            'confirm_password' => '确认密码不能为空',
        ));
        if (!$validate->check($params)) {
            return $this->errorCode('5001', $validate->getError());
        }

        $user = UserModel::get(['id' => $params['id']]);
        if ($user['password'] != $params['old_password']) {
            return $this->errorCode(5003);
        }

        UserModel::update($params, ['id' => $params['id']], 'password');
        //刷新token
        $user->refreshToken($user['username'], $params['id']);

        return $this->successCode();
    }

    /**
     * 获取系统配置
     * @return \think\response\Json
     */
    public function getSetting()
    {
        return $this->successCode([
            'app_name' => Config::get('extend.app_name'),
            'app_version' => Config::get('extend.app_version'),
            'company_website' => Config::get('extend.company_website'),
            'company_phone' => Config::get('extend.company_phone'),
            'wechat_notary' => Config::get('extend.wechat_notary'),
            'company_address' => Config::get('extend.company_address'),
            'company_name' => Config::get('extend.company_name'),
        ]);
    }
}