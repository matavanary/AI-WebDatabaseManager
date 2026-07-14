<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("app"));
foreach ($files as $file) {
    if ($file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        
        $newContent = preg_replace("/header\('Location:\s*\/([^\']+)'\)/", "header('Location: ' . \App\Core\Application::asset('$1'))", $content);
        
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
