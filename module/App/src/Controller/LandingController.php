<?php
declare(strict_types = 1);

namespace App\Controller;

use CoreAdmin\Model\MenuModel;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\ViewModel;

class LandingController extends AbstractActionController
{
    private $container;
    private $config;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
    }

    public function welcomeAction()
    {
        return [];
    }
}
