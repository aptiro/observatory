<?php

Namespace AptiScraper;

use Silex;
use Symfony\Component\HttpFoundation\Request;


class Extension extends \Bolt\BaseExtension
{

    function info() {

        $data = array(
            'name' =>"APTI Feed Scraper",
        );

        return $data;

    }

    function initialize() {

        $this->controller = new Controller($this->app);

        $table_name = $this->controller->get_table_name();

        // CREATE TABLE 'bolt_aptiscraper_visited'
        $this->app['integritychecker']->registerExtensionTable(
            function ($schema) use ($table_name) {
                $table = $schema->createTable($table_name);
                $table->addColumn("id", "integer", array('autoincrement' => true));
                $table->setPrimaryKey(array("id"));
                $table->addColumn("key", "text");
                $table->addUniqueIndex(array("key"));
                return $table;
            }
        );

        $routes = array(
            array('', 'view', 'aptiscraperpage'),
        );

        $visitors_routes = $this->app['controllers_factory'];

        foreach ($routes as $route) {
            list($path, $method, $binding) = $route;
            $visitors_routes
                ->match($path, array($this->controller, $method))
                ->bind($binding);
        }
        $this->app->mount("/apti-scraper", $visitors_routes);

    }

}


class Controller
{
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    public function get_table_name() {
        $prefix = $this->app['config']->get('general/database/prefix', "bolt_");
        return $prefix . "aptiscraper_visited";
    }

    public function view(Silex\Application $app, Request $request) {
        $db = $this->app['db'];
        $res = $db->insert($this->get_table_name(), array('key' => "foo"));
        return "hello <em>scraper</em>!";
    }

}
