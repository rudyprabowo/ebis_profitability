<?php
namespace Core\Helper\Layout;

use Core\Model\MenuModel;
use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Authentication\AuthenticationService;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ArrayUtils;
use function _\filter;
use function _\flatMap;

/**
 * This view helper class displays a menu bar.
 */

class Cork extends AbstractHelper
{
  private $config;
  private $container;
  private $authService;
  private $sessionManager;

  public static $PARENT_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" ><path d="M20 22H4a1 1 0 0 1-1-1V8h18v13a1 1 0 0 1-1 1zm1-16H3V3a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v3zM7 11v4h4v-4H7zm0 6v2h10v-2H7zm6-5v2h4v-2h-4z"/></svg>';

  public static $PAGE_ICON = '<svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>';

  public static $LAYOUT_NAME = "cork";

  public function __construct($container, $config)
  {
      $me = $this;
      $me->container = $container;
      $me->config = $config;
      try {
          $me->authService = $container->get(AuthenticationService::class);
      } catch (\Exception $e) {
          $me->authService = null;
      }
      try {
          $me->sessionManager = $container->get(SessionManager::class);
      } catch (\Exception $e) {
          $me->sessionManager = null;
      }
  }

  private function getMenu($identity, $module){
    $me = $this;
    $menu = [];
    /** @var MenuModel $menuModel */
    $menuModel = $me->container->get(\Core\Model\MenuModel::class);
    $tmp = $menuModel->getMenuByUidByLayoutByModule(['uid'=>$identity['id'], 'layout'=>'cork','module'=>$module]);
    // d($tmp);
    return $tmp;
  }

  private function restructureMenu(&$menu){
      $tmp = $menu;
      $topmenu = filter($tmp, ['parent' => null]);
      $topparent = filter($tmp, ['parent' => null, 'url'=>'#0']);
      $topparentkey = flatMap($topparent, function ($n) {
          return $n['id'];
      });

      $lvl1menu = filter($tmp, function ($o) use ($topparentkey) {
          return in_array($o['parent'], $topparentkey);
      });
      $lvl1parent = filter($tmp, function ($o) {
          return $o['parent']!==null && $o['url']==="#1";
      });
      $lvl1parentkey = flatMap($lvl1parent, function ($n) {
          return $n['id'];
      });

      $lvl2menu = filter($tmp, function ($o) use ($lvl1parentkey) {
          return in_array($o['parent'], $lvl1parentkey);
      });
      $lvl2parent = filter($tmp, function ($o) {
          return $o['parent']!==null && $o['url']==="#2";
      });
      $lvl2parentkey = flatMap($lvl2parent, function ($n) {
          return $n['id'];
      });

      $lvl3menu = filter($tmp, function ($o) use ($lvl2parentkey) {
          return in_array($o['parent'], $lvl2parentkey);
      });

      // d($topmenu, $topparent, $topparentkey, $lvl1menu, $lvl1parent, $lvl1parentkey, $lvl2menu, $lvl2parent, $lvl2parentkey, $lvl3menu);//die();

      foreach ($topmenu as $k=>$v) {
          foreach ($lvl1menu as $k1=>$v1) {
            if ($v['id']===$v1['parent']) {
                foreach ($lvl2menu as $k2=>$v2) {
                    if ($v1['id']===$v2['parent']) {
                        foreach ($lvl3menu as $k3=>$v3) {
                            if ($v2['id']===$v3['parent']) {
                                if (!isset($lvl2menu[$k2]['child'])) {
                                    $lvl2menu[$k2]['child'] = [];
                                }
                                $lvl2menu[$k2]['child'][] = $v3;
                            }
                        }
                        if (!isset($lvl1menu[$k1]['child'])) {
                            $lvl1menu[$k1]['child'] = [];
                        }
                        $lvl1menu[$k1]['child'][] = $lvl2menu[$k2];
                    }
                }
                if (!isset($topmenu[$k]['child'])) {
                    $topmenu[$k]['child'] = [];
                }
                $topmenu[$k]['child'][] = $lvl1menu[$k1];
            }
          }
      }
      // d($topmenu);
      $menu = $topmenu;
  }

