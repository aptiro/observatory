<?php

namespace AptiSuggest;

use Silex;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

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
    $to = $app['config']->get('general/apti_mail/recipients');
    mail($to, $subject, $text, "From: " . FROM_EMAIL);
}


class Suggest extends \Bolt\Content {

    public static function form(Request $request, Silex\Application $app) {
        if($request->getMethod() == 'POST') {
            $form = $request->request;

            $recaptcha_url = 'https://www.google.com/recaptcha/api/siteverify' .
              '?secret=' . $app['config']->get('general/apti_recaptcha/secret') .
              '&response=' . urlencode($form->get('g-recaptcha-response'));
            $recaptcha_resp = json_decode(file_get_contents($recaptcha_url), true);
            if(! $recaptcha_resp['success']) {
                return "Bad captcha";
            }

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
            'recaptcha_sitekey' => $app['config']->get('general/apti_recaptcha/sitekey'),
        ));
    }

}
