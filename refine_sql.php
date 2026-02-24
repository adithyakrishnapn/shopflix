<?php

$sourceFile = 'database/master_template.sql';
$targetFile = 'database/master_template_clean.sql';

if (!file_exists($sourceFile)) {
    die("Source file not found: $sourceFile\n");
}

$handle = fopen($sourceFile, 'r');
$output = fopen($targetFile, 'w');

$skipMode = false;
$count = 0;

while (($line = fgets($handle)) !== false) {
    $trimmedLine = trim($line);
    
    // Start skipping if we see an INSERT statement
    if (stripos($trimmedLine, 'INSERT INTO') === 0) {
        $skipMode = true;
        $count++;
    }
    
    if (!$skipMode) {
        fwrite($output, $line);
    }
    
    // If the line ends an INSERT statement (semicolon), stop skipping
    // Note: This assumes standard mysqldump format where INSERTs end with ;
    if ($skipMode && substr($trimmedLine, -1) === ';') {
        $skipMode = false;
    }
}

fclose($handle);
fclose($output);

echo "Successfully removed $count INSERT blocks.\n";
echo "Cleaned SQL saved to: $targetFile\n";
