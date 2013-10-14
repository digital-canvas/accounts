<?php namespace Model;

use PDO;
use Validate;

/**
 * Database login model
 *
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @license MIT http://opensource.org/licenses/MIT
 * @package Model
 * @subpackage Database
 */
class Database extends BaseModel
{

    /**
     * Default values
     * @var array
     */
    public static $default = array(
        'Hostname' => null,
        'Username' => null,
        'Password' => null,
        'Database' => null,
        'URL' => null,
        'dbtype' => 'MYSQL',
    );

    /**
     * Returns Database Logins for a website
     *
     * @param int $domain_id
     *
     * @return array
     */
    public function getDBLogins($domain_id)
    {
        $sth = self::getConnection()->prepare(
            "SELECT DISTINCT `group` FROM `data` WHERE `domain`=:id AND `group` LIKE 'database%' ORDER BY `group` ASC"
        );
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->execute();
        $databases = array();

        $sthData = self::getConnection()->prepare(
            "SELECT `name`,`value` FROM `data` WHERE `domain`=:id AND `group` LIKE :group"
        );
        $sthData->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sthData->bindParam(":group", $group, PDO::PARAM_STR);

        while ($group = $sth->fetchColumn()) {
            $sthData->execute();
            if(preg_match("/^database(\\d+)$/i", $group, $matches)){
                $dbnum = $matches[1];
            }else{
                $dbnum = 0;
            }
            $result = $sthData->fetchAll(PDO::FETCH_KEY_PAIR);
            $databases[] = array(
                'id' => $dbnum,
                'group' => $group,
                'domain' => $domain_id,
                'Hostname' => $result['Hostname'],
                'Username' => $result['Username'],
                'Password' => $result['Password'],
                'Database' => $result['Database'],
                'URL' => $result['URL'],
                'dbtype' => $result['Database Type'],
            );
        }
        return $databases;
    }

    /**
     * Returns database login details
     *
     * @param int $domain_id
     * @param int $dbnum
     *
     * @return array
     */
    public function getDBDetails($domain_id, $dbnum)
    {
        $group = "database" . $dbnum;
        $sth = self::getConnection()->prepare(
            "SELECT `name`,`value` FROM `data` WHERE `domain`=:id AND `group` LIKE :group ORDER BY `name` ASC"
        );
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->bindValue(":group", $group, PDO::PARAM_STR);
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
        if(!$result){
            return $result;
        }
        return array(
            'id' => $dbnum,
            'group' => $group,
            'domain' => $domain_id,
            'Hostname' => $result['Hostname'],
            'Username' => $result['Username'],
            'Password' => $result['Password'],
            'Database' => $result['Database'],
            'URL' => $result['URL'],
            'dbtype' => $result['Database Type'],
        );
    }

    /**
     * Adds database login credentials
     *
     * @param int $domain_id
     * @param array $data
     *
     * @return array Added record
     * @throws Validate\Exception
     * @throws \Exception
     */
    public function addDB($domain_id, array $data)
    {
        $data = $this->filterData(
            $data,
            array(
                 'Hostname',
                 'Username',
                 'Password',
                 'Database',
                 'URL',
                 'dbtype'
            )
        );
        $errors = $this->validate($data);
        if ($errors->hasErrors()) {
            throw new Validate\Exception("Data is invalid", 0, null, $errors);
        }
        $dbnum = $this->getNextIndex($domain_id);
        $group = "database" . $dbnum;
        self::getConnection()->beginTransaction();
        try{
            $sth = self::getConnection()->prepare(
                "INSERT INTO `data` (`domain`,`name`,`value`,`group`) VALUES (:id,:name,:value,:group)"
            );
            $name = "Database Type";
            $value = $data['dbtype'];
            $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
            $sth->bindParam(":name", $name, PDO::PARAM_STR);
            $sth->bindParam(":value", $value, PDO::PARAM_STR);
            $sth->bindValue(":group", $group, PDO::PARAM_STR);
            $sth->execute();

            $name = "Hostname";
            $value = $data['Hostname'];
            $sth->execute();

            $name = "Username";
            $value = $data['Username'];
            $sth->execute();

            $name = "Password";
            $value = $data['Password'];
            $sth->execute();

            $name = "Database";
            $value = $data['Database'];
            $sth->execute();

            $name = "URL";
            $value = $data['URL'];
            $sth->execute();
            self::getConnection()->commit();
            return $this->getDBDetails($domain_id, $dbnum);
        }catch(\Exception $e){
            self::getConnection()->rollBack();
            throw $e;
        }
    }

