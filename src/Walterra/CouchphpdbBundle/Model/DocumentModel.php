<?php

namespace Walterra\CouchphpdbBundle\Model;

use Walterra\CouchphpdbBundle\Model\DatabaseModel;

class DocumentModel
{
   static public function fetchDocument($dbname, $docname, $controller){
       $conn = $controller->get('database_connection');
        
       $q = $conn->createQueryBuilder();
       $doc = $q->select('*')
          ->from($dbname, 'docs')
          ->setMaxResults(1)
          ->where('id = :docname')
          ->setParameter('docname', $docname)
          ->execute();
        
       return $doc->fetchAll();
    }
   
    static public function insertDocument($dbname, $doc, $controller)
    {
        // create an UUID and add it to the document
        $doc["_id"] = DatabaseModel::getUUID();
        // just a dummy for now so something's there
        $doc["_rev"] = "1-" . $doc["_id"];

        // add document to database
        $document = array(
            "id" => $doc["_id"],
            "body" => json_encode($doc)
        );
        $conn = $controller->get('database_connection');
        $conn->insert($dbname, $dbData);
    }

    static public function getAllDocs($dbname, $controller, $includeDocs = false)
    {
        // let's get all the docs!

        $conn = $controller->get('database_connection');
        $q = $conn->createQueryBuilder();
        $q->select('*')
          ->from($dbname, 'docs')
          ->orderBy('docs.id', self::getOrder($controller));
            
        // apply LIMIT
        $request = $controller->getRequest();
        if ((int)$request->query->get('limit') > 0) 
            $q->setMaxResults((int)$request->query->get('limit'));
            
        // apply filters using startkey and endkey
        if($request->query->get('startkey') != NULL){
            $q->where('id >= :startkey');
            $q->setParameter('startkey', json_decode($request->query->get('startkey')));
            if($request->query->get('endkey') != NULL){
                $q->andWhere('id <= :endkey');
                $q->setParameter('endkey', json_decode($request->query->get('endkey')));
            }
        }
        
        $rows = $q->execute();
        
        $transformedRows = array();
        foreach($rows as $row){
            $doc = json_decode($row["body"], true);
            if($doc != NULL){
                $transformedDoc = array(
                    "id" => $row["id"],
                    "key" => $row["id"],
                    "value" => array( "rev" => $doc["_rev"] )
                );
                if($request->query->get('include_docs') == 'true' || $includeDocs){
                    $transformedDoc["doc"] = $doc;
                }
                $transformedRows[] = $transformedDoc;
            }
        }
        return $transformedRows;
    }
    
    static public function getOrder($controller){
        $request = $controller->getRequest();
        return ($request->query->get('descending') == 'true') ? "DESC" : "ASC";
    }
}
