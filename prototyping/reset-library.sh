#!/bin/bash
rm library.sqlite
cat library.sql | sqlite3 library.sqlite
rm library/*
