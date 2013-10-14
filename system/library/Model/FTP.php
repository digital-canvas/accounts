<?php namespace Model;

use PDO;

use Validate;

/**
 * FTP login model
 *
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @license MIT http://opensource.org/licenses/MIT
 * @package Model
 * @subpackage FTP
 */
class FTP extends BaseModel
{

    /**
     * Default values
     * @var array
     */
    public static $default = array(
        'username' => null,
        'password' => null,
        'remote_folder' => null,
        'local_folder' => null,
        'hostname' => null,
    );

    /**
     * Returns FTP Logins for a domain
     *
     * @param int $domain_id
     *
     * @return array
     * @throws Exception if domain doesn't exist
     */
    public function getFTPDetails($domain_id)
    {
        $sth = self::getConnection()->prepare(
            "SELECT `name`,`value` FROM `data` WHERE `domain` = :id AND `group`='ftp'"
        );
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->execute();
        $result = $sth->fetchAll(PDO::FETCH_KEY_PAIR);
        if(!$result){
            return $result;
        }
        return array(
            'id' => $domain_id,
            'username' => $result['username'],
            'password' => $result['password'],
            'remote_folder' => $result['remote folder'],
            'local_folder' => $result['local folder'],
            'hostname' => $result['hostname'],
        );
    }


    /**
     * Updates FTP login credentials
     *
     * @param int $domain_id
     * @param array $data
     *
     * @return array Updated record
     * @throws Validate\Exception
     */
    public function updateFTP($domain_id, array $data)
    {
        $data = $this->filterData(
            $data,
            array(
                 'username',
                 'password',
                 'remote_folder',
                 'hostname',
                 'local_folder'
            )
        );
        $errors = $this->validate($data);
        if ($errors->hasErrors()) {
            throw new Validate\Exception("Data is invalid", 0, null, $errors);
        }
        $query = "UPDATE `data` SET `value`=:value WHERE `name`=:name AND `domain`=:id AND `group`='ftp'";
        $sth = self::getConnection()->prepare($query);
        $name = "hostname";
        $value = $data['hostname'];
        $sth->bindParam(":name", $name, PDO::PARAM_STR);
        $sth->bindParam(":value", $value, PDO::PARAM_STR);
        $sth->bindValue(":id", $domain_id, PDO::PARAM_INT);
        $sth->execute();
        $name = "username";
        $value = $data['username'];
        $sth->execute();
        $name = "password";
        $value = $data['password'];
        $sth->execute();
        $name = "remote folder";
        $value = $data['remote_folder'];
        $sth->execute();
        $name = "local folder";
        $value = $data['local_folder'];
        $sth->execute();

        return $this->getFTPDetails($domain_id);
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
        if (mb_strlen($data['hostname']) > 255) {
            $errors->addError('hostname', 'Hostname must not be more than 255 characters.', 'maxlength');
        }
        if (mb_strlen($data['username']) > 255) {
            $errors->addError('username', 'Username must not be more than 255 characters.', 'maxlength');
        }
        if (mb_strlen($data['password']) > 255) {
            $errors->addError('password', 'Password must not be more than 255 characters.', 'maxlength');
        }
        if (mb_strlen($data['remote_folder']) > 255) {
            $errors->addError('remote_folder', 'Remote folder must not be more than 255 characters.', 'maxlength');
        }
        if (mb_strlen($data['local_folder']) > 255) {
            $errors->addError('local_folder', 'Local folder must not be more than 255 characters.', 'maxlength');
        }
        return $errors;
    }
}
