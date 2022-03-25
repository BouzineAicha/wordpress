<?php


namespace Nextend\SmartSlider3\Platform\WordPress\Integration\TatsuBuilder;

use Nextend\SmartSlider3\Platform\WordPress\Shortcode\Shortcode;

class TatsuBuilder {

    public function __construct() {
        if (class_exists('Tatsu_Builder') && isset($_REQUEST['action']) && $_REQUEST['action'] == 'tatsu_module' && isset($_REQUEST['module'])) {
            $tatsuModuleData = json_decode($_REQUEST['module']);
            if ($tatsuModuleData && is_object($tatsuModuleData) && isset($tatsuModuleData->name) && $tatsuModuleData->name === 'tatsu_text_with_shortcodes') {
                Shortcode::forceIframe('TatsuBuilder', true);
            }
        }
    }
}