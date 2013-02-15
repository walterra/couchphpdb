<?php

namespace Walterra\CouchphpdbBundle\Model;

class DatabaseModel
{
    static public function checkDB($dbName, $controller)
    {
        $checkDB = (object)array();
        if(!self::isValidDbName($dbName)){
            // illegal database name error message
            $checkDB->data = array(
                "error" => "illegal_database_name",
                "reason" => "Only lowercase characters (a-z), digits (0-9), and any of the characters _, $, (, ), +, -, and / are allowed. Must begin with a letter."
            );
            $checkDB->statusCode = 400;
        } else {
            if(!self::dbExists($dbName, $controller)){
                $checkDB->data = array(
                    "error" => "not_found",
                    "reason" => "no_db_file"
                );
                $checkDB->statusCode = 404;
            } else {
                $checkDB->ok = true;
            }
        }
        return $checkDB;
    }
    
    static public function isValidDbName($dbName)
    {
        // check if the database name is valid
        $dbNameRegex = "/^[a-z][a-z0-9\_\$()\+\-\/]*$/";
        preg_match($dbNameRegex, $dbName, $matches);
        return (count($matches) == 0) ? false : true;
    }
    
    static public function dbExists($dbName, $controller)
    {
        $connection = $controller->get('database_connection');
        $dbs = $connection->fetchAll("SHOW TABLES LIKE '".$dbName."'");
        return (count($dbs) == 0) ? false : true;
    }

    static public function getRowCount($dbname, $controller)
    {
        $connection = $controller->get('database_connection');
        $c = $connection->createQueryBuilder()
                  ->select('docs.id') 
                  ->from($dbname, 'docs')
                  ->execute();
        return $c->rowCount();
    }

    static public function getDiskSize($dbname, $controller)
    {
        $connection = $controller->get('database_connection');
     
        $size = $connection->createQueryBuilder()
                     ->select('(data_length+index_length) tablesize') 
                     ->from('information_schema.tables', 't')
                     ->where('t.table_schema=\'couchphpdb\'')
                     ->andWhere('t.table_name=\''.$dbname.'\'')
                     ->execute();
                            
        $size = $size->fetchAll();
        return $size[0]["tablesize"];
    }

    static public function createDb($dbName, $controller)
    {
        $connection = $controller->get('database_connection');
        
        // via http://backchannel.org/blog/friendfeed-schemaless-mysql
        $connection->query("CREATE TABLE ".$dbName." (
            added_id INT NOT NULL AUTO_INCREMENT PRIMARY KEY,
            id VARCHAR(256) NOT NULL,
            updated TIMESTAMP NOT NULL,
            body MEDIUMBLOB,
            UNIQUE KEY (id),
            KEY (updated)
        ) ENGINE=InnoDB;");
        
        return (self::dbExists($dbName, $controller)) ? true : false;
    }
    
    static public function deleteDb($dbName, $controller)
    {
        $connection = $controller->get('database_connection');
        
        // via http://stackoverflow.com/questions/8526534/how-to-truncate-a-table-using-doctrine-in-symfony
        $platform   = $connection->getDatabasePlatform();
        return $connection->executeUpdate($platform->getDropTableSQL($dbName, true /* whether to cascade */));
    }

    static public function getAllDbs($controller)
    {
        $connection = $controller->get('database_connection');

        $dbs = $connection->fetchAll("SHOW TABLES");
        $data = array();
        foreach($dbs as $db){
            foreach($db as $table)
                $data[] = $table;
        }
        return $data;
    }

    static public function getUUID()
    {
        // via http://stackoverflow.com/a/5439548/593957
        return substr(str_shuffle(str_repeat("0123456789abcdef", 32)), 0, 32);
    }
}
