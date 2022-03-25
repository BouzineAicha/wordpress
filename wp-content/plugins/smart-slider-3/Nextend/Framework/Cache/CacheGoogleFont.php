<?php

namespace Nextend\Framework\Cache;

use Nextend\Framework\Filesystem\Filesystem;
use Nextend\Framework\Misc\HttpClient;

class CacheGoogleFont extends AbstractCache {

    protected $_storageEngine = 'filesystem';

    public function __construct() {
        parent::__construct('googlefonts', true);
    }

    /**
     * @param string $url
     *
     * @return boolean|string The path of the cached file
     */
    public function makeCache($url, $extension) {

        $hash = $this->generateHash($url);

        $fileName              = $hash;
        $fileNameWithExtension = $fileName . '.' . $extension;

        $isCached = $this->exists($fileNameWithExtension);

        if ($isCached) {
            if (!$this->testManifestFile($fileName)) {
                $isCached = false;
            }
        }

        if (!$isCached) {

            $cssContent = HttpClient::get($url);

            if (!$cssContent) {
                return false;
            }

            if ($extension === 'css') {
                $cssContent = preg_replace_callback('/url\(["\']?(.*?\.woff2)["\']?\)/i', function ($matches) {

                    $url = $matches[1];

                    $cache = new CacheGoogleFont();

                    $path = $cache->makeCache($url, 'woff2');

                    if ($path) {
                        $url = Filesystem::pathToAbsoluteURL($path);
                    }

                    return 'url(' . $url . ')';
                }, $cssContent);
            }

            $this->set($fileNameWithExtension, $cssContent);

            $this->createManifestFile($fileName);
        }

        return $this->getPath($fileNameWithExtension);
    }

    private function generateHash($url) {
        return md5($url);
    }

    protected function testManifestFile($fileName) {
        $manifestKey = $this->getManifestKey($fileName);
        if ($this->exists($manifestKey)) {

            $manifestData = json_decode($this->get($manifestKey), true);

            if ($manifestData['mtime'] > strtotime('-30 days')) {
                return true;
            }
        }

        return false;
    }

    protected function createManifestFile($fileName) {

        $this->set($this->getManifestKey($fileName), json_encode($this->getManifestData()));
    }

    private function getManifestData() {

        return array(
            'mtime' => time()
        );
    }

    protected function getManifestKey($fileName) {
        return $fileName . '.manifest';
    }
}