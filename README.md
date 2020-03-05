# Micro framework kernel.

Requires PHP 7.2

## Usage

### Dependency injection container
Step 1: Declare dependencies.
```php
declare(strict_types=1);

use Satori\Micro\Kernel;

$app = new Kernel();

/**
 * Declares an action that has dependencies.
 */
$app->pageIndexAction = function (Kernel $app) {
    return new \Page\IndexAction(
        $app->pageIndexTemplate,
        $app->pageIndexService
    );
};

/**
 * Declares a template that depends on a configuration parameter.
 */
$app->pageIndexTemplate = function (Kernel $app) {
    return new \Page\IndexTemplate($app['path.template']);
};

/**
 * Declares an service.
 */
$app->pageIndexService = function (Kernel $app) {
    return new \Page\IndexService($app['fake.data']);
};

/**
 * Configuration parameters.
 */
$app['path.root'] = __DIR__ . '/../..';
$app['path.template'] = $app['path.root'] . '/template/site';
$app['fake.data'] = ['foo' => 'Foo', 'bar' => 'Bar'];
```

Step 2: Get action with resolved dependencies.
```php
$action = $app->pageIndexAction;
```

### Event dispatcher
Step 1: Subscribe a listener.
```php
declare(strict_types=1);

use Satori\Micro\Kernel;
use App\Logger;

$app = new Kernel();

/**
 * Declares a logger.
 */
$app->logger = function (Kernel $app) {
    return new \App\Logger();
};

/**
 * Subscribes a listener to an event.
 *
 * @param string   $event    The unique name of the event.
 * @param string   $listener The unique name of the listener.
 * @param callable $callback The closure or invokable object.
 */
$app->subscribe(
    'system_error', // $event
    'logger',       // $listener
    function (Kernel $app, array $arguments = null) {
        $logger = $app->logger;
        $logger->log('error', 'System error', $arguments);
    }
);

```

Step 2: Notify all listeners about an event.
```php
$app->notify('system_error', ['file' => '/app/functions.php', 'line' => 20]);
```

## License
MIT License
