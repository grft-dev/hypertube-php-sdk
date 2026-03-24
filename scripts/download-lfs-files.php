<?php

echo "🔍 Checking Git LFS files for Hypertube SDK...\n\n";

$packageDir = dirname(__DIR__);
if (!is_dir($packageDir)) {
    echo "❌ Hypertube package directory not found: $packageDir\n";
    exit(1);
}

$packageVersion = detectPackageVersion();
if (!$packageVersion) {
    echo "❌ Could not detect package version\n";
    exit(1);
}

echo "📦 Detected package version: $packageVersion\n\n";

$packagesDir = $packageDir . '/packages';

$lfsFiles = detectLfsFiles($packagesDir, $packageVersion);

if (empty($lfsFiles)) {
    echo "✅ No Git LFS files detected that need downloading.\n\n";
    exit(0);
}

$downloadedCount = 0;

foreach ($lfsFiles as $filename => $fileInfo)
{
    $filePath = $packagesDir . '/' . $filename;

    echo "📦 Checking: $filename\n";

    if (!isLfsPointerFile($filePath)) {
        echo "  ✅ File already exists with actual content\n\n";
        continue;
    }

    echo "  ⚠️  Found Git LFS pointer file, downloading actual file...\n";

    $success = downloadLfsFile( $filePath, $fileInfo['url']);

    if ($success) {
        echo "  ✅ Successfully downloaded $filename\n";
        $downloadedCount++;
    } else {
        echo "  ❌ Failed to download $filename\n";
    }

    echo "\n";
}

if ($downloadedCount > 0) {
    echo "🎉 Downloaded $downloadedCount Git LFS file(s)!\n\n";
} else {
    echo "✅ All Git LFS files are already available.\n\n";
}

echo "📋 Final status:\n";
foreach ($lfsFiles as $filename => $fileInfo)
{
    $filePath = $packagesDir . '/' . $filename;
    if (file_exists($filePath) && !isLfsPointerFile($filePath)) {
        echo "  ✅ $filename (" . number_format(filesize($filePath)) . " bytes)\n";
    } else {
        echo "  ❌ $filename (still missing or LFS pointer)\n";
    }
}

echo "\n";

function detectPackageVersion()
{
    $lockFilePath = dirname(__DIR__,4) . '/composer.lock';
    if (file_exists($lockFilePath)) {
        $lockData = json_decode(file_get_contents($lockFilePath), true);
        if (isset($lockData['packages'])) {
            foreach ($lockData['packages'] as $package) {
                if ($package['name'] === 'hypertube/hypertube-php-sdk') {
                    return $package['version'];
                }
            }
        }
    }

    return false;
}

function detectLfsFiles($packagesDir, $version)
{
    $lfsFiles = [];
    if (!is_dir($packagesDir)) {
        return $lfsFiles;
    }

    $files = scandir($packagesDir);
    foreach ($files as $file) {
        if ($file === '.' || $file === '..') {
            continue;
        }

        $filePath = $packagesDir . '/' . $file;
        if (isLfsPointerFile($filePath) || (pathinfo($file, PATHINFO_EXTENSION) === 'zip'
                && filesize($filePath) < 1000)) {
            $lfsFiles[$file] = [
                'url' => buildGitHubLfsUrl($file, $version)
            ];
        }
    }

    return $lfsFiles;
}

function isLfsPointerFile($filePath)
{
    if (!file_exists($filePath)) {
        return false;
    }

    $fileSize = filesize($filePath);
    if ($fileSize > 500) {
        return false;
    }

    $content = file_get_contents($filePath);

    return strpos($content, 'git-lfs.github.com') !== false &&
        strpos($content, 'version https://git-lfs.github.com/spec/v1') !== false;
}

function buildGitHubLfsUrl($filename, $version)
{
    $baseUrl = 'https://github.com/grft-dev/hypertube-php-sdk/raw';

    if ($version !== 'main' && substr($version, 0, 1) !== 'v') {
        $version = 'v' . $version;
    }

    return "$baseUrl/$version/packages/$filename";
}

function downloadLfsFile($filePath, $url)
{
    echo "  📥 Downloading from: $url\n";

    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'User-Agent: Composer-Git-LFS-Downloader/1.0',
                'Accept: application/octet-stream'
            ],
            'timeout' => 300,
            'follow_location' => true
        ]
    ]);

    $tempFile = $filePath . '.tmp';

    if (file_exists($tempFile)) {
        unlink($tempFile);
    }

    $handle = @fopen($url, 'rb', false, $context);
    if ($handle === false) {
        echo "  ❌ Failed to open URL for reading\n";

        return false;
    }

    $tempHandle = @fopen($tempFile, 'wb');
    if ($tempHandle === false) {
        echo "  ❌ Failed to create temporary file\n";
        fclose($handle);

        return false;
    }

    $downloadedBytes = 0;
    $chunkSize = 8192;

    while (!feof($handle)) {
        $chunk = fread($handle, $chunkSize);
        if ($chunk === false) {
            echo "  ❌ Failed to read from URL\n";
            fclose($handle);
            fclose($tempHandle);
            unlink($tempFile);

            return false;
        }

        if (fwrite($tempHandle, $chunk) === false) {
            echo "  ❌ Failed to write to temporary file\n";
            fclose($handle);
            fclose($tempHandle);
            unlink($tempFile);

            return false;
        }

        $downloadedBytes += strlen($chunk);

        if ($downloadedBytes % (1024 * 1024) === 0) {
            echo "  📊 Downloaded: " . number_format($downloadedBytes) . " bytes...\n";
        }
    }

    fclose($handle);
    fclose($tempHandle);

    $tempFileSize = filesize($tempFile);
    if ($tempFileSize < 1000) {
        echo "  ⚠️  Downloaded file seems too small ($tempFileSize bytes), might be another LFS pointer\n";
        unlink($tempFile);
        return false;
    }

    if (file_exists($filePath)) {
        unlink($filePath);
    }

    if (rename($tempFile, $filePath)) {
        echo "  📊 Downloaded: " . number_format($tempFileSize) . " bytes\n";
        return true;
    } else {
        if (file_exists($tempFile)) {
            unlink($tempFile);
        }
        return false;
    }
}
