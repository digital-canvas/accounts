<?php namespace Validate;

/**
 * Validation exception class
 *
 * @author Jonathan Bernardi <spekkionu@spekkionu.com>
 * @license MIT http://opensource.org/licenses/MIT
 * @package Validation
 */
class Exception extends \Exception
{

    /**
     * Error stack
     * @var \Validate\Errors $errors
     */
    protected $errors;

    /**
     * Class constructor
     *
     * @param string $message The exception message
     * @param int $code The exception code
     * @param \Exception $previous The previous exception
     * @param \Validate\Errors $errorstack An Validate\Errors instance with error messages
     */
    public function __construct(
        $message = "",
        $code = 0,
        \Exception $previous = null,
        Errors $errorstack = null
    ) {
        parent::__construct($message, $code, $previous);
        $this->setErrors($errorstack);
    }

    /**
     * Sets errorstack
     *
     * @param \Validate\Errors $errors
     *
     * @return \Validate\Exception
     */
    public function setErrors(Errors $errors = null)
    {
        $this->errors = $errors;
        return $this;
    }

    /**
     * Returns Error Messages
     * @return \Validate\Errors
     */
    public function getErrors()
    {
        return $this->errors;
    }
}
