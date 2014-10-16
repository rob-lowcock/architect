<?php
namespace Architect;

use \Slim\Slim;
use \Pimple\Container;

/**
 * Architect\Core
 *
 * Defines and runs the core rendering Architect
 *
 * @category Core
 * @package Architect
 * @author Rob Lowcock <rob.lowcock@gmail.com>
 */
class Core extends ArchitectAbstract
{
	public static $app;

	protected $container;

	/**
	 * Constructor
	 */
	public function __construct(Container $container)
	{
		self::$app = new Slim();
		self::$app->add(new \Slim\Middleware\ContentTypes());

		$this->container = $container;

		if (empty($this->container['request'])) {
			throw new \RuntimeException('No request received');
		}

		$this->container['request']->validate();
	}

	/**
	 * Define the routes and return output for each REST method
	 * @param  object $app
	 * @return void
	 */
	public function routes()
	{
		self::$app->get('/:class/:identifier', function($class, $identifier) {
			$loaded = $this->_loadClass($class);

			$this->_displayOutput($loaded->read($identifier));
		});

		self::$app->get('/:class', function ($class) {
			$loaded = $this->_loadClass($class);

			$this->_displayOutput($loaded->read());
		});

		self::$app->put('/:class/:identifier', function($class, $identifier) {
			$loaded = $this->_loadClass($class);

			$this->_displayOutput($loaded->update($identifier));
		});

		self::$app->post('/:class', function($class) {
			$loaded = $this->_loadClass($class);

			$result = $loaded->create();

			if ($result->getCode() === ResponseCode::OK) {
				Core::$app->response->setStatus(201);
			}

			$this->_displayOutput($result);
		});

		self::$app->delete('/:class/:identifier', function($class, $identifier) {
			$loaded = $this->_loadClass($class);

			$this->_displayOutput($loaded->delete($identifier));
		});

		self::$app->options('/(:name+)', function(){
			self::$app->response()->header('Access-Control-Allow-Methods', 'POST, GET, PUT, DELETE, OPTIONS');
			self::$app->response()->header('Access-Control-Allow-Headers', 'Content-Type');
		});
	}

	/**
	 * Load a class based on the route recieved
	 * @param  string $class
	 * @param  object $app
	 * @return object
	 */
	private function _loadClass($class)
	{
		$fullclass = '\\Architect\\Controllers\\' . ucfirst($class);

		if (!class_exists($fullclass)) {
			self::$app->halt(404);
		}

		return new $fullclass($this->container);
	}

	/**
	 * Display the result output
	 * @param  Result $result
	 * @return void
	 */
	private function _displayOutput(Result $result)
	{
		if ($result->getCode() === ResponseCode::RESOURCE_NOT_FOUND) {
			self::$app->halt(404);
		}

		$data = $result->getData();

		if (isset($data)) {
			$response = self::$app->response();
			$response->header('Access-Control-Allow-Origin', '*');
			$response->write(json_encode($data));
		}
	}
}