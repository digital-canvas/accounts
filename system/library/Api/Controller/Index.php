<?php namespace Api\Controller;

use Slim\Slim;

/**
 * List api methods
 *
 * @license MIT http://opensource.org/licenses/MIT
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @package Controller
 * @subpackage API
 */
class Index
{

    public static function listAction()
    {
        $app = Slim::getInstance();
        $response = $app->response;
        $response->headers->set('Content-Type', 'application/json');
        $response->setBody(json_encode(array('success' => true, 'methods' => array('api/domain'))));
        return $response;
    }
}
