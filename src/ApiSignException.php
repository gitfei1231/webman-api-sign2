<?php

namespace Wengg\WebmanApiSign;

class ApiSignException extends \Exception
{
    const PARAMS_ERROR = 1000; //参数错误
    const APPKEY_NOT_FOUND = 1001; //appkey未找到
    const APPKEY_DISABLE = 1002; //appkey已禁用
    const APPKEY_EXPIRED = 1003; //appkey已过期
    const SIGN_VERIFY_FAIL = 1004; //签名验证失败
    const SIGN_TIMEOUT = 1005; //签名超时
    const REQUEST_INVALID = 1006; //请求失效
}
