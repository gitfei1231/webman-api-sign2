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

    public function getInfo(string $app_id)
    {
        $db_cache_time = $this->config['db_cache_time'];
        $key  = is_null($db_cache_time) ? false : $app_id;
        $time = is_null($db_cache_time) ? 0     : $db_cache_time;
        $data = Db::name($this->config['table'])->cache($key, $time)->where('app_id', $app_id)->find();
        return $data ? (array) $data : [];
    }
}