<?php

namespace Pheasant\Tests;

use Pheasant\Types;

class ResultSetTest extends \Pheasant\Tests\MysqlTestCase
{
    public function setUp()
    {
        parent::setUp();

        $t = $this->table('user', [
            'userid' => new Types\IntegerType(8, 'primary auto_increment'),
            'name' => new Types\StringType(),
            'value' => new Types\IntegerType(),
            'active' => new Types\BooleanType(),
        ]);

        $t->insert(['name' => 'Llama', 'active' => false, 'value' => 24]);
        $t->insert(['name' => 'Drama', 'active' => true, 'value' => null]);
    }

    public function testGettingARow()
    {
        $rs = $this->connection()->execute('SELECT name,value,active FROM user');
        $this->assertEquals($rs->count(), 2);

        $this->assertSame($rs->row(), ['name' => 'Llama', 'value' => '24', 'active' => '0']);
        $this->assertSame($rs->row(), ['name' => 'Drama', 'value' => null, 'active' => '1']);
        $this->assertSame($rs->row(), null);

        // this should rewind and seek
        $this->assertEquals($rs->seek(1)->row(), ['name' => 'Drama', 'value' => null, 'active' => true]);
    }

    public function testGettingAScalar()
    {
        $rs = $this->connection()->execute('SELECT name,value,active FROM user');

        $this->assertEquals($rs->scalar(), 'Llama');
        $this->assertEquals($rs->scalar(), 'Drama');
        $this->assertSame($rs->scalar(), null);

        // this should rewind and seek
        $this->assertEquals($rs->seek(0)->scalar(1), 24);
        $this->assertEquals($rs->seek(0)->scalar('name'), 'Llama');
    }

    public function testGettingAColumn()
    {
        $rs = $this->connection()->execute('SELECT name,value,active FROM user');

        $this->assertEquals(iterator_to_array($rs->column()), ['Llama', 'Drama']);
    }

    public function testGettingFieldsWithResults()
    {
        $rs = $this->connection()->execute('SELECT name,value,active FROM user');
        $fields = $rs->fields();

        $this->assertEquals(count($fields), 3);
        $this->assertEquals($fields[0]->name, 'name');
        $this->assertEquals($fields[1]->name, 'value');
        $this->assertEquals($fields[2]->name, 'active');
    }

    public function testGettingFieldsWithNoResults()
    {
        $rs = $this->connection()->execute('SELECT name,value,active FROM user WHERE 1=0');
        $fields = $rs->fields();

        $this->assertEquals(count($rs), 0);
        $this->assertEquals(count($fields), 3);
        $this->assertEquals($fields[0]->name, 'name');
        $this->assertEquals($fields[1]->name, 'value');
        $this->assertEquals($fields[2]->name, 'active');
    }
}
