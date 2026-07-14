<?php
$file = "app/Shared/Components/Layouts/main.php";
$content = file_get_contents($file);
$content = preg_replace("/strpos\(\\\$_SERVER\['REQUEST_URI'\],\s*'([^']+)'\)\s*===\s*0/", "strpos(\$_SERVER['REQUEST_URI'], '$1') !== false", $content);
file_put_contents($file, $content);
echo "Fixed navigation highlighting in main.php\n";
