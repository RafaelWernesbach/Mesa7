<?php

namespace App\Support;

use Filament\Forms\Components\BaseFileUpload;

class PublicUploadPreview
{
    /**
     * @param  string|array<string, string>|null  $storedFileNames
     * @return array{name: string, size: int, type: ?string, url: ?string}|null
     */
    public static function fileInformation(BaseFileUpload $component, string $file, string|array|null $storedFileNames): ?array
    {
        $information = $component->getUploadedFile($file, $storedFileNames);

        if ($information === null || $information['url'] === null || config('filesystems.disks.public.driver') !== 'local') {
            return $information;
        }

        $url = parse_url($information['url']);
        $appUrl = parse_url((string) config('app.url'));
        if (! is_array($url) || ! is_array($appUrl)) {
            return $information;
        }

        if (isset($url['host']) && (
            $url['host'] !== ($appUrl['host'] ?? null)
            || ($url['scheme'] ?? null) !== ($appUrl['scheme'] ?? null)
            || ($url['port'] ?? null) !== ($appUrl['port'] ?? null)
        )) {
            return $information;
        }

        $path = $url['path'] ?? null;
        if (is_string($path) && $path !== '') {
            $information['url'] = '/'.ltrim($path, '/');
            if (isset($url['query'])) {
                $information['url'] .= '?'.$url['query'];
            }
        }

        return $information;
    }
}
