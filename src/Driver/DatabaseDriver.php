<?php

namespace Wengg\WebmanApiSign\Driver;

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
        $data = \support\Db::table($this->config['table'])->where('app_key', $app_key)->first();
        return $data ? (array) $data : [];
    }
}
