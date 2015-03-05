<?php

namespace Myphalcon\Frontend\Controllers;

use Myphalcon\Frontend\Models\Roles;
use Myphalcon\Frontend\Models\Users;
use Phalcon\Http\Response;

class IndexController extends ControllerBase
{

    public function indexAction()
    {
		$this->view->disable();
//		var_dump($this->response->getHeaders());
		$this->debugbar->addMessage('aaaaa','mylabel');
		try{
			throw new \Exception;
		}catch (\Exception $e){
			$this->debugbar->addException($e);
//			throw $e;
		}
//		$this->debugbar->addMeasure('test',PHALCON_START,$a = microtime(true));
		$this->debugbar->addMeasurePoint('s1');

		$this->session->set('key','adfi2rde897');
		$this->cookies->set('test_cookie','aasdfsdf');
		$this->debugbar->addMeasurePoint('s2');

		$roles = Users::find()->toArray();
		$this->debugbar->addMessage( $roles, 'roles' );
		$this->db->delete('unkonwn_talbe');
		$t = Roles::find(['role= :name:','bind'=>array('name'=>"vip")]);
		var_dump($roles);
//		echo 111;
    }

	public function testAction() {
		echo 'test';
	}

}

