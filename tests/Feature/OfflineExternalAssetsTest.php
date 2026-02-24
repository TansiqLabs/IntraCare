<?php

declare(strict_types=1);

namespace Tests\Feature;

use Illuminate\Support\Str;
use Tests\TestCase;

final class OfflineExternalAssetsTest extends TestCase
{
    public function test_no_external_urls_in_runtime_blade_or_app_js(): void
    {
        $paths = [
            base_path('resources/views'),
            base_path('resources/js'),
        ];

        $allowedUrlPrefixes = [
            // XML namespaces / specs commonly used in inline SVG/HTML.
            'http://www.w3.org/',
            'https://www.w3.org/',
        ];

        $violations = [];

        foreach ($paths as $root) {
            if (! is_dir($root)) {
                continue;
            }

            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($root, \FilesystemIterator::SKIP_DOTS)
            );

            /** @var \SplFileInfo $file */
            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $path = $file->getPathname();

                // Only check likely runtime files.
                if (! Str::endsWith($path, ['.blade.php', '.js', '.ts'])) {
                    continue;
                }

                $contents = @file_get_contents($path);
                if ($contents === false) {
                    continue;
                }

                if (! preg_match_all('#https?://[^\s"\')>]+#i', $contents, $matches)) {
                    continue;
                }

                foreach ($matches[0] as $url) {
                    $isAllowed = false;
                    foreach ($allowedUrlPrefixes as $prefix) {
                        if (Str::startsWith($url, $prefix)) {
                            $isAllowed = true;
                            break;
                        }
                    }

                    if ($isAllowed) {
                        continue;
                    }

                    // Ignore localhost-like URLs sometimes used in comments/examples.
                    if (preg_match('#^https?://(localhost|127\.0\.0\.1|0\.0\.0\.0)(:\d+)?\b#i', $url)) {
                        continue;
                    }

                    $violations[] = [
                        'file' => str_replace(base_path().DIRECTORY_SEPARATOR, '', $path),
                        'url' => $url,
                    ];
                }
            }
        }

        $this->assertSame([], $violations, "External URLs found in runtime assets:\n".json_encode($violations, JSON_PRETTY_PRINT));
    }
}
