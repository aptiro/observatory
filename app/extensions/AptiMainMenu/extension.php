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
        return $this->controller->twigMainmenu();
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
            array('label' => 'Home', 'path' => 'homepage', 'class' => 'first'),
            array('label' => 'Overview', 'path' => 'overview'),
        );

        $sqlDomains = "SELECT * FROM bolt_domains ORDER BY weight ASC";
        $domains = $this->app['db']->fetchAll($sqlDomains);

        $sqlSubdomains = "SELECT bolt_subdomains.*, bolt_relations.to_id AS domainId FROM bolt_subdomains
                          LEFT OUTER JOIN bolt_relations
                            ON bolt_subdomains.id = bolt_relations.from_id
                          WHERE bolt_relations.from_contenttype = 'subdomains'
                            AND bolt_relations.to_contenttype = 'domains'
                          ORDER BY weight ASC";
        $subdomains = $this->app['db']->fetchAll($sqlSubdomains);

        foreach($domains as $domain) {
            $submenu = array();
            foreach($subdomains as $subdomain) {
                if($subdomain['domainid'] == $domain['id']) {
                    $submenu[] = array('label' => $subdomain['title'], 'path' => '/subdomain/' . $subdomain['slug']);
                }
            }
            $menu[] = array('label' => $domain['title'], 'path' => '/domain/' . $domain['slug'], 'submenu' => $submenu);
        }
        $menuLast = array(
            array('label' => 'Startups', 'path' => '/page/startups'),
            array('label' => 'Suggest', 'path' => '/page/suggest')
        );
        foreach($menuLast as $item) {
            array_push($menu, $item);
        }

        foreach($menu as $k => $item) {
            if(!empty($item['submenu'])) {
                $menu[$k]['class'] = 'dropdown';
            }
        }

        $html  = '<ul class="nav nav-pills nav-stacked">';
        foreach($menu as $item) {
            $html .= '<li class="' . $item['class'] . '">';
            $html .= '<a href="' . $item['path'] . '">';
            $html .= $item['label'];
            $html .= !empty($item['submenu']) ? '<span class="caret"></span>' : '';
            $html .= '</a>';
            if(!empty($item['submenu'])) {
                $html .= '<ul class="dropdown-menu role="menu">';
                foreach($item['submenu'] as $subitem) {
                    $html .= '<li class="' . $subitem['class'] . '">';
                    $html .= '<a href="' . $subitem['path'] . '">';
                    $html .= $subitem['label'];
                    $html .= '</a></li>';
                }
                $html .= '</ul>';
            }
            $html .= "</li>\n";
        }
        $html .= '</ul>';
/*
    {% for item in menu %}
    <li class="{{ item.class }} {% if item.submenu is defined %}dropdown{% endif %}">
        <a href="{{ item.link }}">
            {{ item.label }}
            {% if item.submenu is defined %}
                <span class="caret"></span>
            {% endif %}
        </a>
        {% if item.submenu is defined %}
            <ul class="dropdown-menu" role="menu">
                {% for item in item.submenu %}
                    <li class="{{ item.class }}">
                        <a href="{{ item.link }}">
                            {{ item.label }}
                        </a>
                    </li>
                {% endfor %}
            </ul>
        {% endif %}
    </li>
    {% endfor %}
</ul>
*/
        return new \Twig_Markup($html, 'UTF-8');
    }

}