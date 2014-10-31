<?php
// AptiContent Extension for Bolt, by Victor Avasiloaei

namespace AptiContent;
use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Intl\Intl;

class Extension extends \Bolt\BaseExtension
{

    /**
     * Info block for AptiContent Extension.
     */
    function info()
    {
        $data = array(
            'name' => "AptiContent",
            'description' => "An extension for managing the APTI content, such as domains, subdomains and items",
            'author' => "Victor Avasiloaei",
            'version' => "0.1",
            'required_bolt_version' => "1.4",
            'highest_bolt_version' => "1.4",
            'type' => "General",
            'first_releasedate' => "2014-07-16",
            'latest_releasedate' => "2014-07-16",
            'dependencies' => "",
            'priority' => 10
        );

        return $data;

    }

    /**
     * Initialize AptiContent. Called during bootstrap phase.
     */
    function initialize()
    {
        $this->addTwigFunction('country_name', 'twigCountryName');
    }

    function twigCountryName($code) {
        $countries = Intl::getRegionBundle()->getCountryNames();
        $name = $countries[$code];
        if(! $name) { $name = $code; }
        return $name;
    }
}

class Domain extends \Bolt\Content
{
    function getAllDomainSubdomains($domainId) {
        $sqlSubdomains = "SELECT bolt_subdomains.*, bolt_relations.to_id AS domainId FROM bolt_subdomains
                          LEFT OUTER JOIN bolt_relations
                            ON bolt_subdomains.id = bolt_relations.from_id
                          WHERE
                            bolt_relations.to_id = $domainId
                            AND bolt_relations.from_contenttype = 'subdomains'
                            AND bolt_relations.to_contenttype = 'domains'
                          ORDER BY weight ASC";
        $subdomainsRaw = $this->app['db']->fetchAll($sqlSubdomains);
        $subdomains = array();
        foreach($subdomainsRaw as $subdomain) {
            $subdomains[$subdomain['id']] = $subdomain['title'];
        }
        return $subdomains;
    }

    function getDomainItems($domainId) {
        $subdomains = $this->getAllDomainSubdomains($domainId);
        $whereIn = !empty($subdomain) ? 'AND bolt_relatoins.to_id IN (' . implode(', ', array_keys($subdomains)) . ')' : '';
        $sqlItems   = "SELECT bolt_items.* FROM bolt_items
                       LEFT JOIN bolt_relations
                        ON bolt_items.id = bolt_relations.from_id
                       LEFT JOIN bolt_subdomains
                        ON bolt_relations.to_id = bolt_subdomains.id
                       WHERE
                        bolt_relations.from_contenttype = 'items'
                        AND bolt_relations.to_contenttype = 'subdomains'
                        $whereIn
                       GROUP BY bolt_items.id
                       ORDER BY weight DESC
        ";
        $itemsRaw = $this->app['db']->fetchAll($sqlItems);
        return $itemsRaw;
    }
}

class Subdomain extends \Bolt\Content
{
    function foo() {
        return "subdomain bar";
    }
}

function sorted_countries($raw_countries) {
    $country_list_first = [];
    $idx_global = array_search("Global", $raw_countries);
    if($idx_global !== false) {
        unset($raw_countries[$idx_global]);
        $country_list_first[] = "Global";
    }
    $idx_eu = array_search("EU", $raw_countries);
    if($idx_eu !== false) {
        unset($raw_countries[$idx_eu]);
        $country_list_first[] = "EU";
    }
    return array_merge($country_list_first, $raw_countries);
}

function feed($app, $id, $title, $item_list) {
    $app['twig.loader.filesystem']->addPath(__DIR__);
    $date_list = [];
    foreach($item_list as $item) {
        $date_list[] = $item['datechanged'];
    }

    return $app['render']->render('feed.twig', array(
        'feed_id' => $id,
        'feed_title' => $title,
        'feed_updated' => max($date_list),
        'item_list' => $item_list,
    ));
}

class Overview extends \Bolt\Content
{
    public static function index(Request $request, Silex\Application $app) {
        $query = "SELECT DISTINCT country FROM bolt_items where country != ''";
        $raw_countries = array();
        foreach($app['db']->fetchAll($query) as $row) {
            $raw_countries[] = $row['country'];
        }
        $country_list = sorted_countries($raw_countries);

        $query = "SELECT DISTINCT title FROM bolt_domains";
        $domain_list = array();
        foreach($app['db']->fetchAll($query) as $row) {
            $domain_list[] = $row['title'];
        }
        sort($domain_list);

        $query = (
            "SELECT bolt_items.*, bolt_domains.title as domain FROM bolt_items ".
            "LEFT JOIN bolt_relations ".
            "  ON bolt_relations.from_contenttype = 'items' ".
            "  AND bolt_items.id = bolt_relations.from_id ".
            "LEFT JOIN bolt_domains ".
            "  ON bolt_relations.to_contenttype = 'domains' ".
            "  AND bolt_relations.to_id = bolt_domains.id ".
            "WHERE bolt_items.status = 'published'"
        );
        $item_map = array();
        foreach($app['db']->fetchAll($query) as $row) {
            $country = $row['country'];
            $domain = $row['domain'];
            if($country && $domain) {
                $item = $app['storage']->getContent('items', array('id' => $row['id']));
                $item_map[$country][$domain][] = $item;
            }
        }

        $app['twig.loader.filesystem']->addPath(__DIR__);
        return $app['render']->render('apti_overview.twig', array(
            'country_list' => $country_list,
            'domain_list' => $domain_list,
            'item_map' => $item_map,
        ));
    }

    public static function index_feed(Request $request, Silex\Application $app) {
        $item_list = $app['storage']->getContent('items',
            array('limit' => 20, 'order' => 'datepublish desc'));
        $feed_id = "http://observatory.mappingtheinternet.eu/overview/feed.xml";
        $body = feed($app, $feed_id, "Policy Observatory", $item_list);

        return new Response($body, 200,
            array('Content-Type' => 'application/rss+xml; charset=utf-8',
                'Cache-Control' => 's-maxage=3600, public',
            )
        );
    }

    public static function more(Request $request, Silex\Application $app,
                                $domain, $country) {
        $query = (
            "SELECT distinct(bolt_items.id) FROM bolt_items ".
            "LEFT JOIN bolt_relations ".
            "  ON bolt_relations.from_contenttype = 'items' ".
            "  AND bolt_items.id = bolt_relations.from_id ".
            "LEFT JOIN bolt_domains ".
            "  ON bolt_relations.to_contenttype = 'domains' ".
            "  AND bolt_relations.to_id = bolt_domains.id ".
            "WHERE bolt_items.status = 'published'"
        );
        if($country != 'all') { $query .= " AND bolt_items.country = :country"; }
        if($domain != 'all') { $query .= " AND bolt_domains.title = :domain"; }
        $stmt = $app['db']->prepare($query);
        if($country != 'all') { $stmt->bindValue('country', $country); }
        if($domain != 'all') { $stmt->bindValue('domain', $domain); }
        $stmt->execute();

        $item_list = array();
        foreach($stmt->fetchAll() as $row) {
          $item_list[] = $app['storage']->getContent('items', array('id' => $row['id']));
        }

        $app['twig.loader.filesystem']->addPath(__DIR__);
        return $app['render']->render('apti_overview_more.twig', array(
            'item_list' => $item_list,
            'domain' => $domain,
            'country' => $country,
        ));
    }
}
