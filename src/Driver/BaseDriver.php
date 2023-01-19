<?php

namespace Wengg\WebmanApiSign\Driver;

interface BaseDriver
{
    /**
     * 获取详情
     * @return array
     * @author mosquito <zwj1206_hi@163.com> 2022-08-25
     */
    public function getInfo(string $app_key);
}
