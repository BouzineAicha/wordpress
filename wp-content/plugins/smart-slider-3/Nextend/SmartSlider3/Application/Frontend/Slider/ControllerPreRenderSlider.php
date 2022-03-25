<?php


namespace Nextend\SmartSlider3\Application\Frontend\Slider;


use Nextend\Framework\Asset\Css\Css;
use Nextend\Framework\Controller\AbstractController;
use Nextend\Framework\ResourceTranslator\ResourceTranslator;

class ControllerPreRenderSlider extends AbstractController {


    public function actionIframe() {

        $sliderIDorAlias = isset($_GET['sliderid']) ? $_GET['sliderid'] : false;

        if (empty($sliderIDorAlias)) {
            echo 'Slider ID or alias is empty.';
        } else {
            Css::addStaticGroup(ResourceTranslator::toPath('$ss3-frontend$/dist/normalize.min.css'), 'normalize');


            $view = new ViewIframe($this);

            $view->setSliderIDorAlias($sliderIDorAlias);

            $view->display();
        }
    }
}