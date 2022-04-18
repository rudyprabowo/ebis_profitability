<?php
declare(strict_types = 1);
namespace Core;

use Laminas\Mvc\MvcEvent;

class Render
{
    /**
     * setViewContent function
     *
     * @param MvcEvent $event
     * @return void
     */
    // SECTION setViewContent
    public function setViewContent(MvcEvent $event)
    {
        // !d($event->getError());//die('aaa');
        // # get matched route
        $routeMatch = $event->getRouteMatch();
        // !d($routeMatch);die();
        if ($routeMatch !== null) { // ? matched route not null
            // !d($routeMatch->getParams());
            // # get controller name
            $controller_name = $routeMatch->getParam('controller', null);
            // # get module name
            $module_name = substr($controller_name, 0, strpos($controller_name, '\\'));
            // # lowercase module name
            $module_name2 = preg_replace('/\B([A-Z])/', '-$1', $module_name);
            $module = strtolower($module_name2);
            // !d($module_name,$module);
            // # get ViewModel
            $viewModel = $event->getViewModel();
            // !d($viewModel);
            // # get view model template/layout
            $layout = $viewModel->getTemplate();
            // !d($layout);
            // die();
            // # split template/layout name
            $layout = explode("/", $layout);
            // # get last array
            $layout = $layout[count($layout) - 1];

            $ini_reader = new \Laminas\Config\Reader\Ini();
            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
            $layout_conf = $conf['layout'];
            // INFO layout === main => _DEFAULT_LAYOUT_
            $layout = ($layout === "main") ? $layout = $layout_conf['default'] : $layout;
            // !d($layout);
            // die();
            if ($controller_name !== null) { // ? controller name not null
                // INFO loop viewModel children
                foreach ($viewModel->getChildren() as $key => $value) {
                    if ($value->captureTo() === "content") { // ? children capture is content
                        // !d($viewModel->getVariables());
                        // !d($value->getVariables());
                        $value->setVariables($viewModel->getVariables());
                        // # get current view content
                        $template_ori = $value->getTemplate();
                        // !d($template_ori);
                        // !d($module);
                        // die();
                        // # replace module/view => module/layout/view
                        $template_tmp = str_replace($module . '/', $module . '/' . $layout . '/', $template_ori);
                        // !d($template_tmp);
                        // die();
                        // # create view absolute path
                        $filepath = APP_PATH . 'module/' . $module_name . '/view/' . $template_tmp . '.phtml';
                        // # create layout absolute path
                        $layoutpath = APP_PATH . 'views/templates/layout/' . $layout . '.phtml';
                        // zdebug($layoutpath);
                        // zdebug(file_exists($layoutpath));
                        // zdebug($filepath);
                        // zdebug(file_exists($filepath));
                        // die('aaa');
                        if (file_exists($layoutpath) && file_exists($filepath)) { // INFO layout exist AND view (layout) exist
                            // # set content view
                            $value->setTemplate($template_tmp);
                            // #set view layout
                            $viewModel->setTemplate($layout);
                        } elseif (file_exists($layoutpath) && !file_exists($filepath) && $routeMatch->getParam("action") !== "not-found") { // INFO layout exist AND view (layout) not exist AND action is not 'not found'
                            // die('qqq');
                            // !d($template_ori,$layout);die();
                            // $value->setVariable('aaa','zzz');
                            // zdebug(get_class_methods($value));
                            // zdebug($value->getVariable('aaa'));die('aaa');
                            // # set content view
                            $value->setTemplate($template_ori);
                            // #set view layout
                            if ($template_ori==="error/except") {
                                // zdebug(get_class_methods($viewModel));die();
                                $viewModel->setTemplate("blank-page");
                                $viewModel->terminate();
                            } else {
                                $viewModel->setTemplate($layout);
                            }
                        } else {
                            $ini_reader = new \Laminas\Config\Reader\Ini();
                            $conf = $ini_reader->fromFile(conf_path() . env('APPLICATION_ENV', 'development') . ".conf");
                            $layout_conf = $conf['layout'];

                            // # replace module/view => module/default_layout/view
                            $template_tmp = str_replace($module . '/', $module . '/' . $layout_conf['default'] . '/', $template_ori);
                            // # create view absolute path
                            $filepath = APP_PATH . '/module/' . $module_name . '/view/' . $template_tmp . '.phtml';
                            // # get default layout
                            $layout = $layout_conf['default'];
                            // # create layout absolute path
                            $layoutpath = APP_PATH . '/views/templates/layout/' . $layout . '.phtml';
                            // # set content view
                            $viewModel->setTemplate($layout);
                            // !d($layoutpath,$filepath);die();
                            if (file_exists($layoutpath) && file_exists($filepath)) {
                                $value->setTemplate($template_tmp);
                            } elseif ($routeMatch->getParam("action") !== "not-found") {
                                $value->setTemplate($template_ori);
                            } else {
                                // die('aaa');
                                $viewModel->setTemplate($layout_conf['default']);
                            }
                        }
                        break;
                    }
                }
            }
        }
    }
    // !SECTION setViewContent
}
