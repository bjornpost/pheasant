<?php

namespace Pheasant\Tests;

use \Pheasant;
use \Pheasant\Database\Mysqli;
use \Pheasant\Database\Dsn;

class ConnectionTest extends \PHPUnit\Framework\TestCase
{
    public function setUp()
    {
        $dsn = getenv('PHEASANT_TEST_DSN')
            ?: 'mysql://root@localhost/pheasanttest?charset=utf8';

        $this->dsn = new Dsn($dsn);
        $this->conn = new Mysqli\Connection($this->dsn);
    }

    public function testConnecting()
    {
        $this->assertTrue(is_numeric($this->conn->execute("SELECT CONNECTION_ID()")->scalar()));
    }

    public function testConnectingUsingSsl()
    {
        $dsn = new Dsn('mysql://root@localhost/pheasanttest?charset=utf8&ssl_ca=foobar.pem');
        $conn = new Mysqli\Connection($dsn);
        $this->assertEquals('foobar.pem', $conn->_ssl_ca);
    }

    public function testReconnecting()
    {
        $id = $this->conn->execute("SELECT CONNECTION_ID()")->scalar();

        // force a re-connect
        $this->conn->connect();
        $this->assertNotEquals($id, $this->conn->execute("SELECT CONNECTION_ID()")->scalar());
    }

    public function testSelectedDatabase()
    {
        $this->assertEquals("pheasanttest", $this->conn->selectedDatabase());
    }

    public function testSelectDatabase()
    {
        $dsn = $this->dsn->copy(array('database'=>''));
        $conn = new Mysqli\Connection($dsn);

        $this->assertNull($conn->selectedDatabase());

        $conn->selectDatabase('pheasanttest');
        $this->assertEquals('pheasanttest', $conn->selectedDatabase());
    }

    public function testSelectNonexistantDatabaseFails()
    {
        $dsn = $this->dsn->copy(array('database'=>''));
        $conn = new Mysqli\Connection($dsn);

        $this->expectException('\Pheasant\Database\Mysqli\Exception');
        $conn->selectDatabase('llamassddfasdfsdfsdf');
    }

    public function testDeadlockException()
    {
        $this->expectException('\Pheasant\Database\Mysqli\DeadlockException');
        $this->conn->execute("SIGNAL SQLSTATE '40001' SET MYSQL_ERRNO='1213'");
    }
}
