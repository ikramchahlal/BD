<?php
class Database {
    private $host = DB_HOST;
    private $user = DB_USER;
    private $pass = DB_PASS;
    private $dbname = DB_NAME;
    
    private $dbh;
    private $stmt;
    private $error;
    
    public function __construct() {
        $dsn = 'mysql:host=' . $this->host . ';dbname=' . $this->dbname . ';charset=utf8mb4';
        $options = array(
            PDO::ATTR_PERSISTENT => true,
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_OBJ,
            PDO::ATTR_EMULATE_PREPARES => false
        );
        
        try {
            $this->dbh = new PDO($dsn, $this->user, $this->pass, $options);
        } catch(PDOException $e) {
            $this->error = $e->getMessage();
            error_log('Database Connection Error: ' . $this->error);
            throw new PDOException('Erreur de connexion à la base de données');
        }
    }
    
    public function query($sql) {
        try {
            $this->stmt = $this->dbh->prepare($sql);
        } catch (PDOException $e) {
            error_log('Query Preparation Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function bind($param, $value, $type = null) {
        try {
            if (is_null($type)) {
                switch (true) {
                    case is_int($value):
                        $type = PDO::PARAM_INT;
                        break;
                    case is_bool($value):
                        $type = PDO::PARAM_BOOL;
                        break;
                    case is_null($value):
                        $type = PDO::PARAM_NULL;
                        break;
                    default:
                        $type = PDO::PARAM_STR;
                }
            }
            $this->stmt->bindValue($param, $value, $type);
        } catch (PDOException $e) {
            error_log('Parameter Binding Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function execute() {
        try {
            return $this->stmt->execute();
        } catch(PDOException $e) {
            error_log('Query Execution Error: ' . $e->getMessage());
            
            ob_start();
            $this->stmt->debugDumpParams();
            $debug = ob_get_clean();
            error_log('PDO Debug: ' . $debug);
            
            if (strpos($e->getMessage(), 'Duplicate entry') !== false) {
                throw new PDOException("Erreur de doublon: la référence existe déjà", 1062);
            }
            
            throw $e;
        }
    }
    
    public function resultSet() {
        try {
            $this->execute();
            return $this->stmt->fetchAll();
        } catch (PDOException $e) {
            error_log('Result Set Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function single() {
        try {
            $this->execute();
            return $this->stmt->fetch();
        } catch (PDOException $e) {
            error_log('Single Result Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function rowCount() {
        try {
            return $this->stmt->rowCount();
        } catch (PDOException $e) {
            error_log('Row Count Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function lastInsertId() {
        try {
            return $this->dbh->lastInsertId();
        } catch (PDOException $e) {
            error_log('Last Insert ID Error: ' . $e->getMessage());
            throw $e;
        }
    }
    
    public function beginTransaction() {
        return $this->dbh->beginTransaction();
    }
    
    public function commit() {
        return $this->dbh->commit();
    }
    
    public function rollBack() {
        return $this->dbh->rollBack();
    }
    
    public function debugDumpParams() {
        return $this->stmt->debugDumpParams();
    }
    public function getQueryString() {
    return $this->stmt->queryString;
}
}