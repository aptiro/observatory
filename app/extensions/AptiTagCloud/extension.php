<?php

Namespace AptiTagCloud;

use Silex;

class Extension extends \Bolt\BaseExtension
{
    public function info()
    {
        $data = array(
            'name' => 'APTI-OBS Tag Cloud',
            'description' => 'Generating a tag cloud for APTI Observatory',
            'author' => 'Victor Avasiloaei'
        );
        return $data;
    }

    public function initialize()
    {
        $this->controller = new Controller($this->app);
        $this->addTwigFunction('tagCloud', 'twigTagCloud');
    }

    public function twigTagCloud()
    {
        return $this->controller->twigTagCloud();
    }

}

class Controller
{
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    public function twigTagCloud()
    {
        // Get all the tags
        $sqlTags = "SELECT id, slug, name, bolt_taxonomy.taxonomytype FROM bolt_taxonomy
                        WHERE taxonomytype='tags'";
        $tagsRaw = $this->app['db']->fetchAll($sqlTags);
        $tags = array();
        // Add them to an array and hold their data nicely
        foreach($tagsRaw as $tag) {
            if(in_array($tag['slug'], array_keys($tags))) {
                $tags[$tag['slug']]['nr']++;
            } else {
                $tags[$tag['slug']] = array(
                    'name' => $tag['name'],
                    'slug' => $tag['slug'],
                    'nr'   => 1
                );
            }
        }
        // Determine the maximum number of appeareances of a tag
        $max = 1;
        foreach($tags as $k => $tag) {
            if($tag['nr'] > $max) {
                $max = $tag['nr'];
            }
        }
        // Determine their font size in em
        foreach($tags as $k => $tag) {
            $tags[$k]['size'] = $tag['nr'] > 1 ? ( $tag['nr'] / 10 ) + 1 : 1;
        }
        
        return $tags;
    }

}
