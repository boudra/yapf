YAPF
====

This is a PHP framework made for educational purposes and it's not recommended
to use in production.

Features
=======

- Services container
- Dependency Injection integrated with the services container
- Router, can call Clousures, Controller methods or Resource controllers for
  easy REST API.
- Query Builder, simple query builder for retrieving, updating and deleting
  data, supports multiple joins, and advanced where expressions.
- Simple image manipulation class

Example
=======

```PHP
use App\Application;
use App\Core\Router;

$app = new Application();

$app->run(function(Router $router) {

    $router->get('/app/{name}/{age:int}', function($name, $age) {

        return [
            'msg' => "Hello $name, you are $age years old!",
        ];

    });

});

// GET http://localhost/app/John/21
{
    "msg": "Hello John, you are 21 years old!"
}
```
