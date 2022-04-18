<?php
declare (strict_types = 1);

namespace CoreAdmin\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
use Core\Form\CsrfForm;
use Laminas\Form\Element\Hidden as HiddenForm;

class IndexController extends AbstractActionController
{
    public function homeAction()
    {
        return [];
    }
}
