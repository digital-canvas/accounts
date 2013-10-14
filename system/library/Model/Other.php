<?php namespace Model;

use PDO;
use Validate;

/**
 * Other data model
 *
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @license MIT http://opensource.org/licenses/MIT
 * @package Model
 * @subpackage Other
 */
class Other extends BaseModel
{

    /**
     * Default values
     * @var array
     */
    public static $default = array(
        'group' => null,
        'name' => null,
        'value' => null,
    );

    /**
     * Returns other data for a website
     *
     * @param int $domain_id
     *
     * @return array
     */
    public function getOtherData($domain_id)
    {
        $sth = self::getConnection()->prepare(
            "SELECT DISTINCT `group` FROM `data` WHERE `domain`=:id AND `group`!='ftp' AND `group` NOT LIKE 'database%' ORDER BY `group` ASC"
        );
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->execute();
        $data = array();

        $sthData = self::getConnection()->prepare("SELECT * FROM `data` WHERE `domain`=:id AND `group` LIKE :group");
        $sthData->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sthData->bindParam(":group", $group, PDO::PARAM_STR);

        while ($group = $sth->fetchColumn()) {
            $sthData->execute();
            $data[] = array(
                'group'=>$group,
                'domain' => $domain_id,
                'data' => $sthData->fetchAll(PDO::FETCH_ASSOC)
            );

        }
        return $data;
    }

    /**
     * Returns used data groups for a domain
     *
     * @param int $domain_id Domain ID
     *
     * @return array
     */
    public function getGroups($domain_id){
        $sth = self::getConnection()->prepare(
            "SELECT DISTINCT `group` FROM `data` WHERE `domain`=:id AND `group`!='ftp' AND `group` NOT LIKE 'database%' ORDER BY `group` ASC"
        );
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->execute();
        return $sth->fetchAll(PDO::FETCH_COLUMN);
    }

    /**
     * Returns database login details
     *
     * @param int $domain_id
     * @param int $id
     *
     * @return array
     */
    public function getDataRow($domain_id, $id)
    {
        $sth = self::getConnection()->prepare("SELECT * FROM `data` WHERE `id`=:id AND `domain` = :domain_id");
        $sth->bindValue(":id", $id, PDO::PARAM_INT);
        $sth->bindValue(":domain_id", $domain_id, PDO::PARAM_INT);
        $sth->execute();
        $row = $sth->fetch(PDO::FETCH_ASSOC);
        $sth->closeCursor();
        return $row;
    }

    /**
     * Adds other data
     *
     * @param int $domain_id
     * @param array $data
     *
     * @return array Added record
     * @throws Validate\Exception
     */
    public function addData($domain_id, array $data)
    {
        $data = $this->filterData(
            $data,
            array(
                 'name',
                 'group',
                 'value'
            )
        );
        $data['domain'] = $domain_id;
        $errors = $this->validate($data);
        if ($errors->hasErrors()) {
            throw new Validate\Exception("Data is invalid", 0, null, $errors);
        }
        $sth = self::getConnection()->prepare(
            "INSERT INTO `data` (`domain`,`name`,`value`,`group`) VALUES (:domain,:name,:value,:group)"
        );
        $sth->execute($data);
        $id = self::getConnection()->lastInsertId();
        return $this->getDataRow($domain_id, $id);
    }

    /**
     * Updates database login credentials
     *
     * @param int $id
     * @param array $data
     * @param  int $domain_id
     *
     * @return array Updated record
     * @throws Validate\Exception
     */
    public function updateDataRow($id, array $data, $domain_id)
    {
        $data = $this->filterData(
            $data,
            array(
                 'name',
                 'group',
                 'value'
            )
        );
        $data['domain'] = $domain_id;
        $errors = $this->validate($data, $id);
        if ($errors->hasErrors()) {
            throw new Validate\Exception("Data is invalid", 0, null, $errors);
        }
        $sth = self::getConnection()->prepare(
            "UPDATE `data` SET `name`=:name, `value`=:value, `group` = :group WHERE  `id`=:id AND `domain` = :domain"
        );
        $data['id'] = $id;
        $sth->execute($data);

        return $this->getDataRow($domain_id, $id);
    }

    /**
     * Deletes data row
     *
     * @param int $id
     * @param int $domain_id
     *
     * @return void
     */
    public function deleteDataRow($id, $domain_id)
    {
        $sth = self::getConnection()->prepare("DELETE FROM `data` WHERE `id` = :id AND `domain` = :domain_id");
        $sth->bindValue(":id", $id, PDO::PARAM_INT);
        $sth->bindValue(":domain_id", $domain_id, PDO::PARAM_INT);
        $sth->execute();
    }

    /**
     * Check if row with name already exists for group on domain
     *
     * @param  string $name
     * @param  string $group
     * @param  int $domain_id
     * @param  int $id
     *
     * @return boolean
     */
    public function nameExistsForGroup($name, $group, $domain_id, $id = null)
    {
        $query = "SELECT COUNT(`id`) AS 'num' FROM `data` WHERE `domain` = :domain_id AND `name` LIKE :name AND `group` LIKE :group";
        if ($id) {
            $query .= " AND `id` != :id";
        }
        $sth = self::getConnection()->prepare($query);
        $sth->bindValue(":domain_id", $domain_id, PDO::PARAM_INT);
        $sth->bindValue(":name", $name, PDO::PARAM_STR);
        $sth->bindValue(":group", $group, PDO::PARAM_STR);
        if ($id) {
            $sth->bindValue(":id", $id, PDO::PARAM_STR);
        }
        $sth->execute();
        $num = $sth->fetchColumn();
        $sth->closeCursor();
        return ($num > 0);
    }

    /**
     * Validates Data
     * Retuns an Validate\Errors instance with any error messages
     * If all data is valid the Validate\Errors will have no errors (Validate\Errors::hasErrors() will return false)
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
        } elseif ($this->nameExistsForGroup($data['name'], $data['group'], $data['domain'], $id)) {
            $errors->addError(
                'name',
                "Row with name {$data['name']} already exists in group {$data['group']} for this domain.",
                'unique'
            );
        }
        if (mb_strlen($data['value']) > 255) {
            $errors->addError('value', 'Value must not be more than 255 characters.', 'maxlength');
        }
        if (empty($data['group'])) {
            $errors->addError('group', 'Group is required.', 'required');
        } elseif (mb_strlen($data['group']) > 255) {
            $errors->addError('group', 'Group must not be more than 255 characters.', 'maxlength');
        } elseif (mb_strtolower($data['group']) == 'ftp') {
            $errors->addError('group', 'Group cannot be ftp.', 'ftp');
        } elseif (preg_match("/^database.*/i", $data['group'])) {
            $errors->addError('group', 'Group cannot start with database.', 'database');
        }
        return $errors;
    }
}
