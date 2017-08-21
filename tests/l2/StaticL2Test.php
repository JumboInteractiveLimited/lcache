<?php

namespace LCache\l2;

use LCache\Address;
use PHPUnit_Framework_TestCase;

class StaticL2Test extends PHPUnit_Framework_TestCase
{
    public function testStaticL2Expiration()
    {
        $l2 = (new StaticL2())->setCreatedTime(time());
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue', -1);
        $this->assertNull($l2->get($myaddr));
    }

    public function testStaticL2Reread()
    {
        $l2 = new StaticL2();
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
        $this->assertEquals('myvalue', $l2->get($myaddr));
    }

    public function testClearStaticL2()
    {
        $l2 = (new StaticL2())->setCreatedTime(time());
        $myaddr = new Address('mybin', 'mykey');
        $l2->set('mypool', $myaddr, 'myvalue');
        $l2->delete('mypool', new Address());
        $this->assertNull($l2->get($myaddr));
    }
}
