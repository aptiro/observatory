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
        $this->addTwigFunction('allTags', 'twigGetTags');
    }

    public function twigTagCloud()
    {
        return $this->controller->twigTagCloud();
    }

    public function twigGetTags()
    {
        return $this->controller->twigTagCloud(false);
    }

}

class Controller
{
    public function __construct(Silex\Application $app)
    {
        $this->app = $app;
    }

    public function twigTagCloud($limit = 20)
    {
        $tags = $this->getTags();

        $totalTags = $limit && $limit > count($tags) ? $limit : count($tags);

        // Sort by the number of items
        usort($tags, function($a, $b) {
            return $b['nr'] - $a['nr'];
        });

        // Limit to a certain number of tags
        if($limit) {
            $tags = array_slice($tags, 0, $limit);
        }

        // Order them alphabetically
        usort($tags, function($a, $b) {
            return strcasecmp($a['name'], $b['name']);
        });

        // Determine the maximum number of appeareances of a tag
        $max = 1;
        foreach($tags as $k => $tag) {
            if($tag['nr'] > $max) {
                $max = $tag['nr'];
            }
            if(!isset($min) || $tag['nr'] < $min) {
                $min = $tag['nr'];
            }
        }

        // Determine their font size in em
        foreach($tags as $k => $tag) {
            $tags[$k]['size'] = $tag['nr'] > $min ? ( $totalTags / 20 / ($tag['nr'] - $min) ) + 1 : 1;
        }

        return $tags;
    }

    public function tags(Silex\Application $app)
    {
        $template = 'tags.twig';
        return $app['render']->render($template);
    }

    private function getTags()
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

        return $tags;
    }

}
