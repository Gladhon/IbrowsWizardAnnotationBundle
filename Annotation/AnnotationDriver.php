<?php

namespace Ibrows\Bundle\WizardAnnotationBundle\Annotation;

use Symfony\Component\HttpKernel\Event\FilterControllerEvent;
use Doctrine\Common\Annotations\Reader;

class AnnotationDriver
{
    /**
     * @var AnnotationHandler
     */
    protected $annotationHandler;

    /**
     * @var string
     */
    protected $annotationClassName;

    /**
     * @var Reader
     */
    protected $reader;

    /**
     * @param Reader $reader
     * @param AnnotationHandler $annotationHandler
     * @param $annotationClassName
     */
    public function __construct(Reader $reader, AnnotationHandler $annotationHandler, $annotationClassName)
    {
        $this->reader = $reader;
        $this->annotationHandler = $annotationHandler;
        $this->annotationClassName = $annotationClassName;
    }

    /**
     * @param FilterControllerEvent $event
     */
    public function onKernelController(FilterControllerEvent $event)
    {        
        $controllerArray = $event->getController();
        if(!is_array($controllerArray)){
            return;
        }
        
        $controller = $controllerArray[0];
        $methodName = $controllerArray[1];
        
        $controllerReflection = new \ReflectionClass($controller);
        $annotationBags = $this->getMethodAnnotationBags($controllerReflection, $methodName);
        
        $this->annotationHandler->handle($event, $annotationBags);
    }

    /**
     * @param \ReflectionClass $controller
     * @param $currentMethodName
     * @return array
     */
    protected function getMethodAnnotationBags(\ReflectionClass $controller, $currentMethodName)
    {
        $bags = array();
        
        foreach($controller->getMethods() as $methodReflection){
            $annotation = $this->reader->getMethodAnnotation($methodReflection, $this->annotationClassName);
            
            if($annotation){
                if($methodReflection->getName() == $currentMethodName){
                    $annotation->setIsCurrentMethod(true);
                }
                
                $bag = new AnnotationBag(
                    $annotation,
                    $this->reader->getMethodAnnotations($methodReflection)
                );
                $annotation->setAnnotationBag($bag);
                
                $bags[] = $bag;
            }
        }
        
        return $bags;
    }
}