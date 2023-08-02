<?php

namespace Packetery\Facade\Admin;

use Packetery\Engine\Grid\DataGrid;
use Packetery\Engine\Link;
use Packetery\Engine\TwigCustomFilter;
use Packetery\Engine\TwigCustomFunctions;

class Template {

    /** @var array */
    private $params = [];

    /** @var array */
    private $breadcrumbs = [];

    /** @var \Language */
    private $language;

    /** @var \Loader */
    private $load;

    /** @var Link */
    private $link;

    /** @var \Session */
    private $session;

    /** @var \Document */
    private $document;

    /** @var TwigCustomFunctions */
    private $twigCustomFunctions;

    /** @var TwigCustomFilter */
    private $twigCustomFilter;

    /**
     * @param \Language $language
     * @param \Loader $load
     * @param \Session $session
     * @param \Document $document
     * @param Link $link
     * @param TwigCustomFunctions $twigCustomFunctions
     */
    public function __construct(
        \Language $language,
        \Loader $load,
        \Session $session,
        \Document $document,
        Link $link,
        TwigCustomFunctions $twigCustomFunctions,
        TwigCustomFilter $twigCustomFilter
    ) {
        $this->language = $language;
        $this->load = $load;
        $this->session = $session;
        $this->document = $document;
        $this->link = $link;
        $this->initBreadcrumbs();
        $this->twigCustomFunctions = $twigCustomFunctions;
        $this->twigCustomFilter = $twigCustomFilter;
    }

    /**
     * @param string $name
     * @param mixed $value
     * @return void
     */
    public function addParameter($name, $value) {
        $this->params[$name] = $value;
    }

    /**
     * @return array
     */
    public function getParameters() {
        return $this->params;
    }

    /**
     * @param string $name
     * @param string $href
     * @return void
     */
    public function addBreadcrumb($name, $href = null) {
        $this->breadcrumbs[] = [
            'text' => $name,
            'href' => $href,
        ];
    }

    /**
     * @return array
     */
    public function getBreadcrumbs() {
        return $this->breadcrumbs;
    }

    /**
     * @param string $title
     * @return void
     */
    public function setTitle($title) {
        $this->document->setTitle($title);
    }

    /**
     * @param string $filePath
     * @return string
     * @throws \Exception
     */
    public function render($filePath) {

        $fileName = is_file($filePath) ? basename($filePath) : $filePath . '.twig';
        $pathTemplates = DIR_SYSTEM . 'library/Packetery/Facade/Admin/Templates/';

        $files = [
            $fileName => is_file($filePath) ? $filePath : $pathTemplates . $fileName,
            'layout.twig' => $pathTemplates . 'layout.twig',
        ];

        $contents = [];
        foreach ($files as $key => $file) {
            if (is_file($file)) {
                $contents[$key] = file_get_contents($file);
            } else {
                $message = 'File %s with template does not exist!';
                throw new \Exception(sprintf($message, $file));
                exit();
            }
        }

        $translations = $this->language->all();
        foreach ($translations as $key => $translation) {
            $this->addParameter($key, $translation);
        }

        // initialize Twig environment
        $config = [
            'autoescape' => false,
            'debug' => false,
            'auto_reload' => true,
            'cache' => DIR_CACHE . 'template/'
        ];

        $this->addParameter('breadcrumbs', $this->getBreadcrumbs());
        $this->loadAdminTemplateParts();

        // check if some error/success messages are stored in session and set it as template parameters
        $templateParameters = [
            //TODO: všechny typy necháváme jen pro účely kompatibily, později zbyde jen flashMessages
            'success',
            'error_warning',
            'error_warning_multirow',
            'alert_info',
            'alert_info_heading',
            'api_key_validation_error',
            'flashMessage',
            'flashMessages'
        ];

        foreach ($templateParameters as $templateParameter) {
            if (isset($this->session->data[$templateParameter])) {
                $this->addParameter($templateParameter, $this->session->data[$templateParameter]);
                unset($this->session->data[$templateParameter]);
            }
        }

        try {
            $loader = new \Twig\Loader\ArrayLoader($contents);
            $twig = new \Twig\Environment($loader, $config);

            $twig->addFunction(new \Twig\TwigFunction('urlAdmin', function ($action, $params) {

                return $this->twigCustomFunctions->urlAdmin($action, $params);
            }));

            $twig->addFunction(new \Twig\TwigFunction('grid', function (DataGrid $grid) {

                return $this->twigCustomFunctions->grid($grid);
            }));

            $twig->addFilter(new \Twig\TwigFilter('translate', function ($key) {
                return $this->twigCustomFilter->translate($key);
            }));

            return $twig->render($fileName, $this->getParameters());
        } catch (\Exception $e) {
            $message = 'Error: Could not load template %s ! %s';
            trigger_error(sprintf($message, $fileName, $e->getMessage()), E_USER_ERROR);
            exit();
        }
    }

    /**
     * @return void
     */
    private function loadAdminTemplateParts() {
        $this->addParameter('header', $this->load->controller('common/header'));
        $this->addParameter('column_left', $this->load->controller('common/column_left'));
        $this->addParameter('footer', $this->load->controller('common/footer'));
    }

    /**
     * @return void
     */
    private function initBreadcrumbs() {
        $this->addBreadcrumb($this->language->get('text_home'), $this->link->createAdminLink('common/dashboard'));
        $this->addBreadcrumb($this->language->get(Facade::TEXT_TITLE_MAIN));
    }
}
