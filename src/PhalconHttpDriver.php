<?php
/**
 * User: zhuyajie
 * Date: 15/3/3
 * Time: 11:55
 */

namespace Snowair\Debugbar;

use Phalcon\DI\Injectable;
use DebugBar\HttpDriverInterface;

class PhalconHttpDriver extends Injectable implements HttpDriverInterface {

	/**
	 * {@inheritDoc}
	 */
	function setHeaders( array $headers ) {
		if ( $this->response!==null ) {
			foreach ( $headers as $key => $value ) {
				$this->response->setHeader( $key,$value );
			}
		}
	}

	/**
     * {@inheritDoc}
	 */
	function isSessionStarted() {
		if ( !$this->session->exists() ) {
			$this->session->start();
		}
		return $this->session->exists();
	}

	/**
	 * {@inheritDoc}
	 */
	function setSessionValue( $name, $value ) {
		$this->session->set($name,$value);
	}

	/**
	 * Checks if a value is in the session
	 *
	 * @param string $name
	 *
	 * @return boolean
	 */
	function hasSessionValue( $name ) {
		return $this->session->has($name);
	}

	/**
	 * Returns a value from the session
	 *
	 * @param string $name
	 *
	 * @return mixed
	 */
	function getSessionValue( $name ) {
		return $this->session->get($name);
	}

	/**
	 * Deletes a value from the session
	 *
	 * @param string $name
	 */
	function deleteSessionValue( $name ) {
		$this->session->remove($name);
}}