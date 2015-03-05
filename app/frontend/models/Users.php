<?php
namespace  Myphalcon\Frontend\Models;

use Phalcon\Mvc\Model\Validator\Email as Email;

class Users extends \Phalcon\Mvc\Model
{

    /**
     *
     * @var integer
     */
    public $id;

    /**
     *
     * @var string
     */
    public $username;

    /**
     *
     * @var string
     */
    public $email;

    /**
     *
     * @var string
     */
    public $password;

    /**
     *
     * @var string
     */
    public $confirmation_code;

    /**
     *
     * @var string
     */
    public $remember_token;

    /**
     *
     * @var integer
     */
    public $confirmed;

    /**
     *
     * @var string
     */
    public $created_at;

    /**
     *
     * @var string
     */
    public $updated_at;

    /**
     * Validations and business logic
     */
    public function validation()
    {

        $this->validate(
            new Email(
                array(
                    'field'    => 'email',
                    'required' => true,
                )
            )
        );
        if ($this->validationHasFailed() == true) {
            return false;
        }
    }

    /**
     * Independent Column Mapping.
     */
    public function columnMap()
    {
        return array(
            'id' => 'id', 
            'username' => 'username', 
            'email' => 'email', 
            'password' => 'password', 
            'confirmation_code' => 'confirmation_code', 
            'remember_token' => 'remember_token', 
            'confirmed' => 'confirmed', 
            'created_at' => 'created_at', 
            'updated_at' => 'updated_at'
        );
    }

	public function initialize()
	{
		$this->setConnectionService('db2');
	}

}
