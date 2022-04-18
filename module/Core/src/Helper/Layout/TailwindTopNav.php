<?php
namespace Core\Helper\Layout;

use Core\Model\MenuModel;
use function _\filter;
use function _\flatMap;
use Laminas\Authentication\AuthenticationService;
use Laminas\Form\View\Helper\AbstractHelper;
use Laminas\Session\SessionManager;
use Laminas\Stdlib\ArrayUtils;

/**
 * This view helper class displays a menu bar.
 */

class TailwindTopNav extends AbstractHelper
{
    private $config;
    private $container;
    private $authService;
    private $sessionManager;
    public $menu = [];

    public static $PARENT_ICON = '<em class="ri-list-check ml-2 mr-1 text-lg text-gray-400 group-hover:text-gray-500"></em>';

    public static $PAGE_ICON = '<em class="ri-external-link-line mr-1 text-lg text-gray-400 group-hover:text-gray-500"></em>';

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

    private function getMenu($identity, $module)
    {
        $me = $this;
        /** @var MenuModel $menuModel */
        $menuModel = $me->container->get(\Core\Model\MenuModel::class);
        $tmp = $menuModel->getMenuByUidByLayoutByModule(['uid'=>$identity['id']??null, 'layout'=>'tailwind-topnav','module'=>$module]);
        // d($tmp);
        return $tmp;
    }

    private function restructureMenu(&$menu)
    {
        $tmp = $menu;
        // var_dump($menu);
        // die();
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
        // zdebug($lvl1parent);
        // die();
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
        // zdebug($topmenu);
        // die('qqq');
        $menu = $topmenu;
    }

    /** @var \Laminas\View\Renderer\PhpRenderer $lamView */
    public function generateMenu($identity, $module)
    {
        $me = $this;
        $menu = $me->getMenu($identity, $module);
        // d($identity, $module, $menu);
        // die();
        $me->restructureMenu($menu);
        // d($menu);die('aaa');
        $me->menu = $menu;
    }

    /** @var \Laminas\View\Renderer\PhpRenderer $lamView */
    public function generateTopMenu($lamView)
    {
        $me = $this;
        $html = '';

        foreach ($me->menu as $k=>$v) {
            if (!isset($v['child'])) {
                $icon = ($v['icon']===null)?$me::$PAGE_ICON:$v['icon'];
                $url = $v['url'];
                if ($v['route']!==null && $v['route_id']!==null) {
                    $_par = [];
                    if ($v['param']!==null) {
                        $_par = json_decode($v['param'], true);
                    }
                    $url = $lamView->url($v['route_name'], $_par);
                }

                $html .= '<div x-cloak class="py-1">
                <a href="'.$url.'"
                    class="group flex items-center px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 hover:text-gray-900" role="menuitem">
                    '.$icon.'
                    <span class="ml-1">'.$v['title'].'</span>
                </a>
                </div>';
            }
        }

        return $html;
    }

    private function generateSideMenuList($lamView, $menu, $key, $lvl)
    {
        $me = $this;
        $html = '';
        if (!isset($menu['child'])) {
            $icon = ($menu['icon']===null)?$me::$PAGE_ICON:$menu['icon'];
            $url = $menu['url'];
            if ($menu['route']!==null && $menu['route_id']!==null) {
                $_par = [];
                if ($menu['param']!==null) {
                    $_par = json_decode($menu['param'], true);
                }
                $url = $lamView->url($menu['route_name'], $_par);
            }

            $html .= '<div class="">
                <a href="'.$url.'"
                    class="'.(($lvl>1)?'w-full':'').' group whitespace-nowrap flex px-[7px] py-[4px] rounded-sm text-sm font-medium focus:outline-none hover:bg-bluegray-700 items-center hover:text-white '.(($lvl>1)?"text-bluegray-700":"text-bluegray-300").'" role="menuitem">
                    '.$icon.'
                    <span class="'.(($lvl>1)?'ml-2 py-1':'ml-1').' pr-2">'.$menu['title'].'</span>
                </a>
            </div>';
        } elseif (isset($menu['child'])) {
            $icon = ($menu['icon']===null)?$me::$PARENT_ICON:$menu['icon'];
            $html .= '<div x-cloak x-data="{ open_'.$menu['id'].': false }"
            @keydown.escape="open_'.$menu['id'].' = false"
            @click.outside="open_'.$menu['id'].' = false"
            @click="open_'.$menu['id'].' = true"
            class="'.(($lvl>1)?'w-full':'').' relative inline-block text-left">
            <div class="'.(($lvl>1)?'w-full':'').' " >
                <button type="button"
                class="'.(($lvl>1)?'w-full':'').' flex justify-between whitespace-nowrap hover:bg-bluegray-700 align-middle hover:text-white px-[7px] py-[4px] rounded-sm text-sm font-medium focus:outline-none"
                x-bind:class="{ \'bg-white\': open_'.$menu['id'].' ,\''.(($lvl>1)?'text-bluegray-700':'text-bluegray-300').'\': !open_'.$menu['id'].',\'text-bluegray-900\': open_'.$menu['id'].' }"
                id="options-menu" aria-haspopup="true" aria-expanded="true" x-bind:aria-expanded="open_'.$menu['id'].'">
                <div class="flex-none"><span class="align-middle">'.$icon.'</span>
                <span class="'.(($lvl>1)?'ml-2 py-1':'ml-1').' pr-2 align-middle">'.$menu['title'].'</span></div>
                '.(($lvl>1)?'<div class="ml-1"><i class="ri-arrow-right-s-line align-middle"></i></div>':'').'
                </button>
            </div>

            <div x-cloak x-show="open_'.$menu['id'].'"
                x-transition:enter="transition ease-out duration-100"
                x-transition:enter-start="transform-gpu opacity-0 scale-95"
                x-transition:enter-end="transform-gpu opacity-100 scale-100"
                x-transition:leave="transition ease-in duration-75"
                x-transition:leave-start="transform-gpu opacity-100 scale-100"
                x-transition:leave-end="transform-gpu opacity-0 scale-95"
                class="absolute '.(($lvl>1)?'transform-gpu origin-top-left translate-x-60 -translate-y-10 -m-2':'').' mt-2 rounded-md shadow-lg bg-white ring-1 ring-black ring-opacity-5 divide-y divide-gray-200 z-10"
                role="menu" aria-orientation="vertical" aria-labelledby="options-menu">';
            foreach ($menu['child'] as $k1=>$v1) {
                $html .= $me->generateSideMenuList($lamView, $v1, $k1, $lvl+1);
            }
            $html .= '</div>
            </div>';
        }
        return $html;
    }

