<?php
echo "Current execution time: " . ini_get('max_execution_time') . "\n";
set_time_limit(300);
echo "New execution time: " . ini_get('max_execution_time') . "\n";
