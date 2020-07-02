<?php
/**
 * User: zhuyajie
 * Date: 15/3/4
 * Time: 15:40
 */

namespace Snowair\Debugbar\DataCollector;


use DebugBar\DataCollector\DataCollector;
use DebugBar\DataCollector\DataCollectorInterface;
use DebugBar\DataCollector\Renderable;
use Phalcon\DiInterface;
use Phalcon\Http\Request;
use Phalcon\Http\Response;
use Phalcon\Version;

class PhalconRequestCollector extends DataCollector implements DataCollectorInterface,Renderable {

	/**
	 * @var  Request $request
	 */
	protected  $request;
	/**
	 * @var  Response $response
	 */
	protected $response;
	/**
	 * @var  DiInterface
	 */
	protected $di;

	public function __construct($request, $response,$di)
	{
		$this->request = $request;
		$this->response = $response;
		$this->di = $di;
	}

	/**
	 * Called by the DebugBar when data needs to be collected
	 * @return array Collected data
	 */
	function collect() {
		$request = $this->request;
		$response = $this->response;

		$status = $response->getHeaders()->get('Status')?:'200 ok';

		$responseHeaders = $response->getHeaders()->toArray()?:headers_list();

		$cookies = $_COOKIE;
		unset($cookies[session_name()]);
		$cookies_service = $response->getCookies();
		if ( $cookies_service ) {
			$useEncrypt = true;
			if ( $cookies_service->isUsingEncryption() && $this->di->has('crypt') && !$this->di['crypt']->getKey()) {
				$useEncrypt = false;
			}
			if ( !$cookies_service->isUsingEncryption() ) {
				$useEncrypt = false;
			}
			foreach ( $cookies as $key=>$vlaue ) {
				$cookies[$key] = $cookies_service->get($key)->useEncryption($useEncrypt)->getValue();
			}
		}
		$data = array(
			'status'           => $status,
			'request_query'    => $request->getQuery(),
			'request_post'     => $request->getPost(),
			'request_body'     => $request->getRawBody(),
			'request_cookies'  => $cookies,
			'request_server'   => $_SERVER,
			'response_headers' => $responseHeaders,
            'response_body'    => $request->isAjax()?$response->getContent():'',
		);
        if ( Version::getId()<2000000 && $request->isAjax()) {
            $data['request_headers']=''; // 1.3.x has a ajax bug , so we use empty string insdead.
        }else{
            $data['request_headers']=$request->getHeaders();
        }

		$data = array_filter($data);
		if ( isset($data['request_query']['_url']) ) {
			unset($data['request_query']['_url']);
		}
		if ( empty($data['request_query']) ) {
			unset($data['request_query']);
		}

		if (isset($data['request_headers']['php-auth-pw'])) {
			$data['request_headers']['php-auth-pw'] = '******';
		}

		if (isset($data['request_server']['PHP_AUTH_PW'])) {
			$data['request_server']['PHP_AUTH_PW'] = '******';
		}

		foreach ($data as $key => $var) {
			if (!is_string($data[$key])) {
				$data[$key] = $this->formatVar($var);
			}
		}

		return $data;
	}

	/**
	 * Returns the unique name of the collector
	 * @return string
	 */
	function getName() {
		return 'request';
	}

	/**
	 * Returns a hash where keys are control names and their values
	 * an array of options as defined in {@see DebugBar\JavascriptRenderer::addControl()}
	 * @return array
	 */
	function getWidgets() {
		return array(
			"request" => array(
				"icon" => "tags",
				"widget" => "PhpDebugBar.Widgets.VariableListWidget",
				"map" => "request",
				"default" => "{}"
			)
		);
	}
}