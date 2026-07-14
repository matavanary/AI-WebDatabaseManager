<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("app"));
foreach ($files as $file) {
    if ($file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        
        // Match expressions like $var ?? 'default'
        $newContent = preg_replace_callback("/(\\$[_a-zA-Z0-9\[\]\'\"]+)\s*\?\?\s*([^\s;,\)]+)/", function($matches) {
            $var = $matches[1];
            $default = $matches[2];
            return "isset($var) ? $var : $default";
        }, $content);
        
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
