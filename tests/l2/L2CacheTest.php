<?php

namespace LCache\l2;

use LCache\Address;
use PDO;
use PHPUnit_Extensions_Database_DataSet_DefaultDataSet;
use PHPUnit_Extensions_Database_TestCase;

abstract class L2CacheTest extends PHPUnit_Extensions_Database_TestCase
{

    abstract protected function buildL2();

    public function testHits()
    {
        $l2 = $this->buildL2();
        $myaddr = new Address('mybin', uniqid());
        $l2->set('mypool', $myaddr, 'myvalue');
        $this->assertEquals(0, $l2->getHits());
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals(1, $l2->getHits());
    }

    public function testMisses()
    {
        $l2 = $this->buildL2();
        $myaddr = new Address('mybin', uniqid());
        $this->assertEquals(0, $l2->getMisses());
        $this->assertEquals(null, $l2->get($myaddr));
        $this->assertEquals(1, $l2->getMisses());
    }

    public function testTags()
    {
        $l2 = $this->buildL2();
        $l2->set('mypool', new Address());
        $myaddr = new Address('mybin', uniqid());
        $mytag = 'foo';
        $l2->set('mypool', $myaddr, 'myvalue', null, [$mytag]);
        $this->assertNotNull($l2->get($myaddr));
        $this->assertEquals([$mytag], $l2->getEntry($myaddr)->tags);
        $this->assertEquals([$myaddr], $l2->getAddressesForTag($mytag));
    }

    public function testExpiration()
    {
        $l2 = $this->buildL2();
        $myaddr = new Address('mybin', uniqid());
        $l2->set('mypool', $myaddr, 'myvalue', -1);
        $this->assertNull($l2->get($myaddr));
    }

    public function testReread()
    {
        $l2 = $this->buildL2();
        $myaddr = new Address('mybin', uniqid());
        $l2->set('mypool', $myaddr, 'myvalue');
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
    }

    public function testClear()
    {
        $l2 = $this->buildL2();
        $myaddr = new Address('mybin', uniqid());
        $l2->set('mypool', $myaddr, 'myvalue');
        $l2->delete('mypool', new Address());
        $this->assertNull($l2->get($myaddr));
    }

    public function testBatchDeletion()
    {
        $l2 = $this->buildL2();
        $myaddr = new Address('mybin', uniqid());
        $l2->set('mypool', $myaddr, 'myvalue');

        $mybin = new Address('mybin', null);
        $l2->delete('mypool', $mybin);

        $this->assertNull($l2->get($myaddr));
    }

    public function testExists()
    {
        $l2 = $this->buildL2();
        $myaddr = new Address('mybin', uniqid());
        $l2->set('mypool', $myaddr, 'myvalue');
        $this->assertTrue($l2->exists($myaddr));
        $l2->delete('mypool', $myaddr);
        $this->assertFalse($l2->exists($myaddr));
    }

    /**
     * @return PHPUnit_Extensions_Database_DB_IDatabaseConnection
     */
    protected function getConnection()
    {
        return $this->createDefaultDBConnection(new PDO('sqlite::memory:'), ':memory:');
    }

    /**
    * @return PHPUnit_Extensions_Database_DataSet_IDataSet
    */
    protected function getDataSet()
    {
        return new PHPUnit_Extensions_Database_DataSet_DefaultDataSet();
    }
}
