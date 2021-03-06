<?php

/**
 * @file
 * Test file for the Static L1 driver in LCache library.
 */

namespace LCache\l1;

/**
 * StaticTest concrete implementation.
 *
 * @author ndobromirov
 */
class StaticTest extends L1CacheTest
{
    /**
     * {@inheritDoc}
     */
    protected function driverName()
    {
        return 'statc';
    }
}
