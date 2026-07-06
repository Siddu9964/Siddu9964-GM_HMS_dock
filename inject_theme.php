<?php
// Script to inject gm-theme.css globally into all PHP files.
$rootDir = __DIR__;
$themeLink = '<link rel="stylesheet" href="/GM_HMS/assets/css/gm-theme.css">' . "\n";
$count = 0;

$iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($rootDir));

foreach ($iterator as $file) {
    if ($file->isFile() && $file->getExtension() === 'php' && $file->getFilename() !== 'inject_theme.php') {
        $filepath = $file->getRealPath();
        
        $content = file_get_contents($filepath);
        if ($content === false) continue;
        
        // If gm-theme.css is already there, skip
        if (strpos($content, 'gm-theme.css') !== false) {
            continue;
        }
        
        // If the file has <head>
        if (stripos($content, '<head>') !== false || stripos($content, '<head ') !== false) {
            // Find <head> or <head ...> and replace
            $newContent = preg_replace('/(<head[^>]*>)/i', '$1' . "\n    " . $themeLink, $content, 1);
            
            if ($newContent !== null && $newContent !== $content) {
                file_put_contents($filepath, $newContent);
                $count++;
            }
        }
    }
}

echo "Injected gm-theme.css into $count files.\n";
?>
