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
                $table->addColumn("id", "integer",
                    array('autoincrement' => true));
                $table->setPrimaryKey(array("id"));
                $table->addColumn("url", "text");
                $table->addColumn("item", "text");
                $table->addColumn("time", "datetime",
                    array('default' => 'CURRENT_TIMESTAMP'));
                $table->addUniqueIndex(array("url", "item"));
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

    public function fetch_feed($url) {
        $cache_path = $this->app['resources']->getPath('cache') . '/simplepie';
        if (!file_exists($cache_path)) {
            mkdir($cache_path, 0777, true);
        }

        $feed = new \SimplePie();
        $feed->set_cache_location($cache_path);
        $feed->set_feed_url($url);
        $feed->init();
        $feed->handle_content_type();

        echo "feed title: " . $feed->get_title();

        $rv = array();

        foreach ($feed->get_items() as $item) {
            $rv[] = array(
                'feed' => $url,
                'id' => $item->get_id(),
                'title' => $item->get_title(),
                'description' => $item->get_description(),
                'date' => $item->get_date('j F Y | g:i a'),
            );
        }

        return $rv;
    }

    public function visit_item($item) {
        $table_name = $this->get_table_name();
        $rv = false;

        $db = $this->app['db'];
        $db->beginTransaction();
        try {
            $stmt = $db->prepare(
                'SELECT count(*) as count FROM ' . $table_name .
                ' WHERE url = ? AND item = ?'
            );
            $stmt->bindValue(1, $item['feed']);
            $stmt->bindValue(2, $item['id']);
            $stmt->execute();
            $seen = $stmt->fetchAll()[0]['count'] > 0;

            if(! $seen) {
                $db->insert($table_name, array(
                    'url' => $item['feed'],
                    'item' => $item['id'],
                ));
                $this->create_cms_item($item);
                $rv = true;
            }

            $db->commit();
        } catch(Exception $e) {
            $db->rollback();
            throw $e;
        }

        return $rv;
    }

    public function create_cms_item($item) {
    }

    public function scrape_feed($url) {
        $count = 0;
        foreach ($this->fetch_feed($url) as $item) {
            if($this->visit_item($item)) {
                $count += 1;
            }
        }
        return $count;
    }

    public function scrape_all() {
        $url_list = array(
            'https://medium.com/feed/message',
        );

        $rv = array();
        foreach($url_list as $url) {
            $count = $this->scrape_feed($url);
            $rv[] = array("url" => $url, "count" => $count);
        }
        return $rv;
    }

    public function view(Silex\Application $app, Request $request) {
        $report = "";
        foreach($this->scrape_all() as $feed) {
            $report .= "" . $feed['count'] . " " . $feed['url'] . "\n";
        }
        ;
        return "<code><pre>" . $report . "</code></pre>\n";
    }

}
