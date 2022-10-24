<?php
namespace Controller;

use General\Config;

use \General\CustomException as CustomException;

/**
 *
 * Main application controller
 * @author Paweł
 * @brief Main application controller that runs appropriate controllers depending on user requests
 *
 */
class Main extends Base implements \Interfaces\Singleton {

	private static $instance;

	/**
	 * Private constructor
	 */
	private function __construct() {

	}

	/**
	 *
	 * Singleton instance
	 * @throws \Exception
	 */
	static public function getInstance() {
		if (empty(self::$instance)) {
			self::$instance = new self();
		}
		if (empty(self::$instance)) {
			throw new \Exception('Main Controller was unable to initiate');
		}
		return self::$instance;
	}

	/**
	 * Main controller
	 * @return string
	 */
	public function get() {
		try {
			/**
			 * Quoted request table
			 * @var array
			 */
			$aRequest = $_REQUEST;
			\Database\Factory::getInstance()->quoteAll($aRequest);

			/**
			 * Template initiation
			 * @var \General\Templater
			 */
			$template = new \General\Templater('index.html');

			/*
			 * Registration of slats
			*/
			\Listeners\Message::getInstance()->register($aRequest, $template);

			if (empty ( $aRequest ['class'] )) {
				$aRequest ['class'] = 'Frontpage';
			}
			if (empty ( $aRequest ['method'] )) {
				$aRequest ['method'] = 'render';
			}
			if (! isset ( $HTTP_RAW_POST_DATA )) {
				$HTTP_RAW_POST_DATA = file_get_contents ( "php://input" );
			}

			$retVal = '';
			$className = '';
			switch ($aRequest ['class']) {
				default:
					$className = '\\Controller\\'.$aRequest ['class'];
					break;
			}

			$methodName = '';
			switch ($aRequest ['method']) {
				default :
					$methodName = $aRequest ['method'];
					break;
			}

			if (class_exists($className)) {
				$tObject = $className::getInstance();
				if (method_exists($tObject, $methodName)) {
					$tObject->{$methodName}($aRequest, $template);
				}
			}

			\Listeners\LowLevelMessage::getInstance()->register($aRequest, $template);
		} catch ( CustomException $e ) {
			$template->add('mainContent',\General\Debug::cThrow ( $e->getMessage (), $e, array ('send' => false, 'display' => false ) ));
		} catch ( Exception $e ) {
			$template->add('mainContent',\General\Debug::cThrow ( null, $e ));
		}

		$template->add('chartHead', '');
		$template->add('listeners', '');
		$template->add('menu', '');
		$template->add('mainContent', '');
		$template->add('titleSecond', '');
		$template->add('pageTitle', '{T:Product Name}');
		
		return (string) $template;

	}

}