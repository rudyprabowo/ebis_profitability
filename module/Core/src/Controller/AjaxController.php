<?php
declare(strict_types=1);
namespace Core\Controller;

use Laminas\Mvc\Controller\AbstractActionController;
use Zend\Debug\Debug;
use Laminas\View\Model\ViewModel; 
use Laminas\View\Model\JsonModel;
use Laminas\View\Model\FeedModel;
use Laminas\Feed\Writer\Feed;
use Laminas\Stdlib\ArrayUtils;
use Laminas\Authentication\AuthenticationService;
use Laminas\Http\PhpEnvironment\Request;
use Laminas\Http\PhpEnvironment\Response;

class AjaxController extends AbstractActionController
{
    private $container;
    private $config;
    
    public function __construct($container, $config){
        $me = $this;
        $me->container = $container;
        $me->config = $config;
    }

    public function generatorDeleteSQLAction(){
        $me = $this;
        $auth = $me->container->get(AuthenticationService::class);
        $ret = [
            "ret"=>false,
            'msg'=>'INVALID REQUEST',
            'data'=>[]
        ];

        /** @var Request $request */
        $request = $me->getRequest();
        /** @var Response $response */
        $response = $me->getResponse();
        if($auth->hasIdentity() && $request->isPost()){
            session_write_close();
            $pHeader = $me->params()->fromHeader();
            $pPost = $me->params()->fromPost();
            // $par = ArrayUtils::merge($pPost, $pHeader);
            // Debug::dump($pPost);//die();
            // Debug::dump($pHeader);die();

            if(isset($pHeader['X-Csrf-Token']) && isset($pHeader['X-Db-Conn']) && isset($pHeader['X-Db-Table'])){
                $data['csrf'] = $pHeader['X-Csrf-Token'];
                $csrfForm = $me->container->get('csrfForm');
                $csrfForm->setData($data);
                // !d($data,$csrfForm->isValid());die();
                // Debug::dump($csrfForm->isValid());die();
                if ($csrfForm->isValid()) {
                    $csrfForm = $me->container->get('csrfForm');
                    $newcsrf = $csrfForm->get('csrf')->getValue();
                    $ret = $me->DataGenerator()->deleteSQLExecute($pHeader['X-Db-Conn'], $pHeader['X-Db-Table'], $pPost);
                    $ret['csrf'] = $newcsrf;
                }else{
                    $ret['msg']='INVALID CSRF';
                }
            }
        }else{
            $file = __METHOD__;
            $file = preg_replace("/[^a-zA-Z0-9_]/", "_", $file);
            $me->Logging()->alertLogging("ajax",$file,$me);
            $response->setStatusCode(HTTP_UNAUTHORIZED);
        }
        
        $viewModel = new JsonModel();
        $viewModel->setVariables($ret);
        return $viewModel;
    }

    public function callmodeluploadAction()
    {
        $me = $this;
        $auth = $me->container->get(AuthenticationService::class);
        $ret = [
            "ret"=>false,
            'msg'=>'INVALID REQUEST',
            'data'=>[]
        ];

        /** @var Request $request */
        $request = $me->getRequest();
        /** @var Response $response */
        $response = $me->getResponse();
        if((env("BYPASS",false)===true || $auth->hasIdentity()) && $request->isPost()){
            session_write_close();
            // Debug::dump($auth->getIdentity());die();
            // !d($me->params());die();
            $pFiles = $me->params()->fromFiles();
            $pHeader = $me->params()->fromHeader();
            $pRoute = $me->params()->fromRoute();
            $pQuery = $me->params()->fromQuery();
            $pPost = $me->params()->fromPost();
            // d($pFiles,$pHeader,$pRoute,$pQuery,$pPost);die();
            // !d($pRoute);

            if (isset($pRoute['app']) && isset($pRoute['func'])) {
                try {
                    $app = $pRoute['app'];
                    $mdl = "UploadModel";
                    $func = $pRoute['func'];
                    $cls = $app."\\Model\\".$mdl;
                    // !d($cls,$func);die();
                    // $model = $me->container->get(\App\Model\MenuModel::class);
                    $model = $me->container->get($cls);
                    // !d(get_class($model));die();
                    $exist = method_exists($model, $func);
                    // !d($exist);die();
                    if ($exist) {
                        $par = ArrayUtils::merge($pFiles, $pPost, $pQuery);
                        // !d($par);die();
                        $ret = [
                            'ret'=>true,
                            'msg'=>'Success Request',
                            'data'=>$model->{$func}($par)
                        ];
                    }
                } catch (\Exception $e) {
                } catch (\ArgumentCountError $e) {
                }
            }
        }else{
            $file = __METHOD__;
            $file = preg_replace("/[^a-zA-Z0-9_]/", "_", $file);
            $me->Logging()->alertLogging("ajax",$file,$me);
            $response->setStatusCode(HTTP_UNAUTHORIZED);
        }
        $viewModel = new JsonModel();
        // $viewModel->setVariable('items', $items);
        $viewModel->setVariables($ret);
        return $viewModel;
    }

    public function callmodelAction()
    {
        $me = $this;
        $auth = $me->container->get(AuthenticationService::class);
        $ret = [
            "ret"=>false,
            'msg'=>'INVALID REQUEST',
            'data'=>[]
        ];
        // zdebug(env("BYPASS",false)===true);die();
        // zdebug($auth->hasIdentity());die();
        /** @var Request $request */
        $request = $me->getRequest();
        /** @var Response $response */
        $response = $me->getResponse();
        if((env("BYPASS",false)===true || $auth->hasIdentity()) && $request->isPost()){
            session_write_close();
            // Debug::dump($auth->getIdentity());die();
            // !d($me->params()->fromPost());die();
            $pFiles = $me->params()->fromFiles();
            $pHeader = $me->params()->fromHeader();
            $pRoute = $me->params()->fromRoute();
            $pQuery = $me->params()->fromQuery();
            $pPost = $me->params()->fromPost();
            !d($pFiles,$pHeader,$pRoute,$pQuery,$pPost);die();
            // !d($pRoute);

            if (isset($pRoute['app']) && isset($pRoute['model']) && isset($pRoute['func'])) {
                try {
                    $app = $pRoute['app'];
                    $mdl = $pRoute['model'];
                    $func = $pRoute['func'];
                    $cls = $app."\\Model\\".$mdl;
                    // !d($cls,$func);die();
                    // $model = $me->container->get(\App\Model\MenuModel::class);
                    $model = $me->container->get($cls);
                    // !d(get_class($model));die();
                    $exist = method_exists($model, $func);
                    // !d($exist);die();
                    if ($exist) {
                        $par = ArrayUtils::merge($pPost, $pQuery);
                        // !d($par);die();
                        $ret = [
                            'ret'=>true,
                            'msg'=>'Success Request',
                            'data'=>$model->{$func}($par)
                        ];
                        // !d($ret);die();
                    }
                } catch (\Exception $e) {
                } catch (\ArgumentCountError $e) {
                }
            }
        }else{
            $file = __METHOD__;
            $file = preg_replace("/[^a-zA-Z0-9_]/", "_", $file);
            $me->Logging()->alertLogging("ajax",$file,$me);
            $response->setStatusCode(HTTP_UNAUTHORIZED);
        }
        $viewModel = new JsonModel();
        // $viewModel->setVariable('items', $items);
        // !d($ret);die();
        // zdebug($ret);die();
        // unset($ret['data'][8]);
        $viewModel->setVariables($ret);
        return $viewModel;
    }
}