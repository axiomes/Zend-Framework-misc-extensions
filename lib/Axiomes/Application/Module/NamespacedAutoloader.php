<?php
/**
 * 
 */
namespace Axiomes\Application\Module;
class NamespacedAutoloader extends \Zend_Loader_Autoloader_Resource{

    public function __construct($options)
    {
        if ($options instanceof \Zend_Config) {
            $options = $options->toArray();
        }
        if (!is_array($options)) {
            require_once 'Zend/Loader/Exception.php';
            throw new \Zend_Loader_Exception('Options must be passed to resource loader constructor');
        }

        $this->setOptions($options);

        $namespace = $this->getNamespace();
        if ((null === $namespace)
            || (null === $this->getBasePath())
        ) {
            require_once 'Zend/Loader/Exception.php';
            throw new \Zend_Loader_Exception('Resource loader requires both a namespace and a base path for initialization');
        }

        require_once 'Zend/Loader/Autoloader.php';
        \Zend_Loader_Autoloader::getInstance()->unshiftAutoloader($this, $namespace.'\\');

        $this->addDefaultResourceTypes();
    }

    public function addDefaultResourceTypes(){
        $this->addResourceTypes(
            array(
                'repository' => array(
                    'namespace' => 'Repository',
                    'path'      => 'domain/repositories',
                ),
                'document' => array(
                    'namespace' => 'Document',
                    'path'      => 'domain/documents',
                ),
                'entity' => array(
                    'namespace' => 'Entity',
                    'path'      => 'domain/entities'
                ),
                'service' => array(
                    'namespace' => 'Service',
                    'path'      => 'domain/services',
                ),
                'form'    => array(
                    'namespace' => 'Form',
                    'path'      => 'forms',
                ),
                'plugin'  => array(
                    'namespace' => 'Plugin',
                    'path'      => 'plugins',
                ),
                'viewhelper' => array(
                    'namespace' => 'View\Helper',
                    'path'      => 'views/helpers',
                ),
                'viewfilter' => array(
                    'namespace' => 'View\Filter',
                    'path'      => 'views/filters',
                ),
            )
        );
    }
    
    public function addResourceType($type, $path, $namespace = null)
    {
        $type = strtolower($type);
        if (!isset($this->_resourceTypes[$type])) {
            if (null === $namespace) {
                require_once 'Zend/Loader/Exception.php';
                throw new \Zend_Loader_Exception('Initial definition of a resource type must include a namespace');
            }
            $namespaceTopLevel = $this->getNamespace();
            $namespace = ucfirst(trim($namespace, '\\'));
            $this->_resourceTypes[$type] = array(
                'namespace' => empty($namespaceTopLevel) ? $namespace : $namespaceTopLevel . '\\' . $namespace,
            );
        }
        if (!is_string($path)) {
            require_once 'Zend/Loader/Exception.php';
            throw new Zend_Loader_Exception('Invalid path specification provided; must be string');
        }
        $this->_resourceTypes[$type]['path'] = $this->getBasePath() . '/' . rtrim($path, '\/');

        $component = $this->_resourceTypes[$type]['namespace'];
        $this->_components[$component] = $this->_resourceTypes[$type]['path'];
        return $this;
    }

    /**
     * Helper method to calculate the correct class path
     *
     * @param string $class
     * @return False if not matched other wise the correct path
     */
    public function getClassPath($class)
    {
        $segments          = explode('\\', $class);
        $namespaceTopLevel = $this->getNamespace();
        $namespace         = '';

        if (!empty($namespaceTopLevel)) {
            $namespace = array_shift($segments);
            if ($namespace != $namespaceTopLevel) {
                // wrong prefix? we're done
                return false;
            }
        }

        if (count($segments) < 2) {
            // assumes all resources have a component and class name, minimum
            return false;
        }

        $final     = array_pop($segments);
        $component = $namespace;
        $lastMatch = false;
        do {
            $segment    = array_shift($segments);
            $component .= empty($component) ? $segment : '\\' . $segment;
            if (isset($this->_components[$component])) {
                $lastMatch = $component;
            }
        } while (count($segments));

        if (!$lastMatch) {
            return false;
        }

        $final = substr($class, strlen($lastMatch) + 1);
        $path = $this->_components[$lastMatch];
        $classPath = $path . '/' . str_replace('\\', '/', $final) . '.php';

        if (\Zend_Loader::isReadable($classPath)) {
            return $classPath;
        }

        return false;
    }
}
