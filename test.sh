#!/bin/bash
clear
#rm tests/test.sqlite
#rm tests/serial.sqlite
#cat default-sqlite.sql | sqlite3 tests/test.sqlite
#cat test-serial.sql | sqlite3 tests/serial.sqlite

result=$(dirname $0)
if [ -n "$1" ]; then
	phpunit --bootstrap $result/tests/autoload-composer.php $result/tests --filter $1
else
	phpunit --bootstrap $result/tests/autoload-composer.php $result/tests
fi

#phpunit --bootstrap $result/tests/autoload-composer.php $result/tests
