<?php
declare (strict_types = 1);

namespace App\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\JsonModel;

class ExampleController extends AbstractActionController
{
    private $container;
    private $config;

    public function __construct($container, $config)
    {
        $me = $this;
        $me->container = $container;
        $me->config = $config;
    }

    public function indexAction(){
      // die('aaa');
      $mdl_menu = $this->container->get(\App\Model\MenuModel::class);
      $menus = $mdl_menu->getAllMenu();
      // zdebug($menus);die();

      return [
        'list_menu'=>$menus
      ];
      
      // $viewModel = new JsonModel();
      // $viewModel->setVariables($menus);
      // return $viewModel;
    }
}