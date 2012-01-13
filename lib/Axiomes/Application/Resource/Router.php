<?php
namespace Axiomes\Application\Resource;
class Router extends \Zend_Application_Resource_ResourceAbstract{

    /**
     * @var \Zend_Controller_Router_Interface
     */
    protected $_router;

    /**
     * @var array
     */
    protected $_routesConfig;

    /**
     * @var \Zend_Cache_Core
     */
    protected $_cache;

	/**
	 * @var string
	 */
	protected $_cacheKey = 'AxiomesApplicationResourceRouter';

	/**
	 * @var string
	 */
	protected $_registryKey = 'AxiomesRouter';

	/**
	 * @param $key string
	 */
	public function setCacheKey($key){
		$this->_cacheKey = $key;
	}

	/**
	 * @param $key string
	 */
	public function setRegistryKey($key){
		$this->_registryKey = $key;
	}

    public function init(){
        $front = \Zend_Controller_Front::getInstance();
        $options = $this->getOptions();
        if(isset($options['cache'])){
			$this->initCache($options['cache']);
		}
        if (!$this->_router) {
            $this->initFile($options['file']);
            $this->_router = $front->getRouter();
            if($this->_routesConfig){
                $routes = $this->_routesConfig;
            } else {
                $options = $this->getOptions();
                $routes = isset($options['routes']) ? $options['routes'] : array();
                $this->_routesConfig = $routes;
            }
            if(!empty($routes)){
                $this->_router->addConfig($routes, 'routes');
                if( $this->_cache ) $this->_cache->save($this->_router, 'Router');
            }
            if($this->_cache){
				$this->_cache->save($this->_router, $this->_cacheKey);
			}

        }

        $front->setRouter($this->_router);
		\Zend_Registry::set($this->_registryKey, $this->_router);
        return $this->_router;
    }

	/**
	 * @param $file
	 * @return Router
	 * @throws Exception
	 */
    protected function initFile($file)
    {
        if($this->_cache){
            $test = $this->_cache->test($this->_cacheKey);
            if($test) return;
        }
        $environment = $this->getBootstrap()->getApplication()->getEnvironment();
        $suffix      = pathinfo($file, PATHINFO_EXTENSION);
        $suffix      = ($suffix === 'dist')
                     ? pathinfo(basename($file, ".$suffix"), PATHINFO_EXTENSION)
                     : $suffix;

        switch (strtolower($suffix)) {
            case 'ini':
                $config = new \Zend_Config_Ini($file, $environment);
                break;

            case 'xml':
                $config = new \Zend_Config_Xml($file, $environment);
                break;

            case 'json':
                $config = new \Zend_Config_Json($file, $environment);
                break;

            case 'yaml':
            case 'yml':
                $config = new \Zend_Config_Yaml($file, $environment);
                break;

            case 'php':
            case 'inc':
                $config = include $file;
                if (!is_array($config)) {
                    throw new namespace\Exception('Invalid configuration file; PHP file does not return array value');
                }
                return $config;
                break;

            default:
                throw new namespace\Exception('Invalid configuration file provided; unknown config type');
        }
        $this->_routesConfig = isset($config->routes) ? $config : array();
        return $this;
    }

    protected function initCache($cache)
    {
        if (is_string($cache)) {
            $bootstrap = $this->getBootstrap();
            if ($bootstrap instanceof \Zend_Application_Bootstrap_ResourceBootstrapper
                && $bootstrap->hasPluginResource('CacheManager')
            ) {
                $cacheManager = $bootstrap->bootstrap('CacheManager')
                    ->getResource('CacheManager');
                if (null !== $cacheManager && $cacheManager->hasCache($cache)) {
                    $cache = $cacheManager->getCache($cache);
                }
            }
        }

        if ($cache instanceof \Zend_Cache_Core) {
            $this->_cache = $cache;

            $router = $cache->load($this->_cacheKey);
            if($router) $this->_router = $router;
        }

        return $this;
    }
}
