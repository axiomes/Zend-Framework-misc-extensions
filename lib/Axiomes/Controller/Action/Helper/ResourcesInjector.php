<?php
/**
 * @see http://weierophinney.net/matthew/archives/235-A-Simple-Resource-Injector-for-ZF-Action-Controllers.html
 */
namespace Axiomes\Controller\Action\Helper;

class ResourcesInjector extends \Zend_Controller_Action_Helper_Abstract{

    protected $_resources;
    
    public function init()
    {
        $bootstrap  = $this->getBootstrap();
        $controller = $this->getActionController();

        if (!isset($controller->dependencies)
            || !is_array($controller->dependencies)
        ) {
            return;
        }

        foreach ($controller->dependencies as $name) {
            if (!$bootstrap->hasResource($name)) {
                throw new DomainException("Unable to find dependency by name '$name'");
            }
            $controller->$name = $bootstrap->getResource($name);
        }
    }

    public function getBootstrap()
    {
        return $this->getFrontController()->getParam('bootstrap');
    }
}
