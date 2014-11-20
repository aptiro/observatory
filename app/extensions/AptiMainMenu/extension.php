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
            'left' => array(
                array('label' => 'Home', 'path' => '/', 'class' => 'first'),
                array('label' => 'About', 'path' => '/page/about'),
                array('label' => 'News & Views', 'path' => '/overview'),
                array('label' => 'Suggest', 'path' => '/suggest/'),
            ),
            'right' => array(
                array('label' => 'Privacy', 'path' => '/page/privacy', 'class' => 'dropdown',
                    'submenu' => array(
                        array('label' => 'Privacy 1', 'path' => '/page/privacy-1'),
                        array('label' => 'Privacy 2', 'path' => '/page/privacy-2'),
                        array('label' => 'Privacy 3', 'path' => '/page/privacy-3'),
                    )
                ),
                array('label' => 'Intellectual Property Rights', 'path' => '/page/ipr', 'class' => 'dropdown',
                    'submenu' => array(
                        array('label' => 'IPR 1', 'path' => '/page/ipr-1'),
                        array('label' => 'IPR 2', 'path' => '/page/ipr-2'),
                        array('label' => 'IPR 3', 'path' => '/page/ipr-3'),
                    )),
                array('label' => 'Internet Governance', 'path' => '/page/ig', 'class' => 'dropdown',
                    'submenu' => array(
                        array('label' => 'IG 1', 'path' => '/page/ig-1'),
                        array('label' => 'IG 2', 'path' => '/page/ig-2'),
                        array('label' => 'IG 3', 'path' => '/page/ig-3'),
                    )),
                array('label' => 'Startups', 'path' => '/page/startups'),
            )
        );
        return $menu;
    }

}