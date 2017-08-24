<?php

/**
 * @file
 * Test file for the SQLite L1 driver in LCache library.
 */

namespace LCache\l1;

/**
 * SQLiteTest concrete implementation.
 *
 * @author ndobromirov
 */
class SQLiteTest extends L1CacheTest
{
    /**
     * {@inheritDoc}
     */
    protected function driverName()
    {
        return 'sqlite';
    }
}
