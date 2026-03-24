<?php

echo "📦 Extracting Hypertube component ZIP archives...\n\n";

$baseDir = dirname(__DIR__);
$packageDir = $baseDir . '/hypertube';
$zipMap = [
    'hypertube-sdk.zip' => 'sdk',
    'hypertube-core.zip' => 'core',
    'hypertube-utils.zip' => 'utils',
    'hypertube-Binaries.zip' => 'binaries'
];

if (!class_exists('ZipArchive')) {
    echo "❌ ZIP extension is not available in PHP!\n";
    echo "   Install php-zip extension: apt-get install php-zip\n";
    exit(1);
}

if (!is_dir($packageDir)) {
    mkdir($packageDir, 0755, true);
    echo "✅ Created directory: $packageDir\n";
}

$extractedCount = 0;
$totalCount = count($zipMap);

echo "🔍 Looking for ZIP archives in: $baseDir/packages/\n\n";

foreach ($zipMap as $zipFile => $path) {
    $zipPath = $baseDir . '/packages/' . $zipFile;
    $targetDir = $packageDir . '/' . $path;

    echo "📦 Processing: $zipFile → $path\n";

    if (!file_exists($zipPath)) {
        echo "  ⚠️  Missing file: $zipFile \n";
        continue;
    }

    try {
        if (is_dir($targetDir)) {
            removeDirectory($targetDir);
        }

        mkdir($targetDir, 0755, true);

        $zip = new ZipArchive();
        $result = $zip->open($zipPath);

        if ($result !== TRUE) {
            throw new Exception("Cannot open ZIP archive: $zipFile (error code: $result)");
        }

        if ($zip->numFiles === 0) {
            throw new Exception("Archive $zipFile is empty");
        }

        $zip->extractTo($targetDir);
        $zip->close();

        echo "  ✅ Extracted to: $targetDir\n";

        $fileCount = countFiles($targetDir);
        echo "  📊 Extracted: $fileCount files\n";
        $extractedCount++;

        if (unlink($zipPath)) {
            echo "  🗑️  Deleted ZIP archive: $zipFile\n";
        } else {
            echo "  ⚠️  Could not delete ZIP archive: $zipFile\n";
        }

    } catch (Exception $e) {
        echo "  ❌ Error: " . $e->getMessage() . "\n";
    }

    echo "\n";
}

echo "📋 Summary:\n";
if ($extractedCount > 0) {
    echo " 🎉 Extracted $extractedCount/$totalCount components!\n\n";
} else {
    echo " ❌ All components not extracted!\n\n";
    exit(1);
}

echo "📁 hypertube structure:\n";
showHypertubeStructure($packageDir);

echo "\n✅ Components are available through PSR-4 autoloader:\n";
echo "   - use sdk\\ClassName;\n";

function removeDirectory($dir) {
    if (!is_dir($dir)) {
        return;
    }

    $files = array_diff(scandir($dir), ['.', '..']);
    foreach ($files as $file) {
        $path = $dir . '/' . $file;
        if (is_dir($path)) {
            removeDirectory($path);
        } else {
            unlink($path);
        }
    }
    rmdir($dir);
}

function countFiles($dir) {
    if (!is_dir($dir)) {
        return 0;
    }

    $count = 0;
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS)
        );
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $count++;
            }
        }
    } catch (Exception $e) {
        return 0;
    }
    return $count;
}

function showHypertubeStructure($packageDir)
{
    if (!is_dir($packageDir)) {
        echo "  ❌ hypertube directory does not exist\n";
        return;
    }

    $components = ['sdk', 'core', 'utils', 'binaries'];

    foreach ($components as $component) {
        $componentDir = $packageDir . '/' . $component;
        if (is_dir($componentDir)) {
            $fileCount = countFiles($componentDir);
            echo "  📁 hypertube/$component/ ($fileCount files)\n";
        } else {
            echo "  ⚪ hypertube$component/ (does not exist)\n";
        }
    }
}
