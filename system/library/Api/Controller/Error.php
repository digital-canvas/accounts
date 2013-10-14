<?php namespace Api\Controller;

use Slim\Slim;

/**
 * Error controller
 *
 * @license MIT http://opensource.org/licenses/MIT
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @package Controller
 * @subpackage Error
 */
class Error
{

    public static function notfoundAction()
    {
        $app = Slim::getInstance();
        $response = $app->response;
        $response->setStatus(404);
        $response->headers->set('Content-Type', 'application/json');
        $response->setBody(json_encode(array('success' => false, 'message' => 'Requested URI is not found.')));
        return $response;
    }
}
