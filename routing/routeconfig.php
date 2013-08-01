<?php
/**
 * ownCloud - App Framework
 *
 * @author Thomas Müller
 * @copyright 2013 Thomas Müller thomas.mueller@tmit.eu
 *
 * This library is free software; you can redistribute it and/or
 * modify it under the terms of the GNU AFFERO GENERAL PUBLIC LICENSE
 * License as published by the Free Software Foundation; either
 * version 3 of the License, or any later version.
 *
 * This library is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU AFFERO GENERAL PUBLIC LICENSE for more details.
 *
 * You should have received a copy of the GNU Affero General Public
 * License along with this library.  If not, see <http://www.gnu.org/licenses/>.
 *
 */

namespace OCA\AppFramework\routing;

require_once __DIR__ . "/../3rdparty/Yaml/Parser.php";
require_once __DIR__ . "/../3rdparty/Yaml/Inline.php";

use OCA\AppFramework\DependencyInjection\DIContainer;
use Symfony\Component\Yaml\Parser;

/**
 * Class RouteConfig
 * @package OCA\AppFramework\routing
 */
class RouteConfig {
	private $container;
	private $router;
	private $yml;
	private $appName;

	/**
	 * @param \OCA\AppFramework\DependencyInjection\DIContainer $container
	 * @param \OC_Router $router
	 * @param string $pathToYml
	 * @internal param $appName
	 */
	public function __construct(DIContainer $container, \OC_Router $router, $pathToYml) {
		if (file_exists($pathToYml)) {
			$this->yml = file_get_contents($pathToYml);
		} else {
			$this->yml = $pathToYml;
		}
		$this->container = $container;
		$this->router = $router;
		$this->appName = $container['AppName'];
	}

	/**
	 * The routes and resource will be registered to the \OC_Router
	 */
	public function register() {

		// parse yaml
		$routes = $this->load();

		// parse simple
		$this->processSimpleRoutes($routes);

		// parse resources
		$this->processResources($routes);
	}

	/**
	 * Creates one route base on the give configuration
	 * @param $routes
	 * @throws \UnexpectedValueException
	 */
	private function processSimpleRoutes($routes)
	{
		$simpleRoutes = isset($routes['routes']) ? $routes['routes'] : array();
		foreach ($simpleRoutes as $simpleRoute) {
			$name = $simpleRoute['name'];
			$url = $simpleRoute['url'];
			$verb = isset($simpleRoute['verb']) ? strtoupper($simpleRoute['verb']) : 'GET';

			$split = explode('#', $name, 2);
			if (count($split) != 2) {
				throw new \UnexpectedValueException('Invalid route name');
			}
			$controller = $split[0];
			$action = $split[1];

			$controllerName = $this->buildControllerName($controller);
			$actionName = $this->buildActionName($action);

			// register the route
			$handler = new RouteActionHandler($this->container, $controllerName, $actionName);
			$this->router->create($this->appName.'.'.$controller.'.'.$action, $url)->method($verb)->action($handler);
		}
	}

	/**
	 * For a given name and url restful routes are created:
	 *  - index
	 *  - show
	 *  - new
	 *  - create
	 *  - update
	 *  - destroy
	 *
	 * @param $routes
	 */
	private function processResources($routes)
	{
		// declaration of all restful actions
		$actions = array(
			array('name' => 'index', 'verb' => 'GET', 'on-collection' => true),
			array('name' => 'show', 'verb' => 'GET'),
			array('name' => 'create', 'verb' => 'POST', 'on-collection' => true),
			array('name' => 'update', 'verb' => 'PUT'),
			array('name' => 'destroy', 'verb' => 'DELETE'),
		);

		$resources = isset($routes['resources']) ? $routes['resources'] : array();
		foreach ($resources as $resource => $config) {

			// the url parameter used as id to the resource
			$resourceId = $this->buildResourceId($resource);
			foreach($actions as $action) {
				$url = $config['url'];
				$method = $action['name'];
				$verb = $action['verb'];
				$collectionAction = isset($action['on-collection']) ? $action['on-collection'] : false;
				if (!$collectionAction) {
					if ( isset($config['params']) ) {
						$params = explode('/', $config['params']);
						foreach ($params as $param) {
							$url = $url . "/{" . $param . "}";
						};
						
					} else {
						$url = $url . '/' . $resourceId;
					}
				}

				$controller = $resource;

				$controllerName = $this->buildControllerName($controller);
				$actionName = $this->buildActionName($method);

				$routeName = $this->appName . '.' . strtolower($resource) . '.' . strtolower($method);

				$this->router->create($routeName, $url)->method($verb)->action(
					new RouteActionHandler($this->container, $controllerName, $actionName)
				);
			}
		}
	}

	/**
	 * Parse the YAML
	 * @return mixed
	 */
	private function load() {
		$yaml = new Parser();
		return $yaml->parse($this->yml);
	}

	/**
	 * Based on a given route name the controller name is generated
	 * @param $controller
	 * @return string
	 */
	private function buildControllerName($controller)
	{
		return $this->underScoreToCamelCase(ucfirst($controller)) . 'Controller';
	}

	/**
	 * Based on the action part of the route name the controller method name is generated
	 * @param $action
	 * @return string
	 */
	private function buildActionName($action) {
		return $this->underScoreToCamelCase($action);
	}

	/**
	 * Generates the id used in the url part o the route url
	 * @param $resource
	 * @return string
	 */
	private function buildResourceId($resource) {
		return '{'.$this->underScoreToCamelCase(rtrim($resource, 's')).'Id}';
	}

	/**
	 * Underscored strings are converted to camel case strings
	 * @param $str string
	 * @return string
	 */
	private function underScoreToCamelCase($str) {
		$pattern = "/_[a-z]?/";
		return preg_replace_callback(
			$pattern,
			function ($matches) {
				return strtoupper(ltrim($matches[0], "_"));
			},
			$str);
	}
}
