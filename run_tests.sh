#!/bin/bash
cd /c/Users/HP/Desktop/samacite
php artisan test 2>&1 | grep -E "(PASS|FAIL|Tests:|passed|failed)"
