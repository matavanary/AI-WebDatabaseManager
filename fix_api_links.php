<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("app/Modules"));
foreach ($files as $file) {
    if ($file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        
        $newContent = preg_replace("/\'\/api\//", "window.BASE_URL + \'/api/", $content);
        $newContent = preg_replace("/\"\/api\//", "window.BASE_URL + \"/api/", $newContent);
        
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
