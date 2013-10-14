<?php namespace Model;

use PDO;
use Validate;

/**
 * Domain Model
 *
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @license MIT http://opensource.org/licenses/MIT
 * @package Model
 * @subpackage Domain
 */
class Domain extends BaseModel
{

    public static $default = array(
        'name' => null,
        'domain' => null,
        'url' => null,
        'notes' => null,
    );

    /**
     * Returns array of domains
     *
     * @param boolean $with_details If true child data is also returned.
     *
     * @return array
     */
    public function getDomains($with_details = true)
    {
        $sth = self::getConnection()->query("SELECT * FROM `domains` ORDER BY `name` ASC");
        if (!$with_details) {
            return $sth->fetchAll(PDO::FETCH_ASSOC);
        }
        $domains = array();
        $admin_sth = self::getConnection()->prepare("SELECT * FROM `admin_logins` WHERE `domain_id` = ?");
        $db_sth = self::getConnection()->prepare("SELECT * FROM `database_data` WHERE `domain_id` = ?");
        $ftp_sth = self::getConnection()->prepare("SELECT * FROM `ftp_data` WHERE `domain_id` = ?");
        while ($row = $sth->fetch(PDO::FETCH_ASSOC)) {
            $admin_sth->execute(array($row['id']));
            $row['admin'] = $admin_sth->fetchAll(PDO::FETCH_ASSOC);
            $cp_sth->execute(array($row['id']));
            $row['controlpanel'] = $cp_sth->fetchAll(PDO::FETCH_ASSOC);
            $db_sth->execute(array($row['id']));
            $row['database'] = $db_sth->fetchAll(PDO::FETCH_ASSOC);
            $ftp_sth->execute(array($row['id']));
            $row['ftp'] = $ftp_sth->fetchAll(PDO::FETCH_ASSOC);
            $domains[] = $row;
        }
        return $domains;
    }

    /**
     * Returns total number of domains
     *
     * @param string $search Search filter
     *
     * @return int
     */
    public function countDomains($search = null)
    {
        if (empty($search)) {
            $sth = self::getConnection()->query("SELECT COUNT(*) FROM `domains`");
        } else {
            $sth = self::getConnection()->prepare(
                "SELECT COUNT(*) FROM `domains` WHERE (`name` LIKE :search) OR (`domain` LIKE :search) OR (`url` LIKE :search)"
            );
            $searchstring = "%" . str_replace("%", "\\%", $search) . "%";
            $sth->bindValue(":search", $searchstring);
            $sth->execute();
        }
        $total = $sth->fetchColumn();
        $sth->closeCursor();
        return $total;
    }

