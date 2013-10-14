<?php namespace Api\Controller;

use Slim\Slim;
use Model;
use Validate;

/**
 * FTP logins api methods
 *
 * @license MIT http://opensource.org/licenses/MIT
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @package Controller
 * @subpackage FTP
 */
class FTP
{

    /**
     * Returns ftp login data
     * @url /api/ftp/:domain_id
     * @method GET
     */
    public static function detailsAction($domain_id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $domain = $mgr->domainExists($domain_id);
            if (!$domain) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "Domain {$domain_id} not found")));
                return $response;
            }
            $mgr = new Model\FTP();
            $results = $mgr->getFTPDetails($domain_id);
            if (!$results) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "FTP login credentials not found for domain " . $domain['name'])));
                return $response;
            }
            return $response->body(json_encode($results));
        } catch (Exception $e) {
            $app->getLog()->error("Error showing ftp info for domain " . $domain_id . ". - " . $e->getMessage());
            $response->status(500);
            $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
            return $response;
        }
    }

    /**
     * Updates an ftp login
     * @url /api/ftp/:domain_id
     * @method PUT
     */
    public static function updateAction($domain_id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $domain = $mgr->getDomain($domain_id, false);
            if (!$domain) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "Domain not found.")));
                return $response;
            }
            $mgr = new Model\FTP();
            $results = $mgr->getFTPDetails($domain_id);
            if (!$results) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "FTP login credentials not found")));
                return $response;
            }
            $data = ($app->request()->getMediaType() == 'application/json') ? json_decode(
                $app->request()->getBody(),
                true
            ) : $app->request()->post();
            $results = array_merge($results, array_intersect_key($data, $results));
            $results = $mgr->updateFTP($domain_id, $results);
            $app->getLog()->info("FTP info updated for domain " . $domain['name'] . ".");
            $response->status(200);
            $response->body(json_encode($results));
            return $response;
        } catch (Exception $e) {
            if ($e instanceof Validate\Exception) {
                $response->status(400);
                $response->body(
                    json_encode(
                        array(
                             'success' => false,
                             'message' => $e->getMessage(),
                             'errors' => $e->getErrors()->getErrors()
                        )
                    )
                );
                return $response;
            } else {
                $app->getLog()->error(
                    "Error updating ftp info for domain " . $domain_id . ". - " . $e->getMessage()
                );
                $response->status(500);
                $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
                return $response;
            }
        }
    }
}
