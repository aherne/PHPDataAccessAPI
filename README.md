# SQL Data Access API

Table of contents:

- [About](#about)
- [Configuration](#configuration)
- [Execution](#execution)
- [Installation](#installation)
- [Unit Tests](#unit-tests)
- [Examples](#examples)
- [Reference Guide](#reference-guide)

## About 

This API is a ultra light weight [Data Access Layer](https://en.wikipedia.org/wiki/Data_access_layer) built on top of [PDO](https://www.php.net/manual/en/book.pdo.php) and inspired by [JDBC](https://en.wikipedia.org/wiki/Java_Database_Connectivity) in terms of architecture. As a data access layer, its purpose is to 
to shield complexity of working with different SQL vendors and provide a simple and elegant interface for connecting, querying and parsing query results that overcomes PDO design flaws (such as chaotic architecture and functionality).

![diagram](https://www.lucinda-framework.com/public/images/svg/sql-data-access-api.svg)

The whole idea of working with SQL databases (vendors) is reduced to following steps:

- **[configuration](#configuration)**: setting up an XML file where SQL vendors used by your site are configured per development environment
- **[execution](#execution)**: using [Lucinda\SQL\Wrapper](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/Wrapper.php) to read above XML based on development environment, compile [Lucinda\SQL\DataSource](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/DataSource.php) object(s) storing connection information and inject them statically into
[Lucinda\SQL\ConnectionSingleton](#class-connectionsingleton) or [Lucinda\SQL\ConnectionFactory](#class-connectionfactory) classes to use in querying

API is fully PSR-4 compliant, only requiring PHP7.1+ interpreter, SimpleXML and PDO extensions. To quickly see how it works, check:

- **[installation](#installation)**: describes how to install API on your computer, in light of steps above
- **[unit tests](#unit-tests)**: API has 100% Unit Test coverage, using [UnitTest API](https://github.com/aherne/unit-testing) instead of PHPUnit for greater flexibility
- **[examples](#examples)**: shows a number of examples in how to implement CRUD queries using this API

## Configuration

To configure this API you must have a XML with a **sql** tag inside:

```xml
<sql>
	<{ENVIRONMENT}>
		<server name="..." driver="..." host="..." port="..." username="..." password="..." schema="..." charset="..."/>
		...
	</{ENVIRONMENT}>
	...
</sql>
```

Where:

- **sql**: holds global connection information for SQL servers used
    - {ENVIRONMENT}: name of development environment (to be replaced with "local", "dev", "live", etc)
        - **server**: stores connection information about a single server via attributes:
            - *name*: (optional) unique identifier. Required if multiple sql servers are used for same environment!
            - *driver*: (mandatory) PDO driver name (pdo drivers)
            - *host*: (mandatory) server host name.
            - *port*: (optional) server port. If not set, default server port is used.
            - *username*: (mandatory) user name to use in connection.
            - *password*: (mandatory) password to use in connection.
            - *schema*: (optional) default schema to use after connecting.
            - *charset*: (optional) default charset to use in queries after connecting.
            - *autocommit*: (not recommended) whether or not INSERT/UPDATE operations should be auto-committed (value can be: 0 or 1). Not supported by all vendors!
            - *persistent*: (not recommended) whether or not connections should be persisted across sections (value can be: 0 or 1). Not supported by all vendors!
            - *timeout*: (not recommended) time in seconds by which idle connection is automatically closed. Not supported by all vendors!

Example:

```xml
<sql>
    <local>
        <server driver="mysql" host="localhost" port="3306" username="root" password="" schema="example" charset="utf8"/>
    </local>
    <live>
        <server driver="mysql" host="localhost" port="3306" username="hello" password="world" schema="example" charset="utf8"/>
    </live>
</sql>
```

## Execution

Once you have completed step above, you need to run this in order to be able to connect and query database(s) later on:

```php
new Lucinda\SQL\Wrapper(simplexml_load_file(XML_FILE_NAME), DEVELOPMENT_ENVIRONMENT);
```

This will wrap each **server** tag found for current development environment into [Lucinda\SQL\DataSource](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/DataSource.php) objects and inject them statically into:

- [Lucinda\SQL\ConnectionSingleton](#class-connectionsingleton): if your application uses a single SQL server per environment (the usual case)
- [Lucinda\SQL\ConnectionFactory](#class-connectionfactory): if your application uses multiple SQL servers per environment (in which case **server** tags must have *name* attribute)

Both classes above insure a single [Lucinda\SQL\Connection](#class-connection) is reused per server throughout session (input-output request flow) duration. To use that connection in querying, following methods are available:

- **statement**: returns a [Lucinda\SQL\Statement](#class-statement) object to use in creation and execution of a sql statement
- **preparedStatement**: returns a [Lucinda\SQL\PreparedStatement](#class-preparedstatement) object to use in creation and execution of a sql prepared statement
- **transaction**: returns a [Lucinda\SQL\Transaction](#class-transaction) object to use in wrapping operations with above two in transactions

Once an SQL statement was executed via *execute* methods above, users are able to process results based on [Lucinda\SQL\StatementResults](#class-statementresults) object returned.

## Installation

First choose a folder where API will be installed then write this command there using console:

```console
composer require lucinda/sql-data-access
```

Then create a *configuration.xml* file holding configuration settings (see [configuration](#configuration) above) and a *index.php* file (see [initialization](#initialization) above) in project root with following code:

```php
require(__DIR__."/vendor/autoload.php");
new Lucinda\SQL\Wrapper(simplexml_load_file("configuration.xml"), "local");
```

Then you are able to query server, as in below example:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$users = $connection->statement("SELECT id, name FROM users")->toMap("id", "name");
```

## Unit Tests

For tests and examples, check following files/folders in API sources:

- [unit-tests.sql](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/unit-tests.xml): SQL commands you need to run ONCE on server (assuming MySQL) before unit tests execution
- [test.php](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/test.php): runs unit tests in console
- [unit-tests.xml](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/unit-tests.xml): sets up unit tests and mocks "sql" tag
- [tests](https://github.com/aherne/php-sql-data-access-api/tree/v3.0.0/tests): unit tests for classes from [src](https://github.com/aherne/php-sql-data-access-api/tree/v3.0.0/src) folder

## Examples

### INSERT

Example of processing results of an INSERT query:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$resultSet = $connection->statement("INSERT INTO users (first_name, last_name) VALUES ('John', 'Doe')");
$lastInsertID = $resultSet->getInsertId();
```

### UPDATE/DELETE

Example of processing results of an UPDATE/DELETE query:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$resultSet = $connection->statement("UPDATE users SET first_name='Jane' WHERE id=1");
if($resultSet->getAffectedRows()>0) {
    // update occurred
}
```

### SELECT

Example of getting a single value from SELECT resultset:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$firstName = $connection->statement("SELECT first_name FROM users WHERE id=1")->toValue();
```

Example of parsing SELECT resultset row by row:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$resultSet = $connection->statement("SELECT * FROM users");
while ($row = $resultSet->toRow()) {
    // process row
}
```

Example of getting all values of first column from SELECT resultset:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$ids = $connection->statement("SELECT id FROM users")->toColumn();
```

Example of getting all rows from SELECT resultset as array where value of first becomes key and value of second becomes value:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$users = $connection->statement("SELECT id, name FROM users")->toMap("id", "name");
// above is an array where id of user becomes key and name becomes value
```

Example of getting all values from SELECT resultset:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$users = $connection->statement("SELECT * FROM users")->toList();
// above is an array containing all rows, each as column-value associative array
```

## Reference Guide

### Class Connection

[Lucinda\SQL\Connection](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/Connection.php) can be used to execute operations on a connection.

Following methods are relevant to connection management (HANDLED BY API AUTOMATICALLY, so **to be used only in niche situations**):

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| connect | [Lucinda\SQL\DataSource](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/DataSource.php) | void | Connects to database server based on data source. Throws [Lucinda\SQL\ConnectionException](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/ConnectionException.php) if connection fails! |
| disconnect | void | void | Closes connection to database server. |
| reconnect | void | void | Closes then opens connection to database server based on stored data source. Throws [Lucinda\SQL\ConnectionException](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/ConnectionException.php) if connection fails! |
| keepAlive | void | void | Restores connection to database server in case it got closed unexpectedly. Throws [Lucinda\SQL\ConnectionException](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/ConnectionException.php) if connection fails! |

Following methods are relevant for querying:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| statement | void | [Lucinda\SQL\Statement](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/Statement.php) | Creates a statement to use in querying. |
| preparedStatement | void | [Lucinda\SQL\PreparedStatement](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/PreparedStatement.php) | Creates a prepared statement to use in querying. |
| transaction | void | [Lucinda\SQL\Transaction](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/Transaction.php) | Creates a transaction wrap above operations with. |

### Class ConnectionSingleton

[Lucinda\SQL\ConnectionSingleton](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/ConnectionSingleton.php) class insures a single [Lucinda\SQL\Connection](#class-connection) is used per session. Has following static methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| static setDataSource | [Lucinda\SQL\DataSource](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/DataSource.php) | void | Sets data source detected beforehand. Done automatically by API! |
| static getInstance | void | [Lucinda\SQL\Connection](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/Connection.php) | Connects to server based on above data source ONCE and returns connection for later querying. Throws [Lucinda\SQL\ConnectionException](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/ConnectionException.php) if connection fails! |

Usage example:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$connection->statement()->execute("UPDATE users SET name='John' WHERE name='Jane'");
```

Please note this class closes all open connections automatically on destruction!

### Class ConnectionFactory

[Lucinda\SQL\ConnectionFactory](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/ConnectionFactory.php) class insures single [Lucinda\SQL\Connection](#class-connection) per session and server name. Has following static methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| static setDataSource | string $serverName, [Lucinda\SQL\DataSource](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/DataSource.php) | void | Sets data source detected beforehand per value of *name* attribute @ **server** tag. Done automatically by API! |
| static getInstance | string $serverName | [Lucinda\SQL\Connection](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/Connection.php) | Connects to server based on above data source ONCE and returns connection for later querying. Throws [Lucinda\SQL\ConnectionException](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/ConnectionException.php) if connection fails! |

Usage example:

```php
$connection = Lucinda\SQL\ConnectionFactory::getInstance("myServer");
$conection->statement()->execute("UPDATE users SET name='John' WHERE name='Jane'");
```

### Class Statement

[Lucinda\SQL\Statement](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/Statement.php) implements normal SQL unprepared statement operations and comes with following public methods:


| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| quote | mixed $value | void | Escapes and quotes value against SQL injection. |
| execute | string $query | [Lucinda\SQL\StatementResults](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/StatementResults.php) | Executes query and returns results. Throws [Lucinda\SQL\StatementException](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/StatementException.php) if execution fails! |

Usage example:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$statement = $connection->statement();
$resultSet = $statement->execute("SELECT id FROM users WHERE name='".$statement->quote($name)."'");
```

Please note this class closes all open connections automatically on destruction!

### Class PreparedStatement

[Lucinda\SQL\PreparedStatement](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/PreparedStatement.php) implements SQL prepared statement operations and comes with following public methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| prepare | string $query | void |  Prepares query for execution. |
| bind | string $parameter, mixed $value, int $dataType=\PDO::PARAM_STR | void | Binds parameter to prepared query. |
| execute | array $boundParameters = array()  | [Lucinda\SQL\StatementResults](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/StatementResults.php) | Executes query and returns results. Throws [Lucinda\SQL\StatementException](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/StatementException.php) if execution fails! |

Usage example:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$preparedStatement = $connection->preparedStatement();
$preparedStatement->prepare("SELECT id FROM users WHERE name=:name");
$preparedStatement->bind(":name", $name);
$resultSet = $preparedStatement->execute();
```

### Class Transaction

[Lucinda\SQL\Transaction](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/Transaction.php) can wrap *execute* methods of two classes above in transactions, in order to maintain data integrity, and thus comes with following public methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| begin | void | void | Starts a transaction. |
| commit | void | void | Commits transaction. |
| rollback | void | void | Rolls back transaction. |

Usage example:

```php
$connection = Lucinda\SQL\ConnectionSingleton::getInstance();
$transaction = $connection->transaction();
$transaction->begin();
$connection->statement()->execute("UPDATE users SET name='John Doe' WHERE id=1");
$transaction->commit();
```

### Class StatementResults

[Lucinda\SQL\StatementResults](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/src/StatementResults.php) encapsulates patterns of processing results of sql statement execution and comes with following public methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getInsertId | void | string | Gets last insert id following INSERT statement execution. |
| getAffectedRows | void | int | Gets affected rows following UPDATE/DELETE statement execution. |
| toValue | void | string | Gets value of first column & row in resultset following SELECT statement execution. |
| toRow | void | array|false | Gets next row from resultset as column-value associative array following SELECT statement execution. |
| toColumn | void | array | Gets first column in resulting rows following SELECT statement execution. |
| toMap | string $columnKeyName, string $columnValueName | array | Gets two columns from resulting rows, where value of one becomes key and another as value, following SELECT statement execution. |
| toList | void | array | Gets all resulting rows, each as column-value associative array, following SELECT statement execution. |

Usage examples of above methods can be seen below or in [unit tests](https://github.com/aherne/php-sql-data-access-api/blob/v3.0.0/tests/StatementResultsTest.php)!
