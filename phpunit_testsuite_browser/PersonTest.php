<?php
include 'Person.php';

class PersonTest extends PHPUnit_Framework_TestCase
{
    protected $person;

    public function setUp()
    {
        $this->person = new Person('Joe');
    }

    public function testGetName()
    {
        $expected = 'Joe';
        $actual = $this->person->getName();
        $this->assertEquals($expected, $actual);
    }
}
