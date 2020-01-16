<?php
namespace Test\Lucinda\SQL;

use Lucinda\SQL\Connection;
use Lucinda\SQL\DataSourceDetection;
use Lucinda\UnitTest\Result;
use Lucinda\SQL\ConnectionException;
use Lucinda\SQL\StatementException;

class ConnectionTest
{
    private $connection;
    
    public function __construct()
    {
        $this->connection = new Connection();
    }

    public function connect()
    {
        $results = [];
        
        $detector = new DataSourceDetection(\simplexml_load_file(dirname(__DIR__)."/unit-tests.xml")->sql->local->server);
        $dataSource = $detector->getDataSource();
        
        $dataSource->setUserName("asd");
        try {
            $this->connection->connect($dataSource);
            $results[] = new Result(false, "incorrect credentials");
        } catch (ConnectionException $e) {
            $results[] = new Result(true, "incorrect credentials");
        }
        
        $dataSource->setUserName("unit_test");
        $this->connection->connect($dataSource);
        $results[] = new Result(true, "correct credentials");
        
        return $results;
    }
        

    public function transaction()
    {
        $transaction = $this->connection->transaction();
        $transaction->begin();
        $this->connection->statement()->execute("UPDATE users SET first_name='Jones' WHERE id=1");
        $transaction->rollback();
        return new Result($this->connection->statement()->execute("SELECT first_name FROM users WHERE id=1")->toValue()=="John");
    }
        

    public function statement()
    {
        $results = [];
        
        $statement = $this->connection->statement();
        
        try {
            $statement->execute("SELECT first_name FROM users WHERE iad=1");
            $results[] = new Result(false, "incorrect query");
        } catch (StatementException $e) {
            $results[] = new Result(true, "incorrect query");
        }
        
        $results[] = new Result($statement->execute("SELECT first_name FROM users WHERE id=1")->toValue()=="John", "correct query");
        
        return $results;
    }
        

    public function preparedStatement()
    {
        $results = [];
        
        
        try {
            $statement = $this->connection->preparedStatement();
            $statement->prepare("SELECT first_name FROM users WHERE iad=:id");
            $statement->execute([":id"=>1]);
            $results[] = new Result(false, "incorrect query");
        } catch (StatementException $e) {
            $results[] = new Result(true, "incorrect query");
        }
        
        $statement = $this->connection->preparedStatement();
        $statement->prepare("SELECT first_name FROM users WHERE id=:id");
        $results[] = new Result($statement->execute([":id"=>1])->toValue()=="John", "correct query");
        
        return $results;
    }


    public function keepAlive()
    {
        $this->connection->keepAlive();
        return new Result(true);
    }
        

    public function reconnect()
    {
        $this->connection->reconnect();
        return new Result(true);
    }
        

    public function disconnect()
    {
        $this->connection->disconnect();
        return new Result(true);
    }
}
