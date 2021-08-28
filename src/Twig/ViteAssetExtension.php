<?php
namespace Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class ViteAssetExtension extends AbstractExtension
{
    private $isDev;
    private $manifest;
    private $manifestData = null;

    public function __construct($isDev, $manifest)
    {
        $this->isDev = $isDev;
        $this->manifest = $manifest;
    }

    public function getFunctions()
    {
        return [
            new TwigFunction(
                'vite_asset',
                [$this, 'asset'],
                ['is_safe' => ['html']]
            )
        ];
    }

    public function asset($entry, $deps) {
        if ($this->isDev) {
            return $this->assetDev($entry, $deps);
        }

        return $this->assetProd($entry);
    }

    public function assetDev($entry, $deps) {
        $html = <<<HTML
<script type="module" src="http://localhost:3000/assets/@vite/client"></script>
HTML;

        if(in_array('react', $deps)) {
            $html .= '<script type="module">
              import RefreshRuntime from "http://localhost:3000/assets/@react-refresh";
              RefreshRuntime.injectIntoGlobalHook(window);
              window.$RefreshReg$ = () => {};
              window.$RefreshSig$ = () => type => type;
              window.__vite_plugin_react_preamble_installed__ = true;
             </script>';
        }

        $html .= <<<HTML
    <script type="module" src="http://localhost:3000/assets/{$entry}" defer></script>
HTML;

        return $html;
    }

    public function assetProd($entry) {
        if(!$this->manifestData) {
            $this->manifestData = json_decode(file_get_contents($this->manifest), true);
        }

        $file = $this->manifestData[$entry]['file'];

        $css = [];
        if (array_key_exists('css', $this->manifestData[$entry])) {
            $css = $this->manifestData[$entry]['css'];
        }

        $imports = [];
        if (array_key_exists('imports', $this->manifestData[$entry])) {
            $imports = $this->manifestData[$entry]['imports'];
        }

        $html = <<<HTML
<script type="module" src="/assets/{$file}" defer></script>
HTML;

        foreach ($css as $cssFile) {
            $html .= <<<HTML
<link rel="stylesheet" media="screen" href="/assets/{$cssFile}"> 
HTML;
        }

        foreach ($imports as $import) {
            $html .= <<<HTML
<link rel="modulepreload" href="/assets/{$import}"> 
HTML;
        }

        return $html;

    }

}