  /** @var \Laminas\View\Renderer\PhpRenderer $lamView */
  public function generateSideMenu($identity, $module, $active_menu, $route_id,$lamView){
    // d($identity, $module, $active_menu, $route_id);die();
    // zdebug(get_class($lamView));die();
    $me = $this;
    $menu = $me->getMenu($identity, $module);
    // d($menu);//die();
    $lastmenu = [];
    if(count($menu)>0){
        $lastmenu = $menu[count($menu)-1];
    }
    $me->restructureMenu($menu);
    // d($menu);die();
    $html = '';

    foreach($menu as $k=>$v){
      //topmenu
        $class = "menu";
        $aria_expanded = "false";
        if ($lastmenu['id']===$v['id']) {
            $class = "menu active";
            $aria_expanded = "true";
        }
        $url = $v['url'];
        if($v['route']!==null){
            $url = $lamView->url($v['route_name']);
            // zdebug($url);die();
        }
      if(!isset($v['child'])){
        $icon = ($v['icon']===null)?$me::$PAGE_ICON:$v['icon'];
        $html .= '<li class="'.$class.'" id="topmenu-'.$v['id'].'.">
            <a href="'.$url.'" aria-expanded="'.$aria_expanded.'" class="dropdown-toggle">
                <div class="">'.$icon.'<span> '.$v['title'].'</span></div>
            </a>
        </li>';
      }else{
          $icon = ($v['icon']===null)?$me::$PARENT_ICON:$v['icon'];
          $html .= '<li class="'.$class.'" id="topmenu-'.$v['id'].'.">
            <a href="#top_submenu'.$v['id'].'" data-toggle="collapse" aria-expanded="'.$aria_expanded.'" class="dropdown-toggle">
                <div class="">'.$icon.'<span> '.$v['title'].'</span></div>
                <div>
                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                </div>
            </a>
            <ul class="collapse submenu list-unstyled" id="top_submenu'.$v['id'].'" data-parent="#accordionExample">';
            
          foreach ($v['child'] as $k1=>$v1) {
              //lvl1
                $url = $v1['url'];
                if($v1['route']!==null){
                    $url = $lamView->url($v1['route_name']);
                    // zdebug($url);die();
                }
                $class = "";
                $aria_expanded = "false";
                if ($lastmenu['id']===$v1['id']) {
                    $class = "active";
                    $aria_expanded = "true";
                }
              if (!isset($v1['child'])) {
                  $icon = ($v1['icon']===null)?$me::$PAGE_ICON:$v1['icon'];
                  $html .= '<li class="'.$class.'" id="lvl1menu-'.$v1['id'].'.">
                    <a href="'.$url.'" aria-expanded="'.$aria_expanded.'" class="dropdown-toggle lvl1">
                        <div class="">'.$icon.'<span> '.$v1['title'].'</span></div>
                    </a>
                </li>';
              } else {
                  $icon = ($v1['icon']===null)?$me::$PARENT_ICON:$v1['icon'];
                  $html .= '<li class="'.$class.'" id="lvl1menu-'.$v1['id'].'.">
                    <a href="#lvl1_submenu'.$v1['id'].'" data-toggle="collapse" aria-expanded="'.$aria_expanded.'" class="dropdown-toggle lvl1">
                        <div class="">'.$icon.'<span> '.$v1['title'].'</span></div>
                        <div>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                        </div>
                    </a>
                    <ul class="collapse submenu list-unstyled" id="lvl1_submenu'.$v1['id'].'" data-parent="#top_submenu'.$v['id'].'">';
                    
                  foreach ($v1['child'] as $k2=>$v2) {
                      //lvl2
                        $url = $v2['url'];
                        if($v2['route']!==null){
                            $url = $lamView->url($v2['route_name']);
                            // zdebug($url);die();
                        }
                        $class = "";
                        $aria_expanded = "false";
                        if ($lastmenu['id']===$v2['id']) {
                            $class = "active";
                            $aria_expanded = "true";
                        }
                      if (!isset($v2['child'])) {
                          $icon = ($v2['icon']===null)?$me::$PAGE_ICON:$v2['icon'];
                          $html .= '<li class="'.$class.'" id="lvl2menu-'.$v2['id'].'.">
                            <a href="'.$url.'" aria-expanded="'.$aria_expanded.'" class="dropdown-toggle lvl2">
                                <div class="">'.$icon.'<span> '.$v2['title'].'</span></div>
                            </a>
                        </li>';
                      } else {
                            $icon = ($v2['icon']===null)?$me::$PARENT_ICON:$v2['icon'];
                            $html .= '<li class="'.$class.'" id="lvl2menu-'.$v2['id'].'.">
                            <a href="#lvl2_submenu'.$v2['id'].'" data-toggle="collapse" aria-expanded="'.$aria_expanded.'" class="dropdown-toggle lvl2">
                                <div class="">'.$icon.'<span> '.$v2['title'].'</span></div>
                                <div>
                                    <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                </div>
                            </a>
                            <ul class="collapse submenu list-unstyled" id="lvl2_submenu'.$v2['id'].'" data-parent="#lvl1_submenu'.$v1['id'].'">';
                            
                            foreach ($v2['child'] as $k3=>$v3) {
                                //lvl3
                                $url = $v3['url'];
                                if($v3['route']!==null){
                                    $url = $lamView->url($v3['route_name']);
                                    // zdebug($url);die();
                                }
                                $class = "";
                                $aria_expanded = "false";
                                if ($lastmenu['id']===$v3['id']) {
                                    $class = "active";
                                    $aria_expanded = "true";
                                }
                                if (!isset($v3['child'])) {
                                    $icon = ($v3['icon']===null)?$me::$PAGE_ICON:$v3['icon'];
                                    $html .= '<li class="'.$class.'" id="lvl3menu-'.$v3['id'].'.">
                                    <a href="'.$url.'" aria-expanded="'.$aria_expanded.'" class="dropdown-toggle lvl3">
                                        <div class="">'.$icon.'<span> '.$v3['title'].'</span></div>
                                    </a>
                                </li>';
                                } else {
                                        $icon = ($v3['icon']===null)?$me::$PARENT_ICON:$v3['icon'];
                                        $html .= '<li class="'.$class.'" id="lvl3menu-'.$v3['id'].'.">
                                        <a href="#lvl3_submenu'.$v3['id'].'" data-toggle="collapse" aria-expanded="'.$aria_expanded.'" class="dropdown-toggle lvl3">
                                            <div class="">'.$icon.'<span> '.$v3['title'].'</span></div>
                                            <div>
                                                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
                                            </div>
                                        </a>
                                        <ul class="collapse submenu list-unstyled" id="lvl3_submenu'.$v3['id'].'" data-parent="#lvl2_submenu'.$v2['id'].'">';
                                        
                                        foreach ($v3['child'] as $k4=>$v4) {
                                            //lvl4
                                            $url = $v4['url'];
                                            if($v4['route']!==null){
                                                $url = $lamView->url($v4['route_name']);
                                                // zdebug($url);die();
                                            }
                                            $class = "";
                                            $aria_expanded = "false";
                                            if ($lastmenu['id']===$v4['id']) {
                                                $class = "active";
                                                $aria_expanded = "true";
                                            }
                                            if (!isset($v4['child'])) {
                                                $icon = ($v4['icon']===null)?$me::$PAGE_ICON:$v4['icon'];
                                                $html .= '<li class="'.$class.'" id="lvl4menu-'.$v4['id'].'.">
                                                <a href="'.$url.'" aria-expanded="'.$aria_expanded.'" class="dropdown-toggle lvl4">
                                                    <div class="">'.$icon.'<span> '.$v4['title'].'</span></div>
                                                </a>
                                            </li>';
                                            } else {
                                            }
                                        }
                                        $html .= '</ul>
                                    </li>';
                                }
                            }
                            $html .= '</ul>
                        </li>';
                      }
                  }
                  $html .= '</ul>
                </li>';
              }
          }
          $html .= '</ul>
        </li>';
      }
    }

    // <li class="menu">
    //     <a href="#submenu" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
    //         <div class="">
    //             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
    //             <span> Menu P 2</span>
    //         </div>
    //         <div>
    //             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
    //         </div>
    //     </a>
    //     <ul class="collapse submenu list-unstyled" id="submenu" data-parent="#accordionExample">
    //         <li>
    //             <a href="javascript:void(0);"> Submenu 1 </a>
    //         </li>
    //         <li>
    //             <a href="javascript:void(0);"> Submenu 2 </a>
    //         </li>                           
    //     </ul>
    // </li>

    // <li class="menu">
    //     <a href="#submenu2" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle">
    //         <div class="">
    //             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-file"><path d="M13 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V9z"></path><polyline points="13 2 13 9 20 9"></polyline></svg>
    //             <span> Menu 3</span>
    //         </div>
    //         <div>
    //             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
    //         </div>
    //     </a>
    //     <ul class="collapse submenu list-unstyled" id="submenu2" data-parent="#accordionExample">
    //         <li>
    //             <a href="javascript:void(0);"> Submenu 1 </a>
    //         </li>
    //         <li>
    //             <a href="#sm2" data-toggle="collapse" aria-expanded="false" class="dropdown-toggle"> Submenu 2 <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg> </a>
    //             <ul class="collapse list-unstyled sub-submenu" id="sm2" data-parent="#submenu2"> 
    //                 <li>
    //                     <a href="javascript:void(0);"> Sub-Submenu 1 </a>
    //                 </li>
    //                 <li>
    //                     <a href="javascript:void(0);"> Sub-Submenu 2 </a>
    //                 </li>
    //                 <li>
    //                     <a href="javascript:void(0);"> Sub-Submenu 3 </a>
    //                 </li>
    //             </ul>
    //         </li>
    //     </ul>
    // </li>

    // <li class="menu active">
    //     <a href="#starter-kit" data-toggle="collapse" aria-expanded="true" class="dropdown-toggle">
    //         <div class="">
    //             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-terminal"><polyline points="4 17 10 11 4 5"></polyline><line x1="12" y1="19" x2="20" y2="19"></line></svg>
    //             <span>Starter Kit</span>
    //         </div>
    //         <div>
    //             <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-chevron-right"><polyline points="9 18 15 12 9 6"></polyline></svg>
    //         </div>
    //     </a>
    //     <ul class="collapse submenu recent-submenu mini-recent-submenu list-unstyled show" id="starter-kit" data-parent="#accordionExample">
    //         <li class="active">
    //             <a href="starter_kit_blank_page.html"> Blank Page </a>
    //         </li>
    //         <li>
    //             <a href="starter_kit_boxed.html"> Boxed </a>
    //         </li>
    //         <li>
    //             <a href="starter_kit_collapsible_menu.html"> Collapsible </a>
    //         </li>
    //     </ul>
    // </li>';

    // !d($html);die();
    return $html;
    // return "";
  }
}