<?php

use Illuminate\Support\Str;

test('flux button variants use values supported by installed flux version', function () {
    $allowedVariants = ['outline', 'primary', 'filled', 'danger', 'ghost', 'subtle'];
    $invalidVariants = [];

    $bladeFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($bladeFiles as $bladeFile) {
        if ($bladeFile->getExtension() !== 'php' || ! Str::endsWith($bladeFile->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($bladeFile->getPathname());

        preg_match_all('/<flux:button\b[^>]*\svariant=(["\'])(?<variant>[^"\']+)\1/', $contents, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches['variant'] as [$variant, $offset]) {
            if (in_array($variant, $allowedVariants, true)) {
                continue;
            }

            $line = substr_count(substr($contents, 0, $offset), PHP_EOL) + 1;
            $invalidVariants[] = "{$bladeFile->getPathname()}:{$line} uses variant [{$variant}]";
        }
    }

    expect($invalidVariants)->toBeEmpty();
});

test('flux select variants use values supported by installed flux version', function () {
    $allowedVariants = ['default', 'listbox', 'combobox'];
    $invalidVariants = [];

    $bladeFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($bladeFiles as $bladeFile) {
        if ($bladeFile->getExtension() !== 'php' || ! Str::endsWith($bladeFile->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($bladeFile->getPathname());

        preg_match_all('/<flux:select\b[^>]*\svariant=(["\'])(?<variant>[^"\']+)\1/', $contents, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches['variant'] as [$variant, $offset]) {
            if (in_array($variant, $allowedVariants, true)) {
                continue;
            }

            $line = substr_count(substr($contents, 0, $offset), PHP_EOL) + 1;
            $invalidVariants[] = "{$bladeFile->getPathname()}:{$line} uses variant [{$variant}]";
        }
    }

    expect($invalidVariants)->toBeEmpty();
});

test('flux component attributes do not leak uncompiled blade javascript helpers', function () {
    $invalidAttributes = [];

    $bladeFiles = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator(resource_path('views'), FilesystemIterator::SKIP_DOTS)
    );

    foreach ($bladeFiles as $bladeFile) {
        if ($bladeFile->getExtension() !== 'php' || ! Str::endsWith($bladeFile->getFilename(), '.blade.php')) {
            continue;
        }

        $contents = file_get_contents($bladeFile->getPathname());

        preg_match_all('/<flux:[^>]*@js\(/', $contents, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$match, $offset]) {
            $line = substr_count(substr($contents, 0, $offset), PHP_EOL) + 1;
            $invalidAttributes[] = "{$bladeFile->getPathname()}:{$line} contains [{$match}]";
        }
    }

    expect($invalidAttributes)->toBeEmpty();
});
