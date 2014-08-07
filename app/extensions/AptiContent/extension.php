<?php
// AptiContent Extension for Bolt, by Victor Avasiloaei

namespace AptiContent;
use Silex;
use Symfony\Component\HttpFoundation\Request;

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

class Overview extends \Bolt\Content
{
    public static function index(Request $request, Silex\Application $app) {
        $query = "SELECT DISTINCT country FROM bolt_items where country != ''";
        $country_list = array();
        foreach($app['db']->fetchAll($query) as $row) {
            $country_list[] = $row['country'];
        }
        sort($country_list);

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
        foreach($app['db']->fetchAll($query) as $item) {
            $country = $item['country'];
            $domain = $item['domain'];
            if($country && $domain) {
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
}
