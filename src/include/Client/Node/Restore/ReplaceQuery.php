<?php
namespace Node;
class ReplaceQuery {
	private string $reason = "";
	private mixed $input = STDIN;
	private string $defaultAnswer = "";
	function __construct(string $reason) {
		$this->reason = $reason;
	}

	function setInput(mixed $input): void {
		$this->input = $input;
	}
	
	function setDefault(string $default): void {
		\Assert::isEnum($default, array("r", "s"));
		$this->defaultAnswer = $default;
	}

	private function getQueryOptions(): string {
		$options = "[r]eplace once".PHP_EOL;
		$options .= "[R]eplace always".PHP_EOL;
		$options .= "[s]kip (or enter)".PHP_EOL;
		$options .= "[S]kip always".PHP_EOL;
		$options .= "[c]ancel".PHP_EOL;
		$options .= "> ";
	return $options;
	}
	
	private function query(): string {
		if($this->defaultAnswer !== "") {
			return $this->defaultAnswer;
		}
		echo $this->reason.PHP_EOL;
		while(true) {
			echo $this->getQueryOptions();
			$input = trim(fgets($this->input));
			if($input === "S") {
				$this->defaultAnswer = "s";
			return "s";
			}
			if($input=="R") {
				$this->defaultAnswer = "r";
			return "r";
			}
			if($input === "c") {
				throw new \Exception("user wishes to cancel");
			}
			if(in_array($input, array("c", "s", "r", "S", "R"))) {
				return $input;
			}
		}
	}
	
	public function replace(): bool {
		return $this->query()==="r";
	}
}