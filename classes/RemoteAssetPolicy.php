<?php

namespace Renatio\DynamicPDF\Classes;

use Dompdf\Dompdf;
use System\Classes\SiteManager;
use System\Models\SiteDefinition;

class RemoteAssetPolicy
{
    public function allowApplicationAssets(Dompdf $dompdf): void
    {
        $options = $dompdf->getOptions();

        $hosts = array_values(array_unique(array_merge($options->getAllowedRemoteHosts() ?: [], $this->applicationHosts())));

        /** An empty allowed host list is ignored by dompdf, which would leave remote fetching unrestricted. */
        if ($hosts === []) {
            $options->setIsRemoteEnabled(false);
        } else {
            $options->setIsRemoteEnabled(true)->setAllowedRemoteHosts($hosts);
        }

        $chroot = $options->getChroot();

        if (count($chroot) === 1 && realpath((string) $chroot[0]) === realpath(base_path())) {
            $options->setChroot($this->previewChroot());
        }
    }

    public function allowSelfSignedCertificates(Dompdf $dompdf): void
    {
        $current = $dompdf->getHttpContext();

        $context = stream_context_create(array_replace_recursive(
            is_resource($current) ? stream_context_get_options($current) : [],
            [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true,
                ],
            ],
        ));

        $dompdf->setHttpContext($context);
    }

    /**
     * Without a public/ folder public_path() is the project root, which would allow everything.
     *
     * @return array<int, string>
     */
    protected function previewChroot(): array
    {
        $base = realpath(base_path());

        $paths = [
            public_path(),
            plugins_path(),
            themes_path(),
            base_path('modules'),
            base_path('app'),
            storage_path('app/uploads/public'),
            storage_path('app/public'),
            storage_path('app/media'),
            storage_path('app/resources'),
            storage_path('temp/public'),
        ];

        return array_values(array_filter($paths, fn (string $path): bool => realpath($path) !== $base));
    }

    /**
     * @return array<int, string>
     */
    protected function applicationHosts(): array
    {
        $urls = SiteManager::instance()->listEnabled()
            ->filter(fn (SiteDefinition $site): bool => (bool) $site->is_custom_url)
            ->pluck('app_url')
            ->push(config('app.url'))
            ->all();

        return array_values(array_filter(array_map(
            fn ($url): string => mb_strtolower($this->hostOf((string) $url)),
            $urls,
        )));
    }

    protected function hostOf(string $url): string
    {
        return (string) (parse_url($url, PHP_URL_HOST) ?: parse_url('//' . ltrim($url, '/'), PHP_URL_HOST));
    }
}
