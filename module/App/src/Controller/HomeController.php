<?php
declare(strict_types = 1);

namespace App\Controller;

use Core\Form\CsrfForm;
use Laminas\Form\Element\Hidden as HiddenForm;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class HomeController extends AbstractActionController
{
    public function ebisAction()
    {
        $params = $this->params()->fromRoute();
        // die('Home EBIS');
        return ['params'=>$params];
    }

    public function divisiAction()
    {
        $params = $this->params()->fromRoute();
        if(!isset($params['div']) || $params['div']==''){
            $this->redirect()->toRoute("home-ebis");
        }
        // !s($params);die();
        // $segment = file_get_contents(APP_PATH.'data'.DS.'app'.DS.'telkom-divsegment.json');
        $_segment = json_decode(file_get_contents(APP_PATH.'data'.DS.'app'.DS.'telkom-segment.json'),true);
        $_divisi = $params['div'];
        // !s($_divisi);die();
        $_segment = $_segment[$_divisi]??[];
        // return ['params'=>$params, 'segment'=>json_decode($segment,true), 'detail_segment'=>json_decode($detail_segment,true)];
        // !s($_segment);die();
        return ['params'=>$params, 'divisi'=>$_divisi, 'segment'=>$_segment];
    }

    public function segmentAction()
    {
        $params = $this->params()->fromRoute();
        if(!isset($params['div']) || $params['div']=='' ||
            !isset($params['seg']) || $params['seg']==''){
            $this->redirect()->toRoute("home-ebis");
        }
        // !s($params);die();
        // $segment = file_get_contents(APP_PATH.'data'.DS.'app'.DS.'telkom-divsegment.json');
        $_segment = json_decode(file_get_contents(APP_PATH.'data'.DS.'app'.DS.'telkom-segment.json'),true);
        $_divisi = $params['div'];
        $_segment = $params['seg'];
        $mxPreview = file_get_contents(APP_PATH.'data'.DS.'app'.DS.'Preview Customer.xml');
        // !s($_divisi);die();
        // return ['params'=>$params, 'segment'=>json_decode($segment,true), 'detail_segment'=>json_decode($detail_segment,true)];
        // !s($_segment);die();
        return ['params'=>$params, 'divisi'=>$_divisi, 'segment'=>$_segment, 'mxPreview'=>$mxPreview];
    }

    public function regionalAction()
    {
        $params = $this->params()->fromRoute();
        $reg = "nasional";
        $witel = [];
        if(isset($params['reg']) && $params['reg']!==''){
            $reg = $params['reg'];
            $witel = json_decode(file_get_contents(APP_PATH.'data'.DS.'app'.DS.'telkom-regwitel.json'),true);
            $witel = $witel[$reg]??[];
        }
        // !s($params,$reg,$witel);die();
        return ['params'=>$params, 'reg'=>$reg, 'witel'=>$witel];
    }
}
