<?php
declare(strict_types=1);
use PHPUnit\Framework\TestCase;
use Node\ReplaceQuery;
class ReplaceQueryTest extends TestCase {
	private function getQuestion(): string {
		return "File %s already exists.";
	}

	function testConstruct(): void {
		$rq = new ReplaceQuery($this->getQuestion());
		$this->assertInstanceOf(ReplaceQuery::class, $rq);
	}
	
	private function getFirst(string $filename): string {
		$first = sprintf($this->getQuestion(), $filename).PHP_EOL;
		$first .= $this->getOptions();
	return $first;
	}
	
	private function getOptions(): string {
		$options = "[r]eplace once".PHP_EOL;
		$options .= "[R]eplace always".PHP_EOL;
		$options .= "[s]kip (or enter)".PHP_EOL;
		$options .= "[S]kip always".PHP_EOL;
		$options .= "[c]ancel".PHP_EOL;
		$options .= "> ";
	return $options;
	}
	
	function testSetDefaultReplace(): void {
		$rq = new ReplaceQuery($this->getQuestion());
		$rq->setDefault("r");
		$this->assertSame(true, $rq->replace("test.txt"));
	}
	
	function testSetDefaultSkip(): void {
		$rq = new ReplaceQuery($this->getQuestion());
		$rq->setDefault("s");
		$this->assertSame(false, $rq->replace("test.txt"));
	}
	
	function testSetInvalidDefault(): void {
		$rq = new ReplaceQuery($this->getQuestion());
		$this->expectException(\InvalidArgumentException::class);
		$rq->setDefault("k");
	}
	
	function testCancel(): void {
		$rq = new ReplaceQuery($this->getQuestion());
		$mem = fopen("php://memory", "r+");
		fwrite($mem, "c\n");
		rewind($mem);
		$rq->setInput($mem);
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->expectException(\Exception::class);
		$rq->replace("test.txt");
	}

	function testReplaceOnce(): void {
		$rq = new ReplaceQuery($this->getQuestion());
		$mem = fopen("php://memory", "r+");
		fwrite($mem, "r\nr\nc\n");
		rewind($mem);
		$rq->setInput($mem);
		
		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(true, $rq->replace("test.txt"));
		ob_end_clean();
		
		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(true, $rq->replace("test.txt"));
		ob_end_clean();
		
		
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->expectException(\Exception::class);
		$this->assertSame(true, $rq->replace("test.txt"));
	}
	

	function testReplace(): void {
		$rq = new ReplaceQuery("File already exists.");
		$mem = fopen("php://memory", "r+");
		fwrite($mem, "R\nc\n");
		rewind($mem);
		$rq->setInput($mem);
		
		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(true, $rq->replace("test.txt"));
		ob_end_clean();
		
		// After using 'R' instead of r, replace will always yield true.
		$this->expectOutputString("");
		$this->assertSame(true, $rq->replace("test.txt"));
		$this->assertSame(true, $rq->replace("test.txt"));
		$this->assertSame(true, $rq->replace("test.txt"));
		$this->assertSame(true, $rq->replace("test.txt"));
	}

	function testSkipOnce(): void {
		$rq = new ReplaceQuery($this->getQuestion());
		$mem = fopen("php://memory", "r+");
		fwrite($mem, "s\ns\nc\n");
		rewind($mem);
		$rq->setInput($mem);
		
		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(false, $rq->replace("test.txt"));
		ob_end_clean();
		
		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(false, $rq->replace("test.txt"));
		ob_end_clean();
		
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->expectException(\Exception::class);
		$this->assertSame(true, $rq->replace("test.txt"));
	}

	function testSkipAlways(): void {
		$rq = new ReplaceQuery($this->getQuestion());
		$mem = fopen("php://memory", "r+");
		fwrite($mem, "S\n");
		rewind($mem);
		$rq->setInput($mem);
		
		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(false, $rq->replace("test.txt"));
		ob_end_clean();
		
		$this->expectOutputString("");
		$this->assertSame(false, $rq->replace("test.txt"));
		$this->assertSame(false, $rq->replace("test.txt"));
		$this->assertSame(false, $rq->replace("test.txt"));
		$this->assertSame(false, $rq->replace("test.txt"));
		$this->assertSame(false, $rq->replace("test.txt"));
	}

	function testReplaceMixed(): void {
		$rq = new ReplaceQuery($this->getQuestion());
		$mem = fopen("php://memory", "r+");
		fwrite($mem, "r\ns\ns\nr\nc\n");
		rewind($mem);
		$rq->setInput($mem);
		
		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(true, $rq->replace("test.txt"));
		ob_end_clean();
		
		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(false, $rq->replace("test.txt"));
		ob_end_clean();

		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(false, $rq->replace("test.txt"));
		ob_end_clean();

		ob_start();
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->assertSame(true, $rq->replace("test.txt"));
		ob_end_clean();

		
		$this->expectOutputString($this->getFirst("test.txt"));
		$this->expectException(\Exception::class);
		$this->assertSame(true, $rq->replace("test.txt"));
	}

}