<?php

namespace LCache\l2;

use PHPUnit_Framework_TestCase;
use \Redis as PHPRedis;

class RedisSerializerTest extends RedisTest
{
    protected function buildL2()
    {
        $redis = new PHPRedis();
        $redis->connect('127.0.0.1');
        $redis->setOption(PHPRedis::OPT_SERIALIZER, PHPRedis::SERIALIZER_PHP);
        $redis->flushdb();
        return new Redis($redis);
    }
}
