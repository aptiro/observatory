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
                array('label' => 'Startups', 'path' => '/page/startups'),
                array('label' => 'Privacy', 'path' => '/page/privacy', 'class' => 'dropdown',
                    'submenu' => array(
                        array('label' => 'Data Retention Legislation Europe', 'path' => '/page/data-retention-legislation-europe'),
                        array('label' => 'Personal Data Breaches Policies Europe', 'path' => '/page/personal-data-breaches-policies-europe'),
                        array('label' => 'Personal Data Security Policies Europe', 'path' => '/page/personal-data-security-policies-europe'),
                    )
                ),
                array('label' => 'Intellectual Property Rights', 'path' => '/page/ipr', 'class' => 'dropdown',
                    'submenu' => array(
                        array('label' => 'Basic Intellectual Property Legislation Europe', 'path' => '/page/basic-intellectual-property-legislation-europe'),
                        array('label' => 'EU Orphan Works Directive Implementation', 'path' => '/page/eu-orphan-works-directive-implementation'),
                        array('label' => 'Internet Blocking IPR Enforcement', 'path' => '/page/internet-blocking-ipr-enforcement'),
                    )),
                array('label' => 'Internet Governance', 'path' => '/page/ig', 'class' => 'dropdown',
                    'submenu' => array(
                        array('label' => 'European ccTLDds Management Table', 'path' => '/page/european-cctlds-management-table'),
                        array('label' => 'European Actors Internet Governance', 'path' => '/page/european-actors-internet-governance'),
                        array('label' => 'Net Neutrality Policies', 'path' => '/page/net-neutrality-policies'),
                    )),
            )
        );
        return $menu;
    }

}