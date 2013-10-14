<?php namespace Api\Controller;

use Slim\Slim;
use Model;
use Validate;

/**
 * Database login api methods
 *
 * @license MIT http://opensource.org/licenses/MIT
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @package Controller
 * @subpackage Database
 */
class Database
{

    /**
     * Returns list of database credentials
     * @url /api/database/:domain_id
     * @method GET
     */
    public static function listAction($domain_id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $domain = $mgr->domainExists($domain_id);
            if (!$domain) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "Domain not found")));
                return $response;
            }
            $mgr = new Model\Database();
            $results = $mgr->getDBLogins($domain_id);
            $response->body(json_encode($results));
            return $response;
        } catch (Exception $e) {
            $app->getLog()->error("Error listing databases for domain " . $domain_id . ". - " . $e->getMessage());
            $response->status(500);
            $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
            return $response;
        }
    }

    /**
     * Returns details for database credentials
     * @url /api/database/:domain_id/:id
     * @method GET
     */
    public static function detailsAction($domain_id, $id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $domain = $mgr->domainExists($domain_id);
            if (!$domain) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "Domain not found")));
                return $response;
            }
            $mgr = new Model\Database();
            $results = $mgr->getDBDetails($domain_id, $id);
            if (!$results) {
                $response->status(404);
                $response->body(
                    json_encode(array('success' => false, 'message' => "Database login credentials not found"))
                );
                return $response;
            }
            return $response->body(json_encode($results));
        } catch (Exception $e) {
            $app->getLog()->error(
                "Error showing database {$id} for domain " . $domain_id . ". - " . $e->getMessage()
            );
            $response->status(500);
            $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
            return $response;
        }
    }

    /**
     * Adds new database credentials
     * @url /api/database/:domain_id
     * @method POST
     */
    public static function addAction($domain_id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $domain = $mgr->domainExists($domain_id);
            if (!$domain) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "Domain not found")));
                return $response;
            }
            $data = ($app->request()->getMediaType() == 'application/json') ? json_decode(
                $app->request()->getBody(),
                true
            ) : $app->request()->post();
            $mgr = new Model\Database();
            $results = $mgr->addDB($domain_id, $data);
            $app->getLog()->info("Database {$results['id']} added for domain " . $domain_id . ".");
            $response->status(201);
            $response->body(json_encode($results));
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
                $app->getLog()->error("Error adding database for domain " . $domain_id . ". - " . $e->getMessage());
                $response->status(500);
                $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
                return $response;
            }
        }
    }

    /**
     * Updates database credentials
     * @url /api/database/:domain_id/:id
     * @method PUT
     */
    public static function updateAction($domain_id, $id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $domain = $mgr->getDomain($domain_id);
            if (!$domain) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "Domain not found.")));
                return $response;
            }
            $mgr = new Model\Database();
            $results = $mgr->getDBDetails($domain_id, $id);
            if (!$results) {
                $response->status(404);
                $response->body(
                    json_encode(array('success' => false, 'message' => "Database login credentials not found"))
                );
                return $response;
            }
            $data = ($app->request()->getMediaType() == 'application/json') ? json_decode(
                $app->request()->getBody(),
                true
            ) : $app->request()->post();
            $results = array_merge($results, array_intersect_key($data, $results));
            $results = $mgr->updateDB($id, $results, $domain_id);
            $app->getLog()->info("Database {$id} updated for domain " . $domain_id . ".");
            $response->status(200);
            $response->body(json_encode($results));
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
                $app->getLog()->error(
                    "Error updating database {$id} for domain " . $domain_id . ". - " . $e->getMessage()
                );
                $response->status(500);
                $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
                return $response;
            }
        }
    }

    /**
     * Deletes an admin login
     * @url /api/database/:domain_id/:id
     * @method DELETE
     */
    public static function deleteAction($domain_id, $id)
    {
        $app = Slim::getInstance();
        $response = $app->response();
        $response->header('Content-Type', 'application/json');
        try {
            $mgr = new Model\Domain();
            $domain = $mgr->getDomain($domain_id);
            if (!$domain) {
                $response->status(404);
                $response->body(json_encode(array('success' => false, 'message' => "Domain not found.")));
                return $response;
            }
            $mgr = new Model\Database();
            $results = $mgr->getDBDetails($domain_id, $id);
            if (!$results) {
                $response->status(404);
                $response->body(
                    json_encode(array('success' => false, 'message' => "Database login credentials not found"))
                );
                return $response;
            }
            $results = $mgr->deleteDB($domain_id, $id);
            $app->getLog()->info("Database {$id} deleted from domain " . $domain_id . ".");
            $response->status(204);
            $response->body(json_encode(array('success' => true, 'message' => 'Database login has been deleted.')));
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
                $app->getLog()->error(
                    "Error deleting database {$id} for domain " . $domain_id . ". - " . $e->getMessage()
                );
                $response->status(500);
                $response->body(json_encode(array('success' => false, 'message' => $e->getMessage())));
                return $response;
            }
        }
    }
}
