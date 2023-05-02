<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class NodeTest extends TestCase {
	function __construct() {
		parent::__construct();
		$this->now = mktime();
	}
	static function setUpBeforeClass() {
		TestHelper::resetDatabase();
	}
	
	function testDefine() {
		$cpadmin = new CPAdm(TestHelper::getEPDO());
		$cpadmin->handleCommand(new CommandParser("define storage basic01 type=basic location=".__DIR__."/../storage/basic01"));
		$cpadmin->handleCommand(new CommandParser("define partition backup-main type=common storage=basic01"));
		$cpadmin->handleCommand(new CommandParser("define policy forever partition=backup-main"));
		$cpadmin->handleCommand(new CommandParser("define policy month retexists=31 retdeleted=15 partition=backup-main"));
		Node::define(TestHelper::getEPDO(), new CommandParser("define node test01 policy=forever"));
		Node::define(TestHelper::getEPDO(), new CommandParser("define node test02 policy=forever"));
		Node::define(TestHelper::getEPDO(), new CommandParser("define node test03 policy=month"));
		$target[0] = array("dnd_id" => "1", "dnd_name"=>"test01", "dpo_id"=>"1");
		$target[1] = array("dnd_id" => "2", "dnd_name"=>"test02", "dpo_id"=>"1");
		$target[2] = array("dnd_id" => "3", "dnd_name"=>"test03", "dpo_id"=>"2");
		$this->assertEquals($target, TestHelper::dumpTable(TestHelper::getEPDO(), "d_node", "dnd_id"));
	}
	
	function testDefineUnique() {
		$this->expectException(Exception::class);
		Policy::define(TestHelper::getEPDO(), new CommandParser("define node test01 policy=forever"));
	}
	
	function testFromArray() {
		$row = TestHelper::getEPDO()->row("select * from d_node where dnd_id = ?", array(3));
		$node = Node::fromArray(TestHelper::getEPDO(), $row);
		$this->assertEquals(3, $node->getId());
		$this->assertEquals("test03", $node->getName());
		$this->assertInstanceOf(Policy::class, $node->getPolicy());
		$this->assertEquals(2, $node->getPolicy()->getId());
		
	}
	
	function testFromName() {
		$node = Node::fromName(TestHelper::getEPDO(), "test03");
		$this->assertEquals(3, $node->getId());
		$this->assertEquals("test03", $node->getName());
		$this->assertInstanceOf(Policy::class, $node->getPolicy());
		$this->assertEquals(2, $node->getPolicy()->getId());
	}
	
	function testFromNameBogus() {
		$this->expectException(InvalidArgumentException::class);
		$this->expectExceptionMessage("Node 'squid' does not exist.");
		Node::fromName(TestHelper::getEPDO(), "squid");
	}
	
	function testFromId() {
		$node = Node::fromId(TestHelper::getEPDO(), 3);
		$this->assertEquals(3, $node->getId());
		$this->assertEquals("test03", $node->getName());
		$this->assertInstanceOf(Policy::class, $node->getPolicy());
		$this->assertEquals(2, $node->getPolicy()->getId());
	}
	
	function testFromIdBogus() {
		$this->expectException(RuntimeException::class);
		$this->expectExceptionMessage("Node with id '27' does not exist.");
		Node::fromId(TestHelper::getEPDO(), 27);

	}
}
