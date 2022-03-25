<?php

namespace Nextend\SmartSlider3\Slider\SliderType\Block;

use Nextend\Framework\Asset\Js\Js;
use Nextend\Framework\Data\Data;
use Nextend\Framework\Sanitize;
use Nextend\Framework\View\Html;
use Nextend\SmartSlider3\Slider\SliderType\AbstractSliderTypeFrontend;

class SliderTypeBlockFrontend extends AbstractSliderTypeFrontend {

    public function getDefaults() {
        return array(
            'background'       => '',
            'background-size'  => 'cover',
            'background-fixed' => 0,
            'slider-css'       => '',
            'border-width'     => 0,
            'border-color'     => '3E3E3Eff',
            'border-radius'    => 0,

            'kenburns-animation' => ''
        );
    }

    protected function renderType($css) {

        $params = $this->slider->params;

        Js::addStaticGroup(SliderTypeBlock::getAssetsPath() . '/dist/ss-block.min.js', 'ss-block');

        $this->jsDependency[] = 'ss-block';

        $sliderCSS = $params->get('slider-css');

        $this->initSliderBackground('.n2-ss-slider-1');

        $this->initParticleJS();

        echo $this->openSliderElement();
        ob_start();

        $slide = $this->slider->getActiveSlide();
        $slide->finalize();
        ?>

        <div class="n2-ss-slider-1 n2-ow" style="<?php echo Sanitize::esc_attr($sliderCSS); ?>">
            <div class="n2-ss-slider-2 n2-ow">
                <?php
                echo $this->getBackgroundVideo($params);

                echo Html::tag('div', array('class' => 'n2-ss-slide-backgrounds n2-ow-all'), $slide->background);
                ?>
                <div class="n2-ss-slider-3 n2-ow">
                    <?php
                    $this->displaySizeSVGs($css);

                    echo $this->slider->staticHtml;

                    echo Html::tag('div', Html::mergeAttributes($slide->attributes, $slide->linkAttributes, array(
                        'class' => 'n2-ss-slide n2-ow ' . $slide->classes,
                        'style' => $slide->style
                    )), $slide->getHTML());
                    ?>
                </div>
                <?php
                $this->renderShapeDividers();
                ?>
            </div>
        </div>
        <?php
        echo $this->widgets->wrapSlider(ob_get_clean());
        echo $this->closeSliderElement();

        $this->style .= $css->getCSS();
    }

    public function getScript() {
        return "_N2.r(" . json_encode(array_unique($this->jsDependency)) . ",function(){new _N2.SmartSliderBlock('{$this->slider->elementId}', " . $this->encodeJavaScriptProperties() . ");});";
    }

    /**
     * @param $params Data
     */
    public function limitParams($params) {

        $params->loadArray(array(
            'controlsScroll'            => 0,
            'controlsDrag'              => 0,
            'controlsTouch'             => 0,
            'controlsKeyboard'          => 0,
            'blockCarouselInteraction'  => 1,
            'autoplay'                  => 0,
            'autoplayStart'             => 0,
            'widget-arrow-enabled'      => 0,
            'widget-bullet-enabled'     => 0,
            'widget-autoplay-enabled'   => 0,
            'widget-indicator-enabled'  => 0,
            'widget-bar-enabled'        => 0,
            'widget-thumbnail-enabled'  => 0,
            'widget-fullscreen-enabled' => 0,
            'randomize'                 => 0,
            'randomizeFirst'            => 0,
            'randomize-cache'           => 0,
            'maximumslidecount'         => 1,
            'imageload'                 => 0,
            'imageloadNeighborSlides'   => 0,
            'maintain-session'          => 0,
            'global-lightbox'           => 0
        ));
    }
}