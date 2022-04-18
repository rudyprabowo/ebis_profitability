<?php
declare(strict_types = 1);

namespace App\Controller;

use Core\Form\CsrfForm;
use Laminas\Form\Element\Hidden as HiddenForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class IndexController extends AbstractActionController
{
    public function landingAction()
    {
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        return [
            'app_name'=>$app_conf['app_name'],
            'copy_owner'=>$app_conf['copy_owner']
        ];
    }

    /*
    npx tailwindcss -i ./src/css/App/Index/home/1_tailwind.css -o ./public/css/App/Index/home/1_tailwind.css --JIT --purge="./views/templates/layout/tailwind-topnav.phtml,./views/templates/layout/tailwind-topnav/*.phtml, ./module/App/view/app/index/home.phtml,./module/App/view/app/index/home/*.phtml" --watch
    */
    public function homeAction()
    {
        $params = $this->params()->fromRoute();
        // die('aaa');
        return ['params'=>$params];
    }

    public function homeEbisAction()
    {
        $params = $this->params()->fromRoute();
        die('home-ebis');
        return ['params'=>$params];
    }

    public function mainAction()
    {
        $params = $this->params()->fromRoute();
        // die('aaa');
        return ['params'=>$params];
    }

    public function termcondAction()
    {
        $ini_reader = new \Laminas\Config\Reader\Ini();
        $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
        $app_conf = $conf['app-config'];
        return [
            'app_name'=>$app_conf['app_name'],
            'copy_owner'=>$app_conf['copy_owner']
        ];
    }
}
