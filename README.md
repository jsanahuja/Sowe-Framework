# Sowe-Framework

- [**Sowe\Framework\AbstractEntity**](https://github.com/jsanahuja/Sowe-Framework/blob/master/src/AbstractEntity.php) provides CRUD operations interface. `table` name and table primary `key` must be defined as static variables.
   * `__construct(Database $database)`
   * `get($id, $fields = ["*"])`
   * `list($fields = ["*"], $filters = null)`
   * `update($id, $data)`
   * `create($data)`
   * `delete($id)`
Example
```php
class MyEntity extends AbstractEntity{
    protected static $table = "myentity";
    protected static $key = "id";
}

$entities = new MyEntity();
// loads all entities
$all = $entities->list();
```

- [**Sowe\Framework\AbstractObject**](https://github.com/jsanahuja/Sowe-Framework/blob/master/src/AbstractObject.php) extends `AbstractEntity` but also provides resource format interface.
   * `__construct(Database $database)`
   * `new()`
   * `load($id)`
   * `save()`
   * `remove()`
   * `getData($field)`
   * `setData($field, $value)`
Example
```php
class MyResource extends AbstractObject{
    protected static $table = "myresource";
    protected static $key = "id";
}

$resources = new MyResource();
// Updating resource
$resources->load(10)
  ->setData("name", "Jonh")
  ->setData("lastname", "Doe")
  ->save();
```

- [**Sowe\Framework\QueryBuilder**](https://github.com/jsanahuja/Sowe-Framework/blob/master/src/QueryBuilder.php) used by both `AbstractEntity` and `AbstractObject`. Provides a friendly database query builder interface
   * `__construct($type, Database $database)` where $type can be SELECT|UPDATE|INSERT|DELETE
   * `table(string $table, string $alias = null)`
   * `fields(...$fields)`
   * `set($field, $value)`
   * `condition($field, $operator, $value)`
   * `conditions($conditions)`
   * `or()`
   * `limit($offset, $limit = null)`
   * `innerJoin($table, $alias, $field1, $operator, $field2)`
   * `leftJoin($table, $alias, $field1, $operator, $field2)`
   * `rightJoin($table, $alias, $field1, $operator, $field2)`
   * `join($type, $table, $alias, $field1, $operator, $field2)`
   * `order($field, $order)`
   * `group(...$fields)`
   * `build()` returns the [**Query**](https://github.com/jsanahuja/Sowe-Framework/blob/master/src/Query.php) object
   * `run()` returns the [**Query**](https://github.com/jsanahuja/Sowe-Framework/blob/master/src/Query.php) object after running the
Example
```php
$qb = new QueryBuilder("SELECT", $database);
$data = $qb->table("users")
   ->fields("id", "email", "role")
   ->condition("username", "=", "jdoe")
   ->run() //returns Query object
   ->fetchAll() // this is from Query object.
```

- [**Sowe\Framework\Mailer**](https://github.com/jsanahuja/Sowe-Framework/blob/master/src/Mailer.php) an interface to send mails using PHPMailer
   * `__construct($hostname, $username, $password, $sender, $sendername, $auth = true, $security = PHPMailer::ENCRYPTION_STARTTLS)`
   * `new()`
   * `to($address, $name = '')`
   * `cc($address)`
   * `bcc($address)`
   * `subject($subject)`
   * `body($body)`
   * `htmlbody($body)`
   * `altbody($body)`
   * `attachment($path, $name = '', $encoding = 'base64', $type = '', $disposition = 'attachment')`
   * `debug()` enables `SMTPDebug`
   * `send()`
Example
```php
$mailer = new Mailer($host, $user, $pass, $sender, $sendername);
$mailer->new()
  ->to("bannss1@gmail.com", "Javier")
  ->cc("admin@sowecms.com")
  ->subject("Test Sowe/Framework/Mailer")
  ->body("This is a test message sent with Sowe/Framework/Mailer")
  ->send();
```

- [**Sowe\Framework\Logger**](https://github.com/jsanahuja/Sowe-Framework/blob/master/src/Logger.php) extends Monolog\Logger to prepend file and line where the log was originated.

Example
```php
use Sowe\Framework\Logger;
use Monolog\ErrorHandler;
use Monolog\Handler\StreamHandler;
use Monolog\Formatter\LineFormatter;

$logpath = __DIR__ . "/logs/log.log";
$logger = new Logger("");
$formatter = new LineFormatter(
    "[%datetime%]:%level_name%: %message% %context%\n",
    "Y-m-d\TH:i:s",
    true, /* allow break lines */
    true /* ignore empty contexts */
);
$stream = new StreamHandler($logpath, Logger::DEBUG);
$stream->setFormatter($formatter);
$logger->pushHandler($stream);
$handler = new ErrorHandler($logger);
$handler->registerErrorHandler([], false);
$handler->registerExceptionHandler();
$handler->registerFatalHandler();
```
