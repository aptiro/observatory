<?php

namespace AptiSuggest;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use \Mandrill;

const SITE_URL = "http://observatory.mappingtheinternet.eu";
const FROM_EMAIL = "noreply@observatory.mappingtheinternet.eu";


class Extension extends \Bolt\BaseExtension
{

    function info() {
        $data = array(
            'name' => "AptiSuggest",
        );

        return $data;

    }

    function initialize() {
    }
}


function send_mail($app, $subject, $text) {
    $key = $app['config']->get('general/apti_mail/key');
    $to = $app['config']->get('general/apti_mail/recipients');
    $mandrill = new Mandrill($key);
    $mandrill->messages->send(array(
        'from_name' => "Policy Observatory Notifier",
        'from_email' => FROM_EMAIL,
        'to' => $to,
        'subject' => $subject,
        'text' => $text,
    ));
}


class Suggest extends \Bolt\Content {

    public static function form(Request $request, Silex\Application $app) {
        if($request->getMethod() == 'POST') {
            $form = $request->request;
            $title = $form->get('title');
            $content = $app['storage']->getContentObject('items');
            $content->setValues(array(
                'status' => 'draft',
                'title' => $title,
                'url' => $form->get('url'),
                'description' => $form->get('description'),
            ));
            $app['storage']->saveContent($content, "Suggestion");
            $app['log']->add($content->getTitle(), 3, $content, 'save content');

            $admin_url = SITE_URL . "/bolt/editcontent/items/{$content->id}";
            $subject = "Article suggested on Policy Observatory";
            send_mail($app, $subject, $admin_url);

            $app['session']->getFlashBag()->set('info', "Article '{$title}' has been saved.");
        }

        $app['twig.loader.filesystem']->addPath(__DIR__);
        return $app['render']->render('form.twig', array(
            'url' => $request->query->get('url'),
            'title' => $request->query->get('title'),
            'description' => $request->query->get('description'),
        ));
    }

}
