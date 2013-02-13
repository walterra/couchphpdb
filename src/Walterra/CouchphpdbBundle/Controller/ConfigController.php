<?php

namespace Walterra\CouchphpdbBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\FOSRestController;
use Walterra\CouchphpdbBundle\Commons;

class ConfigController extends FOSRestController
{
    public function nativeQueryServersAction()
    {
        $statusCode = 200;
        
        $data = array();
        
        $view = $this->view($data, $statusCode);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);  
    }

    public function queryServersAction()
    {
        $statusCode = 200;
        
        // normally this points to the executable, we just indicate we'll use j4p5 internally
        $data = array(
            "javascript" => "j4p5"
        );
        
        $view = $this->view($data, $statusCode);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);  
    }
}
