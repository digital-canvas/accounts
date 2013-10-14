<?php namespace Api\Controller;

use Slim\Slim;
use Model;
use Validate;

/**
 * Domain API methods
 *
 * @license MIT http://opensource.org/licenses/MIT
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @package Controller
 * @subpackage Domain
 */
class Domain
{

    /**
     * Returns number of Domains
     * @url /api/domain/count
     * @method GET
     */
    public static function countAction()
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        $search = trim($app->request()->get('search'));
        if ($search == '') {
            $search = null;
        }
        try {
            $mgr = new Model\Domain();
            $count = $mgr->countDomains($search);
            $response->body(json_encode(array("count" => $count)));
            return $response;
        } catch (\Exception $e) {
            $app->getLog()->error("Error counting domains. - " . $e->getMessage());
            $response->status(500);
            $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
            return $response;
        }
    }

    /**
     * Returns list of Domains
     * @url /api/domain
     * @method GET
     */
    public static function listAction()
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        $limit = (int) $app->request()->get('limit');
        if ($limit <= 0) {
            $limit = null;
        }
        $offset = (int) $app->request()->get('offset');
        if ($offset < 0) {
            $offset = 0;
        }
        $sort = trim($app->request()->get('sort'));
        $search = trim($app->request()->get('search'));
        if ($search == '') {
            $search = null;
        }
        $dir = trim($app->request()->get('dir'));
        try {
            $mgr = new Model\Domain();
            $domains = $mgr->getDomainList($search, $limit, $offset, $sort, $dir);
            $response->body(json_encode($domains));
            return $response;
        } catch (\Exception $e) {
            $app->getLog()->error("Error listing domains. - " . $e->getMessage());
            $response->status(500);
            $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
            return $response;
        }
    }


    /**
     * Returns details for a domain
     * @url /api/domain/:id
     * @method GET
     */
    public static function detailsAction($id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $website = $mgr->getDomain($id);
            if (!$website) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => 'Requested URI is not found.')));
                return $response;
            }
            $response->body(json_encode($website));
            return $response;
        } catch (\Exception $e) {
            $app->getLog()->error("Error showing website {$id}. - " . $e->getMessage());
            $response->status(500);
            $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
            return $response;
        }
    }

    /**
     * Adds a new domain
     * @url /api/domain
     * @method POST
     */
    public static function addAction()
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $data = ($app->request()->getMediaType() == 'application/json') ? json_decode(
                $app->request()->getBody(),
                true
            ) : $app->request()->post();
            $domain = $mgr->addDomain($data);
            $app->getLog()->info("Domain {$domain['id']} added.");
            $response->status(201);
            $response->body(json_encode($domain));
            return $response;
        } catch (\Exception $e) {
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
                $app->getLog()->error("Error adding domain. - " . $e->getMessage());
                $response->status(500);
                $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
                return $response;
            }
        }
    }

    /**
     * Updates a domain
     * @url /api/domain/:id
     * @method PUT
     */
    public static function updateAction($id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $domain = $mgr->getDomain($id);
            if (!$domain) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "Domain not found.")));
                return $response;
            }
            $data = ($app->request()->getMediaType() == 'application/json') ? json_decode(
                $app->request()->getBody(),
                true
            ) : $app->request()->post();
            $domain = array_merge($domain, array_intersect_key($data, $domain));
            $domain = $mgr->updateDomain($id, $domain);
            $app->getLog()->info("Domain {$id} updated.");
            $response->status(200);
            $response->body(json_encode($domain));
            return $response;
        } catch (\Exception $e) {
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
                $app->getLog()->error("Error updating domain {$id}. - " . $e->getMessage());
                $response->status(500);
                $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
                return $response;
            }
        }
    }

    /**
     * Deletes a domain
     * @url /api/domain/:id
     * @method DELETE
     */
    public static function deleteAction($id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $domain = $mgr->getDomain($id);
            if (!$domain) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "Domain not found.")));
                return $response;
            }
            $domain = $mgr->deleteDomain($id);
            $app->getLog()->info("Domain {$id} deleted.");
            $response->status(204);
            $response->body(json_encode(array('success' => true, 'message' => 'Domain has been deleted.')));
            return $response;
        } catch (\Exception $e) {
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
                $app->getLog()->error("Error deleting domain {$id}. - " . $e->getMessage());
                $response->status(500);
                $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
                return $response;
            }
        }
    }
}
