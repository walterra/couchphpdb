<?php

namespace Walterra\CouchphpdbBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\FOSRestController;
use Walterra\CouchphpdbBundle\Model\DatabaseModel;
use Walterra\CouchphpdbBundle\Model\DocumentModel;

class DocumentController extends FOSRestController
{
    public function docAction($dbname,$docname)
    {
        $statusCode = 404;
        $data = "";

        $checkDB = DatabaseModel::checkDB($dbname, $this);
        if(isset($checkDB->ok) && $checkDB->ok == true){
            $request = $this->get("request");
            switch($request->getMethod()) {
                case "GET":
                    // request a document
                    // let's get the doc!
                    $doc = DocumentModel::fetchDocument($dbname, $docname, $this);
                    if(count($doc) == 0){
                        $statusCode = 404;
                        $data = array(
                            "error" => "not found",
                            "reason" => "missing"
                        );
                    } else {
                        $statusCode = 200;
                        $data = json_decode($doc[0]["body"], true);
                    }
                break;
                case "PUT":
                    // create/update a document
                    // TODO validate input!!
                    // TODO we do not support check via ?rev= yet
                    // let's insert/update the doc!
                    $olddoc = DocumentModel::fetchDocument($dbname, $docname, $this);
                    
                    $doc = array();
                    $content = $this->get("request")->getContent();
                    if (!empty($content)) $doc = json_decode($content, true); // 2nd param to get as array

                    DocumentModel::insertDocument($dbname, $doc, $this);

                    // prepare response
                    $data = array(
                        "ok" => true,
                        "id" => $doc["_id"],
                        "rev" => $doc["_rev"]
                    );
                    $statusCode = 201; // created
                break;
                case "DELETE":
                    // delete a document
                    // TODO validate input!!
                    // TODO we do not support check via ?rev= yet
                    $doc = DocumentModel::fetchDocument($dbname, $docname, $this);
                    $doc = json_decode($doc[0]["body"], true);
                    
                    // let's delete the doc!
                    // since we do not support replicating yet we really delete the document
                    // original couchdb doesn't really delete the document but adds a _delete:true flag
                    $conn = $this->get('database_connection');
                    $conn->delete($dbname, array("id" => $docname));
                    
                    // prepare response
                    $data = array(
                        "ok" => true,
                        "rev" => $doc["_rev"]
                    );
                    $statusCode = 200; // created
                break;
            }
        } else {
            $data = $checkDB->data;
            $statusCode = $checkDB->statusCode;
        }

        $view = $this->view($data, $statusCode);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);  
    }
    
    public function allDocsAction($dbname)
    {
        $statusCode = 404;
        $data = "";
        $checkDB = DatabaseModel::checkDB($dbname, $this);
        if($checkDB->ok == true){
            $data = array(
                "total_rows" => DatabaseModel::getRowCount($dbname, $this),
                "offset" => 0,
                "rows" => DocumentModel::getAllDocs($dbname, $this)
            );
            $statusCode = 200;
        } else {
            $data = $checkDB->data;
            $statusCode = $checkDB->statusCode;
        }
        
        $view = $this->view($data, $statusCode);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);  
    }
    
    public function bulkDocsAction($dbname)
    {
        $content = $this->get("request")->getContent();
        $docs = array();
        $data = array();
        if (!empty($content)) $docs = json_decode($content, true); // 2nd param to get as array
        foreach($docs["docs"] as $doc)
            $data[] = DocumentModel::insertDocument($dbname, $doc, $this);

        $statusCode = 200;

        $view = $this->view($data, $statusCode);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);  
    }
    
    public function designDocAction($dbname,$designdocname)
    {
        return $this->docAction($dbname,"_design/".$designdocname);
    }
}
