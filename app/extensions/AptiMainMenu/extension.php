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
            array('label' => 'Privacy', 'path' => '/page/privacy'),
            array('label' => 'IPR', 'path' => '/page/ipr'),
            array('label' => 'IG', 'path' => '/page/ig'),
            array('label' => 'Startups', 'path' => '/page/startups'),
            array('label' => 'Suggest', 'path' => '/page/suggest'),
        );
        return $menu;
    }

}