<?php

namespace Wengg\WebmanApiSign\Driver;

/**
 * 数组
 * @author mosquito <zwj1206_hi@163.com> 2022-08-25
 */
class ArrayDriver implements BaseDriver
{
    /**
     * @var array
     */
    protected $config;

    public function __construct(array $config)
    {
        $this->config = $config;
    }

    public function getInfo(string $app_key)
    {
        $list = $this->config[$this->config['table']] ?? [];
        $list && $list = array_combine(array_column($list, 'app_key'), $list);
        return $list[$app_key] ?? [];
    }
}
