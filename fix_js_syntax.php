<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("app/Modules"));
foreach ($files as $file) {
    if ($file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        
        $newContent = str_replace("window.BASE_URL + \\'/api/", "window.BASE_URL + '/api/", $content);
        
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
