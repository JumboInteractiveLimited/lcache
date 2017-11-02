<?php

namespace LCache\l2;

class StaticL2Test extends L2CacheTest
{
    protected function buildL2()
    {
        return new StaticL2();
    }
}
