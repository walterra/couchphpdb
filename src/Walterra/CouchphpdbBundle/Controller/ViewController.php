<?php

namespace Walterra\CouchphpdbBundle\Controller;

use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use FOS\RestBundle\Controller\FOSRestController;
use Walterra\CouchphpdbBundle\Model\DatabaseModel;
use Walterra\CouchphpdbBundle\Model\DocumentModel;
use Walterra\J4p5Bundle\j4p5\js;
use Walterra\J4p5Bundle\j4p5\output;
use Walterra\J4p5Bundle\j4p5\jsrt\js_val;
use Symfony\Component\HttpFoundation\Response;

class ViewController extends FOSRestController
{
    public function viewAction($dbname,$designdocname,$viewname)
    {
        $statusCode = 404;
        $data = "";

        $checkDB = DatabaseModel::checkDB($dbname, $this);
        if($checkDB->ok == true){
            $doc = DocumentModel::fetchDocument($dbname, "_design/" . $designdocname, $this);
            if(count($doc) == 0){
                $statusCode = 404;
                $data = array(
                    "error" => "not found",
                    "reason" => "missing"
                );
            } else {
                $doc = json_decode($doc[0]["body"], true);
                $view = $doc["views"][$viewname];
                $mapCode = (array_key_exists("map", $view)) ? $view["map"] : "";
                $reduceCode = (array_key_exists("reduce", $view)) ? $view["reduce"] : "";
                $allDocs = DocumentModel::getAllDocs($dbname, $this, true); // last argument true gets the actual documents

                $transformedDocs = array();
                foreach($allDocs as $viewDoc)
                {
                    // let's skip design documents
                    if(substr( $viewDoc["id"], 0, 8 ) !== "_design/")
                    {
                        $viewDocJson = json_encode($viewDoc["doc"], true);
                    
// wrap the document and map function from 'couchdb' in 
// javascript wrapper code which we may execute via j4p5
$code = <<<EOD

var doc = $viewDocJson;

function emit(key, value){
    couchphpdb.output(key, value);
};

var map = $mapCode;

map(doc);
    
EOD;
                        // define this special external function so we may access the output
                        // from the map function later on
                        js::define("couchphpdb", array("output" => "js_output"));

                        // let's buffer the output from the javascript interpreter
                        ob_start(); 
                        // run the js code.
                        js::run($code);
                        // flush the buffer, we don't need it and access data via the output class
                        ob_get_clean();
                    
                        $out = output::getInstance(); // get class instance
                        $emits = $out->getAll();
                        foreach($emits as $emit)
                        {
                            $transformedDocs[] = array(
                                "id" => $viewDoc["id"],
                                "key" => json_decode($this->printJS($emit["key"]), true),
                                "value" => json_decode($this->printJS($emit["value"]), true)
                            );
                        }
                        $out->resetMap();
                    }
                }
                
                // check if we need to do map+reduce
                $request = $this->getRequest();
                if($request->query->get('group') == 'true'){
                    $data = array(
                        "rows" => $this->reduce($transformedDocs, $reduceCode)
                    );
                    $statusCode = 200;
                } else {
                    $data = array(
                        "total_rows" => count($transformedDocs),
                        "offset" => 0, // TODO implement
                        "rows" => $transformedDocs
                    );
                    $statusCode = 200;
                }
                
                // we do it this way, seems FOS doesn't render null values in JSON (skips the whole value including the key)
                $response = new Response();
                $response->setContent(json_encode($data));
                $response->headers->set('Content-Type', 'application/json');
                
                return $response;
            }
        } else {
            $data = $checkDB->data;
            $statusCode = $checkDB->statusCode;
        }

        $view = $this->view($data, $statusCode);
        $view->setFormat('json');
        
        return $this->get('fos_rest.view_handler')->handle($view);  
    }
    
    private function reduce($docs, $code) {
        switch($code) {
            // do the reduce job
            case "_sum":
                $t = array_reduce($docs, function($result, $item) { 
                    if(!array_key_exists($item["key"], $result))
                        $result[$item["key"]] = $item["value"];
                    else
                        $result[$item["key"]] += $item["value"];
                    return $result; 
                }, array());
                break;
            case "_count":
                $t = array_reduce($docs, function($result, $item) { 
                    if(!array_key_exists($item["key"], $result))
                        $result[$item["key"]] = 1;
                    else
                        $result[$item["key"]] += 1;
                    return $result; 
                }, array());
                break;
            case "_stats":
                $t = array_reduce($docs, function($result, $item) { 
                    if(!array_key_exists($item["key"], $result)) {
                        $result[$item["key"]] = array(
                            "sum" => $item["value"],
                            "count" => 1,
                            "min" => $item["value"],
                            "max" => $item["value"],
                            "sumsqr" => $item["value"] * $item["value"]
                        );
                    } else {
                        $i = & $result[$item["key"]];
                        $i["sum"] += $item["value"];
                        $i["count"] += 1;
                        $i["min"] = min($item["value"], $i["min"]);
                        $i["max"] = max($item["value"], $i["max"]);
                        $i["sumsqr"] +=  $item["value"] * $item["value"];
                    }
                    return $result; 
                }, array());
                break;
            default:
                // TODO here we would do a reduce job based on javascript code
        }
        // transform data to match couchdb format
        $rows = array();
        foreach($t as $key => $value)
            array_push($rows, array(
                "key" => $key,
                "value" => $value
            ));
        return $rows;
    }
    
    private function printJS($data, $d=1) {
        switch($data->type) {
            case js_val::UNDEFINED: return "undefined";
            case js_val::NULL: return "null";
            case js_val::BOOLEAN: return $data->value?"true":"false";
            case js_val::NUMBER: return $data->value;
            case js_val::STRING: return "\"". $data->value . "\"";
            case js_val::OBJECT:
                $s = "{\n";
                $r = str_repeat(" ", $d*4);
                $sa = array();
                $d++;
                foreach ($data->slots as $key=>$value) {
                    $sa[] = $r . "\"" . $key . "\" : " . $this->printJS($value->value, $d);
                }
                $d--;
                $s .= implode(",\n", $sa);
                $s .= "\n". str_repeat(" ", ($d-1)*4) . "}";
                return $s;
        }
    }
}

?>