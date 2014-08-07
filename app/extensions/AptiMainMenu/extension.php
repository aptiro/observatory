<?php

Namespace AptiMainMenu;

use Silex;

class Extension extends \Bolt\BaseExtension
{
    public function info()
    {
        $data = array(
            'name' => 'APTI-OBS Main Menu',
            'description' => 'Showing a dynamic main menu for APTI Observatory',
            'author' => 'Victor Avasiloaei'
        );
        return $data;
    }

    public function initialize()
    {
        $this->controller = new Controller($this->app);
        $this->addTwigFunction('mainmenu', 'twigMainmenu');
    }

    public function twigMainmenu()
    {
        return $this->controller->twigMainMenu();
    }

}

class Controller
{
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    public function twigMainmenu()
    {
        $menu = array(
            array('label' => 'Home', 'path' => '/', 'class' => 'first'),
            array('label' => 'Overview', 'path' => '/overview'),
        );

        $sqlDomains = "SELECT * FROM bolt_domains ORDER BY weight ASC";
        $domains = $this->app['db']->fetchAll($sqlDomains);

        foreach($domains as $domain) {
            // Declaring it here as it might be used later...
            $submenu = array();
            $menu[] = array(
                'label' => $domain['title'],
                'path' => '/domain/' . $domain['slug'],
                'submenu' => !empty($submenu) ? $submenu : false
            );
        }
        $menuLast = array(
            array('label' => 'Startups', 'path' => '/page/startups'),
            array('label' => 'Suggest', 'path' => '/page/suggest')
        );
        foreach($menuLast as $item) {
            array_push($menu, $item);
        }

        foreach($menu as $k => $item) {
            if(!empty($item['submenu'])) {
                $menu[$k]['class'] = 'dropdown';
            }
        }

        return $menu;
    }

}