    /**
     * Returns next database index
     *
     * @param  int $domain_id Domain id
     *
     * @return int Next index
     */
    public function getNextIndex($domain_id)
    {
        $sth = self::getConnection()->prepare(
            "SELECT `group` FROM `data` WHERE `domain`=:id AND `group` LIKE 'database%' ORDER BY `group` DESC LIMIT 1"
        );
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->execute();
        $group = $sth->fetchColumn();
        $sth->closeCursor();
        if (!$group) {
            return 0;
        }
        if (preg_match("/^database(\\d+)$/i", $group, $matches)) {
            return $matches[1] + 1;
        } else {
            return 0;
        }
    }

    /**
     * Updates database login credentials
     *
     * @param int $dbnum
     * @param array $data
     * @param int $domain_id
     *
     * @return array Updated record
     * @throws Validate\Exception
     */
    public function updateDB($dbnum, array $data, $domain_id)
    {
        $group = "database" . $dbnum;
        $data = $this->filterData(
            $data,
            array(
                 'Hostname',
                 'Username',
                 'Password',
                 'Database',
                 'URL',
                 'dbtype'
            )
        );
        $errors = $this->validate($data);
        if ($errors->hasErrors()) {
            throw new Validate\Exception("Data is invalid", 0, null, $errors);
        }
        $sth = self::getConnection()->prepare(
            "UPDATE `data` SET `value`=:value WHERE `name`=:name AND `domain`=:id AND `group` LIKE :group"
        );
        $name = "Database Type";
        $value = $data['dbtype'];
        $sth->bindParam(":name", $name, PDO::PARAM_STR);
        $sth->bindParam(":value", $value, PDO::PARAM_STR);
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->bindValue(":group", $group, PDO::PARAM_STR);
        $sth->execute();

        $name = "Hostname";
        $value = $data['Hostname'];
        $sth->execute();

        $name = "Username";
        $value = $data['Username'];
        $sth->execute();

        $name = "Password";
        $value = $data['Password'];
        $sth->execute();

        $name = "Database";
        $value = $data['Database'];
        $sth->execute();

        $name = "URL";
        $value = $data['URL'];
        $sth->execute();

        return $this->getDBDetails($domain_id, $dbnum);
    }

    /**
     * Deletes database login
     *
     * @param int $domain_id
     * @param int $dbnum
     *
     * @return void
     */
    public function deleteDB($domain_id, $dbnum)
    {
        // Delete admin logins
        $group = "database" . $dbnum;
        $sth = self::getConnection()->prepare("DELETE FROM `data` WHERE `domain` = :id AND `group` LIKE :group");
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->bindValue(":group", $group, PDO::PARAM_STR);
        $sth->execute();
    }

    /**
     * Validates Data
     * Retuns an Validate\Errors instance with any error messages
     * If all data is valid the Validate\Errors will have no errors (Validate\Errors::hasErrors() will return false)
     *
     * @param array Data
     *
     * @return Validate\Errors
     */
    public function validate(array $data)
    {
        $errors = new Validate\Errors();
        if (empty($data['dbtype'])) {
            $errors->addError('dbtype', 'Database type is required.', 'required');
        } elseif (!in_array($data['dbtype'], array('MYSQL', 'SQLITE', 'MSSQL', 'ORACLE', 'PGSQL', 'ACCESS', 'OTHER'))) {
            $errors->addError('dbtype', 'Invalid database type.', 'invalid');
        }
        if (mb_strlen($data['Database']) > 255) {
            $errors->addError('Database', 'Database must not be more than 255 characters.', 'maxlength');
        }
        if (mb_strlen($data['Hostname']) > 255) {
            $errors->addError('Hostname', 'Hostname must not be more than 255 characters.', 'maxlength');
        }
        if (mb_strlen($data['Username']) > 255) {
            $errors->addError('Username', 'Username must not be more than 255 characters.', 'maxlength');
        }
        if (mb_strlen($data['Password']) > 255) {
            $errors->addError('Password', 'Password must not be more than 255 characters.', 'maxlength');
        }
        if (mb_strlen($data['URL']) > 255) {
            $errors->addError('URL', 'URL must not be more than 255 characters.', 'maxlength');
        }
        return $errors;
    }
}
