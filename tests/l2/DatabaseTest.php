<?php

namespace LCache\l2;

use LCache\Address;
use LCache\DatabaseSchema;
use PDO;
use PHPUnit_Extensions_Database_DataSet_DefaultDataSet;
use PHPUnit_Extensions_Database_TestCase;

class DatabaseTest extends L2CacheTest
{
    protected $dbh = null;

    protected function buildL2()
    {
        DatabaseSchema::create($this->dbh);
        return (new Database($this->dbh))->setCreatedTime(time());
    }

    public function testDatabaseCleanupAfterWrite()
    {
        DatabaseSchema::create($this->dbh);
        $myaddr = new Address('mybin', 'mykey');

        // Write to the key with the first client.
        $l2_client_a = (new Database($this->dbh))->setCreatedTime(time());
        $event_id_a = $l2_client_a->set('mypool', $myaddr, 'myvalue');

        // Verify that the first event exists and has the right value.
        $event = $l2_client_a->getEvent($event_id_a);
        $this->assertEquals('myvalue', $event->value);

        // Use a second client. This gives us a fresh event_id_low_water,
        // just like a new PHP request.
        $l2_client_b = (new Database($this->dbh))->setCreatedTime(time());

        // Write to the same key with the second client.
        $event_id_b = $l2_client_b->set('mypool', $myaddr, 'myvalue2');

        // Verify that the second event exists and has the right value.
        $event = $l2_client_b->getEvent($event_id_b);
        $this->assertEquals('myvalue2', $event->value);

        // Call the same method as on destruction. This second client should
        // now prune any writes to the key from earlier requests.
        $l2_client_b->pruneReplacedEvents();

        // Verify that the first event no longer exists.
        $event = $l2_client_b->getEvent($event_id_a);
        $this->assertNull($event);
    }

    public function testEmptyCleanUpDatabase()
    {
        $l2 = $this->buildL2();
    }

    public function testDatabasePrefix()
    {
        $l2 = $this->buildL2();
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