    /** @var \Laminas\View\Renderer\PhpRenderer $lamView */
    public function generateSideMenu($lamView)
    {
        // d($identity, $module, $active_menu, $route_id);die();
        // zdebug(get_class($lamView));die();
        $me = $this;
        $html = '';

        foreach ($me->menu as $k=>$v) {
            $html .= $me->generateSideMenuList($lamView, $v, $k, 1);
        }
        // zdebug($html);
        // die();
        return $html;
        // return "";
    }

    /** @var \Laminas\View\Renderer\PhpRenderer $lamView */
    public function generateSideBar($lamView)
    {
        // d($identity, $module, $active_menu, $route_id);die();
        // zdebug(get_class($lamView));die();
        $me = $this;
        $html = '';

        foreach ($me->menu as $k=>$v) {
            //topmenu
            if (!isset($v['child'])) {
                $icon = ($v['icon']===null)?$me::$PAGE_ICON:$v['icon'];
                $url = $v['url'];
                if ($v['route']!==null && $v['route_id']!==null) {
                    $_par = [];
                    if ($v['param']!==null) {
                        $_par = json_decode($v['param'], true);
                    }
                    $url = $lamView->url($v['route_name'], $_par);
                }

                $html .= '<a href="'.$url.'"
          class="bg-white text-gray-600 hover:text-gray-900 hover:bg-gray-50 group flex items-center px-2 py-1 text-base font-medium rounded-md align-middle" id="sidemenu-0">
          '.$icon.$v['title'].'
        </a>';
            } else {
                $icon = ($v['icon']===null)?$me::$PARENT_ICON:$v['icon'];

                $html .= '<div x-cloak x-data="{ sidemenu_isExpanded_'.$v['id'].': false, menu_id: '.$v['id'].' }" class="space-y-1" x-bind:id="\'sidemenu-\'+menu_id">
              <button
                class="group w-full flex items-center pl-1 pr-1 py-2 text-base font-medium rounded-md bg-white text-gray-600 hover:text-gray-900 hover:bg-gray-50 focus:outline-none"
                @click.prevent="sidemenu_isExpanded_'.$v['id'].' = !sidemenu_isExpanded_'.$v['id'].'" x-bind:aria-expanded="sidemenu_isExpanded_'.$v['id'].'">
                '.$icon.'
                '.$v['title'].'
                <svg :class="{ \'text-gray-400 rotate-90\': sidemenu_isExpanded_'.$v['id'].', \'text-gray-300\': !sidemenu_isExpanded_'.$v['id'].' }"
                  x-state:on="Expanded" x-state:off="Collapsed"
                  class="ml-auto h-5 w-5 transform group-hover:text-gray-400 transition-colors ease-in-out duration-150 text-gray-300"
                  viewBox="0 0 20 20" aria-hidden="true">
                  <path d="M6 6L14 10L6 14V6Z" fill="currentColor"></path>
                </svg>
              </button>
              <div x-show="sidemenu_isExpanded_'.$v['id'].'" x-description="Expandable link section, show/hide based on state." class="space-y-1" style="display: none;">';

                foreach ($v['child'] as $k1=>$v1) {
                    if (!isset($v1['child'])) {
                        $icon = ($v1['icon']===null)?$me::$PAGE_ICON:$v1['icon'];
                        $url = $v1['url'];
                        if ($v1['route']!==null && $v1['route_id']!==null) {
                            $_par = [];
                            if ($v1['param']!==null) {
                                $_par = json_decode($v1['param'], true);
                            }
                            $url = $lamView->url($v1['route_name'], $_par);
                        }

                        $html .= '<a href="'.$url.'"
                  class="group w-full flex items-center pl-7 pr-2 py-2 text-sm font-medium text-gray-600 rounded-md hover:text-gray-900 bg-gray-50 hover:bg-gray-100">
                  '.$icon.'
                  '.$v1['title'].'
                </a>';
                    }
                }
                $html .= '</div>
        </div>';
            }
        }
        // !d($html);die();
        return $html;
        // return "";
    }
}
