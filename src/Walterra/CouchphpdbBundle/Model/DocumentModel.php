<?php

namespace Walterra\CouchphpdbBundle\Model;

use Walterra\CouchphpdbBundle\Model\DatabaseModel;

class DocumentModel
{
   static public function fetchDocument($dbname, $docname, $controller){
       $connection = $controller->get('database_connection');
        
       $q = $connection->createQueryBuilder();
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
        $olddoc = array();
        // check if document exists
        if(isset($doc["_id"])){
            $olddoc = self::fetchDocument($dbname, $doc["_id"], $controller);
        } else {
            // create an UUID and add it to the document
            $doc["_id"] = DatabaseModel::getUUID();
        }
        // just a dummy for now so something's there
        $doc["_rev"] = "1-" . $doc["_id"];

        // prepare document
        $dbData = array(
            "id" => $doc["_id"],
            "body" => json_encode($doc)
        );

        // add/update document within database
        $connection = $controller->get('database_connection');
        if(count($olddoc) == 0){
            // create new document
            $connection->insert($dbname, $dbData);
        } else {
            // update existing document
            $connection->update($dbname, $dbData, array('id' => $doc["_id"]));
        }
        
        return array(
            "ok" => true,
            "id" => $doc["_id"],
            "rev" => $doc["_rev"]
        );
    }

    static public function getAllDocs($dbname, $controller, $includeDocs = false)
    {
        // let's get all the docs!

        $connection = $controller->get('database_connection');
        $q = $connection->createQueryBuilder();
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
