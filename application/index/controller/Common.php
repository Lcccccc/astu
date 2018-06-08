<?php
namespace app\index\controller;

use think\Config;
use think\Controller;
use think\Request;

class Common extends Controller
{
    protected $prefix = '';

    public function __construct(Request $request = null)
    {
        $this->prefix = Config::get('database.prefix');
        parent::__construct($request);
    }

    /**
     * Response with CORS(https://developer.mozilla.org/zh-CN/docs/Web/HTTP/Access_control_CORS)
     * @param $data
     * @return \think\response\Json
     */
    protected function corsReturn($data)
    {
        return json($data, 200, array(
            'Content-Type' => 'application/json',
            'Access-Control-Allow-Origin' => config('extend.origin'),
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Expose-Headers' => 'Set-Cookie',
            'Access-Control-Allow-Headers' => 'Origin, Content-Type, Accept, Authorization'));
    }

    /**
     * response with error code which defined in error.php
     * @param $code
     * @param $msg
     * @return \think\response\Json
     */
    protected function errorCode($code, $msg = '')
    {
        if (empty($msg)) {
            $msg = config('error.' . $code);
        }

        return $this->corsReturn(array(
            'code' => $code,
            'msg' => $msg,
            'obj' => null,
        ));
    }

    /**
     * response with object to user
     * @param $data
     * @return \think\response\Json
     */
    protected function successCode($data = [], $msg = '返回成功')
    {
        return $this->corsReturn(array(
            'code' => 0,
            'msg' => $msg,
            'data' => $data,
        ));
    }

    /**
     * 回去错误信息
     * @param $code
     * @return mixed
     */
    protected function getErrorMsg($code)
    {
        return Config::get('error.' . $code);
    }
}