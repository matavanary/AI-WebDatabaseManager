<?php
$files = new RecursiveIteratorIterator(new RecursiveDirectoryIterator("app"));
foreach ($files as $file) {
    if ($file->getExtension() === "php") {
        $content = file_get_contents($file->getPathname());
        
        $newContent = preg_replace('/action="\/([^"]*)"/', 'action="<?= \App\Core\Application::asset(\'$1\') ?>"/', $content); // still wrong wait
        // The last slash inside the replacement string is a typo! Let's write it carefully.
        $newContent = preg_replace('/action="\/([^"]*)"/', 'action="<?= \App\Core\Application::asset(\'$1\') ?>"', $content);
        
        $newContent = preg_replace('/href="\/([^"]*)"/', 'href="<?= \App\Core\Application::asset(\'$1\') ?>"', $newContent);
        
        $newContent = preg_replace('/window\.location\.href\s*=\s*\'\/([^\']*)\'/', 'window.location.href = \'<?= \App\Core\Application::asset(\'$1\') ?>\'', $newContent);
        
        if ($newContent !== $content) {
            file_put_contents($file->getPathname(), $newContent);
            echo "Updated: " . $file->getPathname() . "\n";
        }
    }
}
