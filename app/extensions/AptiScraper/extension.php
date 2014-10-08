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
                $table->addColumn("time", "datetime");
                $table->addUniqueIndex(array("url", "item"));
                return $table;
            }
        );

        $routes = array(
            array('scrape', 'view', 'aptiscraper_scrape'),
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

    public function get_db_prefix() {
        return $this->app['config']->get('general/database/prefix', "bolt_");
    }

    public function get_table_name() {
        return $this->get_db_prefix() . "aptiscraper_visited";
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

        $error = $feed->error();
        if($error) {
            throw new \Exception($error);
        }

        $rv = array();

        foreach ($feed->get_items() as $item) {
            $rv[] = array(
                'feed' => $url,
                'id' => $item->get_id(),
                'url' => $item->get_permalink(),
                'title' => $item->get_title(),
                'description' => $item->get_description(),
                'date' => $item->get_date('j F Y | g:i a'),
            );
        }

        return $rv;
    }

    public function visit_item($item, $feed_id) {
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
                    'time' => date('Y-m-d H:i:s'),
                ));
                $this->create_cms_item($item, $feed_id);
                $rv = true;
            }

            $db->commit();
        } catch(Exception $e) {
            $db->rollback();
            throw $e;
        }

        return $rv;
    }

    public function create_cms_item($item, $feed_id) {
        $app = $this->app;

        $content = $app['storage']->getContentObject('items');
        $content->setValues(array(
            'status' => 'draft',
            'title' => $item['title'],
            'url' => $item['url'],
            'feed_id' => $feed_id,
        ));
        $comment = "Scraping";
        $app['storage']->saveContent($content, $comment);

        $app['log']->add($content->getTitle(), 3, $content, 'save content');
    }

    public function scrape_feed($url, $feed_id) {
        $count = 0;
        foreach ($this->fetch_feed($url) as $item) {
            if($this->visit_item($item, $feed_id)) {
                $count += 1;
            }
        }
        return $count;
    }

    public function save_feed_error($id, $error) {
        $db = $this->app['db'];
        $table_name = $this->get_db_prefix() . 'feeds';
        $db->executeUpdate(
            'UPDATE ' . $table_name . ' SET errors = ? WHERE id = ?',
            array($error, $id));
    }

    public function scrape_all() {
        $db = $this->app['db'];
        $table_name = $this->get_db_prefix() . 'feeds';
        $stmt = $db->prepare('SELECT id, url FROM ' . $table_name);
        $stmt->execute();

        $rv = array();
        foreach($stmt->fetchAll() as $feed) {
            $id = $feed['id'];
            $url = $feed['url'];
            $error = "";
            try {
                $result = $this->scrape_feed($url, $id);
            }
            catch(\Exception $e) {
                $result = 'error';
                $error = $e->getMessage();
            }
            $this->save_feed_error($id, $error);
            $rv[] = array("url" => $url, "result" => $result);
        }
        return $rv;
    }

    public function view(Silex\Application $app, Request $request) {
        if($request->getMethod() != 'POST') {
            return "<h2>scrape</h2><form method=post><input name=key><input type=submit></form>";
        }
        $scraping_key = $this->app['config']->get('general/scraping_key');
        if($request->get('key') != $scraping_key) {
            $app->abort(403, "Invalid API key");
        }

        $report = "";
        foreach($this->scrape_all() as $feed) {
            $report .= "" . $feed['result'] . " " . $feed['url'] . "\n";
        }
        ;
        return "<code><pre>\n" . $report . "</code></pre>\n";
    }

}
