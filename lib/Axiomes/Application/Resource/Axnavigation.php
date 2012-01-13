<?php
namespace Axiomes\Application\Resource;
class Axnavigation extends \Zend_Application_Resource_Navigation{

    /**
     * Resource name spoofing, in order to be registered
     * as 'navigation' plugin instead of 'axnavigation'
     */
    public $_explicitType = "navigation";
    
    /**
     * @var Zend_Cache_Core
     */
    protected $_cache;

    /**
     * @var array
     */
    protected $_pages;

    public function initCache($cache)
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

            $container = $cache->load('Zend_Navigation');
            if($container) $this->_container = $container;
        }

        return $this;
    }

    protected function initFile($file)
    {
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
                    throw new namespace\Exception('Invalid configuration file provided; PHP file does not return array value');
                }
                return $config;
                break;

            default:
                throw new namespace\Exception('Invalid configuration file provided; unknown config type');
        }
        $configArray = $config->toArray();
        $this->_pages = isset($configArray['pages']) ? $configArray['pages'] : array();
        return $this;
    }

    /**
     * Defined by Zend_Application_Resource_Resource
     *
     * @return Zend_Navigation
     */
    public function init()
    {
        $options = $this->getOptions();
        if(isset($options['cache'])) $this->initCache($options['cache']);
        if (!$this->_container) {
            $this->initFile($options['file']);
            if($this->_pages){
                $pages = $this->_pages;
            } else {
                $options = $this->getOptions();
                $pages = isset($options['pages']) ? $options['pages'] : array();
            }
            $this->_container = new \Zend_Navigation($pages);

            if(isset($options['defaultPageType'])) {
                \Zend_Navigation_Page::setDefaultPageType($options['defaultPageType']);
            }
            if( $this->_cache ) $this->_cache->save($this->_container, 'Zend_Navigation');
        }

        $this->store();
        return $this->_container;
    }
}
