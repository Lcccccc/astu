<?php
// +----------------------------------------------------------------------
// | ThinkPHP [ WE CAN DO IT JUST THINK ]
// +----------------------------------------------------------------------
// | Copyright (c) 2015-2017 http://thinkphp.cn All rights reserved.
// +----------------------------------------------------------------------
// | Licensed ( http://www.apache.org/licenses/LICENSE-2.0 )
// +----------------------------------------------------------------------
// | Author: maozhiming <zhiming88133@163.com>
// +----------------------------------------------------------------------

// 应用公共文件
if (!function_exists('get_const_key')) {
    /**
     * 获取常量数组键
     * @param array $arr 常量数组
     * @return string 返回值
     */
    function get_const_key($arr)
    {
        return implode(',', array_keys($arr));
    }
}

error_reporting(E_ERROR | E_WARNING | E_PARSE);