<?php

namespace LCache\l2;

use LCache\Address;
use LCache\DatabaseSchema;
use PDO;
use PHPUnit_Extensions_Database_DataSet_DefaultDataSet;
use PHPUnit_Extensions_Database_TestCase;

class L2CacheTest extends PHPUnit_Extensions_Database_TestCase
{
    protected $dbh = null;

    public function testDatabasePrefix()
    {
        DatabaseSchema::create($this->dbh, 'myprefix_');
        $l2 = (new Database($this->dbh, 'myprefix_'))->setCreatedTime(time());
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue', null, ['mytag']);
        $this->assertEquals('myvalue', $l2->get($myaddr));
    }

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        $this->dbh = new PDO('sqlite::memory:');
        $this->dbh->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $this->createDefaultDBConnection($this->dbh, ':memory:');
    }

    /**
    * @return PHPUnit_Extensions_Database_DataSet_IDataSet
    */
    protected function getDataSet()
    {
        return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }
}
