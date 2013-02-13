<?php

namespace Walterra\CouchphpdbBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\FOSRestController;
use Walterra\CouchphpdbBundle\Commons;

class SessionController extends FOSRestController
{
    public function loginAction()
    {
        $statusCode = 200;
        
        // dummy!
        // {"ok":true,"userCtx":{"name":null,"roles":["_admin"]},"info":{"authentication_db":"_users","authentication_handlers":["oauth","cookie","default"],"authenticated":"default"}}
          
        $data = array(
            "ok" => true,
            "userCtx" => array(
                "name" => null,
                "roles" => array("_admin")
            ),
            "info" => array(
                "authentication_db" => "_users",
                "authentication_handlers" => array("oauth","cookie","default"),
                "authenticated" => "default"
            )
        );
        
        $view = $this->view($data, $statusCode);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);  
    }
}
