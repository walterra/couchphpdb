<?php

namespace Walterra\CouchphpdbBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\FOSRestController;

class WelcomeController extends FOSRestController
{
    /**
     * @Rest\View
     */
    public function indexAction()
    {
        $data = array(
            "couchdb" => "Welcome",
            "version" => "couchphpdb_0.0.1pre-alpha"
        );

        $view = $this->view($data, 200);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);  
    }
}
