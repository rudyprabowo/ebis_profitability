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

class ISMLeftNav extends AbstractHelper
{
    private $config;
    private $container;
    private $authService;
    private $sessionManager;
    public $menu = [];

    public static $PARENT_ICON = '<svg class="h-5 w-5"fill=none stroke=currentColor viewBox="0 0 24 24"xmlns=http://www.w3.org/2000/svg><path d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"stroke-linecap=round stroke-linejoin=round stroke-width=2 /></svg>';

    public static $PAGE_ICON = '<svg class="h-5 w-5"fill=none stroke=currentColor viewBox="0 0 24 24"xmlns=http://www.w3.org/2000/svg><path d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"stroke-linecap=round stroke-linejoin=round stroke-width=2 /></svg>';

    public static $LAYOUT_NAME = "ism-leftnav";

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
        $tmp = $menuModel->getMenuByUidByLayoutByModule(['uid'=>$identity['id']??null, 'layout'=>$me::$LAYOUT_NAME,'module'=>$module]);
        // zdebug($tmp);
        // die();
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
        // zdebug($menu);
        // die('aaa');
        $me->menu = $menu;
    }

    private function generateTopMenuList($lamView, $menu, $key, $lvl)
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

            $html .='
            <a
              href="'.$url.'"
              class="flex items-center p-2 text-gray-500 transition-colors rounded-md dark:text-light hover:bg-primary-100 dark:hover:bg-primary"
              role="button"
              aria-haspopup="true"
            >
            <span aria-hidden="true">'.$icon.'</span>
            <span class="ml-2 text-sm">'.$menu['title'].'</span>
            </a>';
        } elseif (isset($menu['child'])) {
            $icon = ($menu['icon']===null)?$me::$PARENT_ICON:$menu['icon'];
            $html .= '<div x-data="{ isActive: false, open: false}">
            <a
              href="#"
              @click="$event.preventDefault(); open = !open"
              class="flex items-center p-2 text-gray-500 transition-colors rounded-md dark:text-light hover:bg-primary-100 dark:hover:bg-primary"
              :class="{\'bg-primary-100 dark:bg-primary\': isActive || open}"
              role="button"
              aria-haspopup="true"
              :aria-expanded="(open || isActive) ? \'true\' : \'false\'"
            >
              <span aria-hidden="true">'.$icon.'</span>
              <span class="ml-2 text-sm">'.$menu['title'].'</span>
              <span class="ml-auto" aria-hidden="true">
                <svg
                  class="w-4 h-4 transition-transform transform"
                  :class="{ \'rotate-180\': open }"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </span>
            </a>
            <div role="menu" x-show="open" class="mt-2 space-y-2 pl-4" aria-label="'.$menu['title'].'">';
            foreach ($menu['child'] as $k1=>$v1) {
                $html .= $me->generateTopMenuList($lamView, $v1, $k1, $lvl+1);
            }
            $html .= '</div>
            </div>';
        }
        return $html;
    }

    /** @var \Laminas\View\Renderer\PhpRenderer $lamView */
    public function generateTopMenu($lamView)
    {
        $me = $this;
        $html = '';
        // zdebug($me->menu);
        // die();
        foreach ($me->menu as $k=>$v) {
            $html .= $me->generateTopMenuList($lamView, $v, $k, 1);
        }
        // zdebug($html);
        // die();
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

            $html .= '
            <a
              href="'.$url.'"
              class="flex items-end p-2 text-gray-500 transition-colors rounded-md dark:text-light hover:bg-primary-100 dark:hover:bg-primary"
              role="button"
            >
              <span aria-hidden="true">'.$icon.'</span>
              <span class="ml-2 text-sm">'.$menu['title'].'</span>
            </a>';
        } elseif (isset($menu['child'])) {
            $icon = ($menu['icon']===null)?$me::$PARENT_ICON:$menu['icon'];
            $html .= '<div x-data="{ isActive: false, open: false}">
              <a
                href="#"
                @click="$event.preventDefault(); open = !open"
                class="flex items-end p-2 text-gray-500 transition-colors rounded-md dark:text-light hover:bg-primary-100 dark:hover:bg-primary"
                :class="{\'bg-primary-100 dark:bg-primary\': isActive || open}"
                role="button"
                aria-haspopup="true"
                :aria-expanded="(open || isActive) ? \'true\' : \'false\'"
              >
              <span aria-hidden="true">'.$icon.'</span>
              <span class="ml-2 text-sm">'.$menu['title'].'</span>
              <span class="ml-auto" aria-hidden="true">
                <svg
                  class="w-4 h-4 transition-transform transform"
                  :class="{ \'rotate-180\': open }"
                  xmlns="http://www.w3.org/2000/svg"
                  fill="none"
                  viewBox="0 0 24 24"
                  stroke="currentColor"
                >
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" />
                </svg>
              </span>
            </a>
            <div role="menu" x-show="open" class="mt-2 space-y-2 pl-4" aria-label="'.$menu['title'].'">';
            foreach ($menu['child'] as $k1=>$v1) {
                $html .= $me->generateSideMenuList($lamView, $v1, $k1, $lvl+1);
            }
            $html .= '</div>
            </div>';
        }
        return $html;
    }

    /** @var \Laminas\View\Renderer\PhpRenderer $lamView */
    public function generateSideBar($lamView)
    {
        $me = $this;
        $html = '';
        // zdebug($me->menu);
        // die();
        foreach ($me->menu as $k=>$v) {
            $html .= $me->generateSideMenuList($lamView, $v, $k, 1);
        }
        // zdebug($html);
        // die();
        return $html;
    }
}
