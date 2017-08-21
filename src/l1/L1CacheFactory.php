<?php

/**
 * @file
 * Contains the factory class implementation for the L1 cache drivers.
 */

namespace LCache\l1;

use LCache\state\StateL1APCu;
use LCache\state\StateL1Interface;
use LCache\state\StateL1Static;

/**
 * Class encapsulating the creation logic for all L1 cache driver instances.
 *
 * @author ndobromirov
 */
class L1CacheFactory
{
    /**
     * L1 cache drivers const
     *
     * @todo Change the return value to L1CacheInterface
     *
     * @param string $driverName
     *   Name of the L1 driver implementation to create. One of the DRIVER_*
     *   class constants.
     * @param string $customPool
     *   Pool ID to use for the data separation.
     *
     * @return L1
     *   Concrete instance that confirms to an L1 interface.
     */
    public function create($driverName = null, $customPool = null)
    {
        // Normalize input.
        $pool = $this->getPool($customPool);
        $driver = mb_convert_case($driverName, MB_CASE_LOWER);

        $factoryName = 'create' . $driver;
        if (!method_exists($this, $factoryName)) {
            // TODO: Decide on better fallback (if needed).
            $factoryName = 'createStatic';
        }

        $l1CacheInstance = call_user_func([$this, $factoryName], $pool);
        return $l1CacheInstance;
    }

    protected function createAPCu($pool): L1
    {
        return new APCu($pool, new StateL1APCu($pool));
    }

    protected function createNull(string $pool): L1
    {
        return new NullL1($pool, new StateL1Static());
    }

    protected function createStatic(string $pool): L1
    {
        return new StaticL1($pool, new StateL1Static());
    }

    protected function createSQLite(string $pool): L1
    {
        $hasApcu = function_exists('apcu_fetch');
        // TODO: Maybe implement StateL1SQLite class instead of NULL one.
        $state = $hasApcu ? new StateL1APCu("sqlite-$pool") : new StateL1Static();
        $cache = new SQLite($pool, $state);
        return $cache;
    }

    public function setPool(string $pool): L1CacheFactory
    {
        $this->pool = $pool;
        return $this;
    }

    /**
     * Pool generator utility.
     *
     * @param string $pool
     *   Custom pool to use. Defaults to NULL. If the  default is uesed, it will
     *   atempt to generate a pool value for use.
     *
     * @return string
     *   Pool value based on input and/or environment variables / state.
     */
    protected function getPool($pool = null)
    {
        if (!is_null($pool)) {
            return (string) $pool;
        } elseif (!empty($this->pool)) {
            return $this->pool;
        } else {
            return $this->generateUniqueID();
        }
    }

    /**
     * Pool generation utility.
     *
     * @see L1CacheFactory::getPool()
     *
     * @return string
     */
    protected function generateUniqueID()
    {
        // @TODO: Replace with a persistent but machine-local (and unique) method.
        return uniqid('', true) . '-' . mt_rand();
    }
}
