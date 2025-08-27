
# DTO
This package is designed to be a simple proxy for PDO that will help you retrieve database results in a type safe manner.
It is not a full abstraction of SQL, or a full ORM or anything like that.

## Why do this?
You can think of it as using PDO::FETCH_CLASS, but it can be configured, as you can add filters, rename field and have value converters.
There are also some helper methods for doing simple SELECTs by primary id, INSERT's and UPDATE's, but the goal of the package is *not* to help you write SQL.
Nor is the goal to reflect the database structure as models in PHP.
The primary goal is only to have properly structured types returned from your queries instead of dumb associative arrays.

## Usage
### Making a connection
As a basic proxy for PDO, you can instantiate the connection by either by either giving the connection class an existing PDO-instance:

```php
use Dschledermann\Dto\Connection;
$connection = Connection::createFromPdo($pdo);
```

Or ask the connection to create a PDO from credentials provided in the environment:

```php
use Dschledermann\Dto\Connection;
$connection = Connection::createFromEnv();
```

### Executing a basic query
If you just want to make a simple query, you can do so with the Connection::query() method.

For any result, you should define a PHP-class that holds the fields you get from the query.

Example:

```php
final class Person
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $name;
    public string $address;
    public int $age;
}
```

You can now do a query and ask the that the result should be mapped onto your class.

Example:

```php
$stmt = $connection->query("SELECT * FROM person", Person::class);
```

### Fetching values
The Statement::fetch() will now emit objects of the type Person for each row returned.
The fields from the resultset is mapped to the corresponding field on the class.
If a field from the resultset does not map onto a field in the class, it is just ommitted.
If you mark a field on the class has the #[Ignore] attribute, nothing from the resultset will be mapped onto it.

```php
$person = $stmt->fetch();
```

The $person variable will now contain a Person object and your IDE should also indicate that this is this is the case.

If you want to get all elements in one method, you can use Statement::fetchAll():

```php
$persons = $stmt->fetchAll();
```

Sometimes it's ergonomic if the elements are indexed by their unique field.
To do this you can use the Statement::fetchAllIndexed() method:

```php
$persons = $stmt->fetchAllIndexed();
```

The keys in the "$persons" array are now taken from the Person::$id field from each record.
This will only work if the target class has a unique field.

### Executing a prepare
You can also do a prepare for a query.

```php
$stmt = $connection->prepare(
    "SELECT * FROM person WHERE age = ?",
    Person::class,
);
$stmt->execute([49]);

$persons = $stmt->fetchAll();
```

### JOINs and field from multiple tables
There's no problem in defining a result class spanning fields from multiple tables or just a subset of fields from one table.
Consider this code:

```php
final class UserResult
{
    public string $username;
    public string $email;
    public string $countryName;
}

$stmt = $connection->prepare(
    "SELECT u.username, u.email, c.country_name
     FROM user AS u
     JOIN country AS c ON u.country_id = c.id
     WHERE u.age > ?",
    UserResult::class,
);

$stmt->execute([$age]);

$userResults = $stmt->fetchAll();
```

### Special cases
While this package does not attempt to build complex SQL for you, it can build certain trivial SQL.
This only works for DTO types that reflect a single table and have a defined unique key.

Consider this type:
```php
final class User
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $username;
    public string $displayName;
    public int $loginCount;
}
```

#### Getting a single element by primary key
A special case is getting a single record from a table by its primary key.

```php
$stmt = $connection->get(123, User::class);
$user = $stmt->fetch();
```

#### Persisting an element
Another case is persisting a record:

```php
$connection->persist($person);
```

This will do either an INSERT in the unique identifier is NULL or an UPDATE if the unique identifier is set.

### Naming
The package is written with the assumption that the PHP code is written in the PSR-12 standard,
and your SQL tables and fields are written in snake case.
The default behaviour is to convert this when translating fields and type names.
If you have this type:

```php
final class BlogPost
{
    #[UniqueIdentifier]
    public ?int $id;
    public string $headerText;
    public string $bodySection;
}
```

.. the default behaviour is translate to something like:

```sql
CREATE TABLE blog_post(
  id INT(11) unsigned NOT NULL AUTO_INCREMENT,
  header_text VARCHAR(255) NOT NULL,
  body_section TEXT NOT NULL
);
```

This conversion is done with the Dschledermann\Dto\Mapper\Key\ToSnakeCase attribute, which is the default.
There are also other options:

```php
final class BlogPost
{
    #[UniqueIdentifier]
    public ?int $id;
    #[ToLowerCase]
    public string $headerText;
    #[SetSqlName("maintext")]
    public string $bodySection;
}
```

.. where $headerText will be translated into "headertext" and $bodySection into "maintext".

If you need something else, feel free to implement the KeyMapperInterface in your own type and make it an attribute.
KeyMapperInterface attributes work both for class names and property names.

### How about single values?

Sometimes you just need a single value from the result set and not a complex type.
It can be a bit cumbersome to either specify a new type for just one value,
or revert to using PDO for this only.
To do this you can specify that you want a single primitive value.
Consider this code example:

```php
use Dschledermann\Dto\Primitive;

$stmt = $connection->prepare(
    "SELECT value1, value2 FROM some_table",
    Primitive::INTEGER,
);

$values = $stmt->fetchAll();
```

The $values variable now contains an array of integers from only the first element in each row.
Column names are not considered and all values are typecast to the requested type.

## FAQ

#### Q: Why not just use Doctrine?
__A__: Doctrine is aimed at supporting the entire model of your database and do so with each entity mapping to one corresponding table.
DTO makes no such assumption.
This is only to map your *results* to well defined types, nothing more.
Mapping your entire domain from database into a complete consistent model in a legacy project can be quite the undertaking.

#### Q: Can I do relations?
__A__: I suppose you could with the IntoPhpInterface, but I'd recommend against it.
There's no need for your result types to map exactly and exclusively onto single table.
You can do partial tables or do all relevant fields from a JOIN-expression.
In most cases, just JOIN the tables you need and design a DTO for that particular result.
Your queries will be lean and your code will have fewer couplings.

#### Q: Will there be a query builder?
__A__: No. I consider query builders harmful.
All the cases that can resonably be covered by simple query building is already implemented in the Connection::get() and Connection::persist() methods.
Anything more complex is bound to reduce readability.
My position is that SQL is the best way to communicate with an SQL-database.
If you need something complex, you are better served with writing the SQL directly and not using some OOP on top of SQL.

#### Q: How should I structure my result type code?
__A__: I would abstain from the temptation of building a centralized set of models or result types.
At least not in all cases.
Yes, some queries may have overlapping result types, but if you are using the same classes across many parts of your code, you are increasing coupling and reducing locality, making it harder to maintain.
Instead, in most cases, you are better served by grouping the DTO structures near the service or controller that uses them.