    /**
     * Returns array of domains
     *
     * @param string $search Search filter
     * @param int $offset The number of records to skip
     * @param int $limit The maximum number of websites to return
     * @param string $sort id|name|domain|url The column to sort by
     * @param string $dir ASC|DESC The direction to sort
     *
     * @return array
     */
    public function getDomainList($search = null, $limit = 25, $offset = 0, $sort = 'name', $dir = 'ASC')
    {
        $sort = mb_strtolower($sort);
        if (!in_array($sort, array('id', 'name', 'domain', 'url'))) {
            $sort = 'name';
        }
        $dir = mb_strtoupper($dir);
        if (!in_array($dir, array('ASC', 'DESC'))) {
            $dir = 'ASC';
        }
        $limit = (int) $limit;
        $offset = (int) $offset;
        $query = "SELECT * FROM `domains`";
        if (is_null($search) || $search == '') {
            $search = null;
        } else {
            $search = "%" . str_replace("%", "\\%", $search) . "%";
            $query .= " WHERE (`name` LIKE :search) OR (`domain` LIKE :search) OR (`url` LIKE :search)";
        }

        $query .= " ORDER BY `{$sort}` {$dir}";
        if ($limit > 0) {
            $query .= " LIMIT :offset, :limit";
        }
        $sth = self::getConnection()->prepare($query);
        if ($limit > 0) {
            $sth->bindValue(":offset", $offset, PDO::PARAM_INT);
            $sth->bindValue(":limit", $limit, PDO::PARAM_INT);
        }
        if (!is_null($search)) {
            $sth->bindValue(":search", $search);
        }
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_ASSOC);
    }


    /**
     * Returns data for a single domain
     *
     * @param int $id
     * @param boolean $with_details If true also return details
     *
     * @return array
     */
    public function getDomain($id, $with_details = true)
    {
        $sth = self::getConnection()->prepare("SELECT * FROM `domains` WHERE `id` = :id");
        $sth->bindValue(":id", $id, PDO::PARAM_INT);
        $sth->execute();
        $domain = $sth->fetch(PDO::FETCH_ASSOC);
        $sth->closeCursor();
        if ($domain && $with_details) {
            $mgrDatabase = new Database();
            $domain['database'] = $mgrDatabase->getDBLogins($id);
            $mgrFTP = new FTP();
            $domain['ftp'] = $mgrFTP->getFTPDetails($id);
            $mgrOther = new Other();
            $domain['other'] = $mgrOther->getOtherData($id);
        }
        return $domain;
    }

    /**
     * Returns true if domain exists
     *
     * @param int $domain_id
     *
     * @return boolean
     */
    public function domainExists($domain_id)
    {
        $sth = self::getConnection()->prepare("SELECT `id` FROM `domains` WHERE `id` = :id");
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->execute();
        $id = $sth->fetchColumn();
        $sth->closeCursor();
        return ($id > 0);
    }

    /**
     * Adds a new domain
     *
     * @param array $data
     *
     * @return array|Validate\Errors Added Domain data
     * @throws Validate\Exception
     */
    public function addDomain(array $data)
    {
        $data = $this->filterData(
            $data,
            array(
                 'name',
                 'domain',
                 'url',
                 'notes'
            )
        );
        $errors = $this->validate($data);
        if ($errors->hasErrors()) {
            throw new Validate\Exception("Data is invalid", 0, null, $errors);
        }
        $sth = self::getConnection()->prepare(
            "INSERT INTO `domains` (`name`,`domain`,`url`,`notes`) VALUES(:name,:domain,:url,:notes)"
        );
        $sth->execute($data);
        $domainid = self::getConnection()->lastInsertId();
        // Add blank FTP data
        $query = "INSERT INTO `data` (`domain`,`group`,`name`,`value`) VALUES (:domain,'ftp',:name,NULL);";
        $sth = self::getConnection()->prepare($query);
        $sth->bindValue(":domain", $domainid, PDO::PARAM_INT);
        $sth->bindParam(":name", $name, PDO::PARAM_STR);

        $name = "hostname";
        $sth->execute();
        $name = "username";
        $sth->execute();
        $name = "password";
        $sth->execute();
        $name = "remote folder";
        $sth->execute();
        $name = "local folder";
        $sth->execute();
        return $this->getDomain($domainid);
    }

    /**
     * Updates a domain
     *
     * @param int $id
     * @param array $data
     *
     * @return array Update domain data
     * @throws Validate\Exception
     */
    public function updateDomain($id, array $data)
    {
        $data = $this->filterData(
            $data,
            array(
                 'name',
                 'domain',
                 'url',
                 'notes'
            )
        );
        $errors = $this->validate($data, $id);
        if ($errors->hasErrors()) {
            throw new Validate\Exception("Data is invalid", 0, null, $errors);
        }
        $data['id'] = $id;
        $sth = self::getConnection()->prepare(
            "UPDATE `domains` SET `name` = :name,`domain` = :domain,`url` = :url,`notes` = :notes WHERE `id` = :id"
        );
        $sth->execute($data);
        return $this->getDomain($id);
    }

    /**
     * Deletes a domain
     *
     * @param int $id
     *
     * @return void
     */
    public function deleteDomain($id)
    {
        // Delete data
        $sth = self::getConnection()->prepare("DELETE FROM `data` WHERE `domain` = :id");
        $sth->bindValue(":id", $id, PDO::PARAM_INT);
        $sth->execute();
        // Delete domain
        $sth = self::getConnection()->prepare("DELETE FROM `domains` WHERE `id` = :id");
        $sth->bindValue(":id", $id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Validates Data
     * Returns an Error_Stack instance with any error messages
     * If all data is valid the Error_Stack will have no errors (Error_Stack::hasErrors() will return false)
     *
     * @param array Data
     * @param int $id Exception for unique values
     *
     * @return Validate\Errors
     */
    public function validate(array $data, $id = null)
    {
        $errors = new Validate\Errors();
        if (empty($data['name'])) {
            $errors->addError('name', 'Name is required.', 'required');
        } elseif (mb_strlen($data['name']) > 255) {
            $errors->addError('name', 'Name must not be more than 255 characters.', 'maxlength');
        } else {
            // Make sure name is unique
            if ($id) {
                $sth = self::getConnection()->prepare(
                    "SELECT COUNT(*) FROM `domains` WHERE `name` LIKE :name AND `id` != :id"
                );
                $sth->bindValue(":id", $id, PDO::PARAM_INT);
            } else {
                $sth = self::getConnection()->prepare("SELECT COUNT(*) FROM `domains` WHERE `name` LIKE :name");
            }
            $sth->bindValue(":name", $data['name']);
            $sth->execute();
            $count = $sth->fetchColumn();
            $sth->closeCursor();
            if ($count > 0) {
                $errors->addError('name', "Domain with name {$data['name']} already exists.", 'unique');
            }
        }
        if (mb_strlen($data['domain']) > 255) {
            $errors->addError('domain', 'Domain must not be more than 255 characters.', 'maxlength');
        }
        if (mb_strlen($data['url']) > 255) {
            $errors->addError('url', 'URL must not be more than 255 characters.', 'maxlength');
        }
        return $errors;
    }
}
