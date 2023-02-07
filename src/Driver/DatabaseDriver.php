<?php

namespace Wengg\WebmanApiSign\Driver;
use think\facade\Db;

/**
 * 数据库
 * @author mosquito <zwj1206_hi@163.com> 2022-08-25
 */
class DatabaseDriver implements BaseDriver
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
        $data = Db::name($this->config['table'])->cache($this->config['table'].'_app_key', 604800)->where('app_key', $app_key)->find();
        return $data ? (array) $data : [];
    }
}