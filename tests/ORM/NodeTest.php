<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
class NodeTest extends TestCase {
	function __construct() {
		parent::__construct();
	}

	static function setUpBeforeClass() {
		TestHelper::createDatabase();
		TestHelper::createStorage();
	}

	static function tearDownAfterClass() {
		TestHelper::deleteDatabase();
		TestHelper::deleteStorage();
	}
	
	function testDefine() {
		$cpadmin = new CPAdm(TestHelper::getEPDO(), array());
		$cpadmin->handleCommand(new CommandParser("define storage basic01 type=basic location=".__DIR__."/../storage/basic01"));
		$cpadmin->handleCommand(new CommandParser("define partition backup-main type=common storage=basic01"));
		$cpadmin->handleCommand(new CommandParser("define policy forever partition=backup-main"));
		$cpadmin->handleCommand(new CommandParser("define policy month retexists=31 retdeleted=15 partition=backup-main"));
		$node1 = Node::define(TestHelper::getEPDO(), new CommandParser("define node test01 policy=forever password=secret"));
		$node2 = Node::define(TestHelper::getEPDO(), new CommandParser("define node test02 policy=forever password=secret"));
		$node3 = Node::define(TestHelper::getEPDO(), new CommandParser("define node test03 policy=month password=secret"));
		$target[0] = array("dnd_id" => "1", "dnd_name"=>"test01", "dpo_id"=>"1", "dnd_password" => $node1->getPassword(), "dnd_salt" => $node1->getSalt());
		$target[1] = array("dnd_id" => "2", "dnd_name"=>"test02", "dpo_id"=>"1", "dnd_password" => $node2->getPassword(), "dnd_salt" => $node2->getSalt());
		$target[2] = array("dnd_id" => "3", "dnd_name"=>"test03", "dpo_id"=>"2", "dnd_password" => $node3->getPassword(), "dnd_salt" => $node3->getSalt());
		$this->assertEquals($target, TestHelper::dumpTable(TestHelper::getEPDO(), "d_node", "dnd_id"));
	}
	
	function testDefineUnique() {
		// This should be nicer, ie throw its own exception.
		$this->expectException(PDOException::class);
		Node::define(TestHelper::getEPDO(), new CommandParser("define node test01 policy=forever password=secret"));
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
	
	function testUpdatePassword() {
		Node::update(TestHelper::getEPDO(), new CommandParser("update node test01 password=secure123"));
		$db = TestHelper::getEPDO()->row("select * from d_node where dnd_name = ?", array("test01"));
		$hash = sha1("secure123".$db["dnd_salt"]);
		$this->assertEquals($hash, $db["dnd_password"]);
	}
	
	function testAuthenticateSuccess() {
		$node = Node::authenticate(TestHelper::getEPDO(), "test01:secure123");
		$this->assertInstanceOf(Node::class, $node);
	}

	function testAuthenticateNoPass() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Unable to read password for node test01");
		$node = Node::authenticate(TestHelper::getEPDO(), "test01");
	}

	function testAuthenticateWrongPass() {
		$this->expectException(Exception::class);
		$this->expectExceptionMessage("Unable to authenticate");
		$node = Node::authenticate(TestHelper::getEPDO(), "test01:letmein");
	}
	
	
}
