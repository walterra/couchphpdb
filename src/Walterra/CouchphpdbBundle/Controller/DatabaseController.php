<?php

namespace Walterra\CouchphpdbBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\FOSRestController;
use Walterra\CouchphpdbBundle\Model\DatabaseModel;

class DatabaseController extends FOSRestController
{
    public function dbAction($dbname)
    {
        $statusCode = 500;
        $data = "";
        if(!DatabaseModel::isValidDbName($dbname)){
            // illegal database name error message
            $data = array(
                "error" => "illegal_database_name",
                "reason" => "Only lowercase characters (a-z), digits (0-9), and any of the characters _, $, (, ), +, -, and / are allowed. Must begin with a letter."
            );
            $statusCode = 400;
        } else {
            $request = $this->get("request");
            switch($request->getMethod()) {
                case "GET":
                    // check if database exists
                    if(!DatabaseModel::dbExists($dbname, $this)){
                        $data = array(
                            "error" => "not_found",
                            "reason" => "no_db_file"
                        );
                        $statusCode = 404;
                    } else {
                        // return db info
                        // TODO output more real data
                        $data = array(
                            "db_name" => "demo",
                            "doc_count" => DatabaseModel::getRowCount($dbname, $this),
                            "doc_del_count" => 0,
                            "update_seq" => 1,
                            "purge_seq" => 0,
                            "compact_running" => false,
                            "disk_size" => DatabaseModel::getDiskSize($dbname, $this),
                            "data_size" => rand(0,500),
                            "instance_start_time" => "1359405301602959",
                            "disk_format_version" => 6,
                            "committed_update_seq" => 1
                        );
                        $statusCode = 200;
                    }
                    break;
                    
                case "PUT":
                    // check if database exists
                    if(!DatabaseModel::dbExists($dbname, $this)){
                        $r = DatabaseModel::createDb($dbname, $this);
                        $data = array(
                            "ok" => $r
                        );
                        $statusCode = 201;
                    } else {
                        $data = array(
                            "error" => "file_exists",
                            "reason" => "The database could not be created, the file already exists."
                        );
                        $statusCode = 412;
                    }
                    break;
                    
                case "POST":
                    // TODO validate input!!
                    
                    // add a document to the DB
                    $doc = array();
                    $content = $this->get("request")->getContent();
                    if (!empty($content)) $doc = json_decode($content, true); // 2nd param to get as array
                   
                    DatabaseModel::insertDocument($dbname, $doc, $controller);

                    // prepare response
                    $data = array(
                        "ok" => true,
                        "id" => $doc["_id"],
                        "rev" => $doc["_rev"]
                    );
                    $statusCode = 200;
                    
                    break;
            }
        }

        $view = $this->view($data, $statusCode);
        $view->setFormat('json');
        return $this->get('fos_rest.view_handler')->handle($view);  
    }

    public function allDbAction()
    {
        $view = $this->view(
            DatabaseModel::getAllDbs($this), 
            200
        );
        $view->setFormat('json');
        return $this->get('fos_rest.view_handler')->handle($view);  
    }
    
    public function uuidsAction()
    {
        $request = $this->getRequest();
        $count = 1;
        if($request->query->get('count') > 0) $count = $request->query->get('count');

        $s = array();
        for($i=1;$i<=$count;$i++){
            $s[] = DatabaseModel::getUUID();
        }
        
        $data = array(
            "uuids" => $s
        );
        
        $view = $this->view($data, 200);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);  
    }
}

