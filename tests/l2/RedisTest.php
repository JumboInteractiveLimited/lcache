<?php

namespace LCache\l2;

use LCache\Address;
use \Redis as PHPRedis;

class RedisTest extends L2CacheTest
{
    protected function buildL2()
    {
        $redis = new PHPRedis();
        $redis->connect('127.0.0.1');
        $redis->flushdb();
        return new Redis($redis);
    }

    public function testKeyGen()
    {
        $l2 = $this->buildL2();
        $myaddr = new Address('mybin', 'mykey');
        $this->assertEquals('lcache:event:5:mybin:mykey', $l2->keyFromAddress($myaddr));
        $this->assertEquals('lcache:tag:mytag', $l2->keyFromTag('mytag'));
    }

    public function testRedisL2Garbage()
    {
        $l2 = $this->buildL2();
        $this->assertEquals(0, $l2->collectGarbage());
        $this->assertEquals(0, $l2->countGarbage());
    }
}
