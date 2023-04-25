#!/bin/bash
clear
#rm tests/test.sqlite
#rm tests/serial.sqlite
#cat default-sqlite.sql | sqlite3 tests/test.sqlite
#cat test-serial.sql | sqlite3 tests/serial.sqlite

result=$(dirname $0)
echo $result
phpunit --bootstrap $result/tests/autoload-composer.php $result/tests
