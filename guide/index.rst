MVC
===

.. image:: image.png
    :alt: Aplus Framework MVC Library

Aplus Framework MVC (Model-View-Controller) Library.

- `Installation`_
- `App`_
- `Services`_
- `Models`_
- `Entities`_
- `Views`_
- `Controllers`_
- `Conclusion`_

Installation
------------

The installation of this library can be done with Composer:

.. code-block::

    composer require aplus/mvc:dev-master

App
---

The App class is the MVC "kernel". It is through it that an application
receives and responds to CLI and HTTP requests. It contains the various `services`_
that can be called from anywhere, with multiple instances with various predefined
configurations.

Using it directly or `extending`_ it is optional. In it, services communicate
internally and are used in `Controllers`_ and `Models`_.

It is designed to integrate several Aplus Framework libraries and, in a simple
way, provide a powerful application.

Initialization:

.. code-block:: php

    use Framework\MVC\App;

    $app = new App();

The App class first param can receive a
`Config <https://docs.aplus-framework.com/guides/libraries/config/index.html>`_
instance, config options as array or a config directory path as string.

For example:

.. code-block:: php

    $app = new App(__DIR__ . '/configs');

After initialization, it is also possible set configurations.

Let's see an example using the **view** and **database** services.

We can use the ``App::config`` method:

.. code-block:: php

    App::config()->setMany([
        'view' => [
            'default' => [
                'views_dir' => __DIR__ . '/views',
            ],
        ],
        'database' => [
            'default' => [
                'username' => 'root',
                'schema' => 'app',
            ],
            'replica' => [
                'host' => '192.168.1.10',
                'username' => 'root',
                'password' => 'Secr3tt',
                'schema' => 'app',
            ],
        ],
    ]);

Then the service config instances will be available.

Prints the contents of the view file ``__DIR__ . '/views/home/index.php'``:

.. code-block:: php

    echo App::view()->render('home/index');

Fetch all rows in the database ``default`` instance and move to the ``replica``
instance:

.. code-block:: php

    $result = App::database()->select()->from('Users')->run();

    while($user = $result->fetch()) {
        App::database('replica')->replace()->into('Users')->set($user)->run();
    }

See config options at `Services`_.

Running
#######

App is designed to `run HTTP`_ and `run CLI`_ requests, sharing the same services.

Run HTTP
^^^^^^^^

App handles the internet Hypertext Transfer Protocol in a very easy-to-use way.

Let's see an example creating a little application:

We will need to autoload classes, so we will set default configs for the
`Autoloader Service`_.

This app will respond to two origins. One is the web front end. Another is the REST API.

The default `Router Service`_ will load one file for each origin.

This is the **public/index.php** file:

.. code-block:: php

    use Framework\MVC\App;

    (new App([
        'autoloader' => [
            'default' => [
                'namespaces' => [
                    'Api' => __DIR__ . '/../api',
                ],
            ],
        ],
        'router' => [
            'default' => [
                'files' => [
                    __DIR__ . '/../routes/front.php',
                    __DIR__ . '/../routes/api.php',
                ],
            ],
        ],
    ]))->runHttp();

And now, let's create the router files:

The **routes/front.php** file is for the front end. The origin will be
`https://domain.tld <https://domain.tld>`_. Change if you want:

.. code-block:: php

    use Framework\MVC\App;
    use Framework\Routing\RouteCollection;
    
    App::router()->serve('https://domain.tld', function (RouteCollection $routes) {
        $routes->get('/', fn () => '<h1>Homepage</h1>');
    });

The **routes/api.php** is for the REST API. The origin will be
`https://api.domain.tld <https://api.domain.tld>`_. Change if you need:

.. code-block:: php

    use Framework\MVC\App;
    use Framework\Routing\RouteCollection;
    
    App::router()->serve('https://api.domain.tld', function (RouteCollection $routes) {
        $routes->get('/', fn () => App::router()->getMatchedCollection());
        $routes->post('/users', 'Api\UsersController::create');
        $routes->get('/users/{int}', 'Api\UsersController::show/0', 'users.show');
    }, 'api');

This is the **api/UsersController.php** example:

.. code-block:: php

    namespace Api;

    use Framework\HTTP\Response;
    use Framework\HTTP\ResponseHeader;
    use Framework\HTTP\Status;
    use Framework\MVC\App;
    use Framework\MVC\Controller;

    class UsersController extends Controller
    {
        public function create() : Response
        {
            $data = $this->request->getPost();
            $errors = $this->validate($data, [
                'name' => 'minLength:5|maxLength:64',
                'email' => 'email',
            ]);
            if ($errors) {
                return $this->response
                    ->setStatus(Status::BAD_REQUEST)
                    ->setJson([
                        'errors' => $errors,
                    ]);
            }
            // TODO: Create the UsersModel to insert the new user
            // ...
            $user = [
                'id' => rand(1, 1000000),
                'name' => $data['name'],
                'email' => $data['email'],
            ];
            return $this->response
                ->setStatus(Status::CREATED)
                ->setHeader(
                    ResponseHeader::LOCATION,
                    App::router()->getNamedRoute('api.users.show')
                        ->getUrl(pathArgs: [$user['id']])
                )->setJson($user);
        }
    }

After that, the application will have the following files:

- public/index.php
- routes/front.php
- routes/api.php
- api/UsersController.php

Put you server to run and access the URLs https://domain.tld and
https://api.domain.tld.

You can make a POST request with curl to https://api.domain.tld/users:

.. code-block::

    curl -i -X POST https://api.domain.tld/users \
    -H "Content-Type: application/x-www-form-urlencoded" \
    -d "name=John&email=john@foomail.com"

That's it. The basic HTTP application structure is created and working.

You can improve it with `Models`_, `Views`_ and `Controllers`_.

Run CLI
^^^^^^^

App handles Command-Line Interface with the `Console Service`_.

Let's create the **console.php** file:

.. code-block:: php

    use Framework\MVC\App;

    $app = new App();
    $app::config()->set('console', [
        'directories' => [
            __DIR__ . '/commands',
        ]
    ]);
    $app->runCli();

Now, let's add a command in the **commands/Meet.php** file:

.. code-block:: php

    use Framework\CLI\CLI;
    use Framework\CLI\Command;

    class Meet extends Command
    {
        public function run() : void
        {
            $name = CLI::prompt('What is your name?', 'Tadandan');
            CLI::write("Nice to meet you, $name. I'm Aplus.");
        }
    }

Go to the terminal and run:

.. code-block::

    php console.php

The console will output **meet** as an available command.

To execute it, run:

.. code-block::

    php console.php meet

That's it.

Services
########

Services are static methods in the App class. With them it is possible to make
quick calls with predefined configurations for different object instances, with
automated dependency injection.

App services can be extended. See `Extending`_.

Built-in services:

- `Anti-CSRF Service`_
- `Autoloader Service`_
- `Cache Service`_
- `Console Service`_
- `Database Service`_
- `Debugger Service`_
- `Exception Handler Service`_
- `Language Service`_
- `Locator Service`_
- `Logger Service`_
- `Mailer Service`_
- `Migrator Service`_
- `Request Service`_
- `Response Service`_
- `Router Service`_
- `Session Service`_
- `Validation Service`_
- `View Service`_

Anti-CSRF Service
^^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\HTTP\AntiCSRF <https://docs.aplus-framework.com/classes/Framework-HTTP-AntiCSRF.html>`_.

.. code-block:: php

    App::antiCsrf()

Anti-CSRF Config Options
************************

.. code-block:: php

    [
        'antiCsrf' => [
            'default' => [
                'enabled' => true,
                'token_name' => 'csrf_token',
                'session_instance' => 'default',
                'request_instance' => 'default',
            ],
        ],
    ]

enabled
"""""""

Set ``true`` to enable and ``false`` to disable. By default it is enabled.

token_name
""""""""""

Sets the token name. The default is ``csrf_token``.

session_instance
""""""""""""""""

Set the `Session Service`_ instance name. The default is ``default``.

request_instance
""""""""""""""""

Set the `Request Service`_ instance name. The default is ``default``.

Autoloader Service
^^^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Autoload\Autoloader <https://docs.aplus-framework.com/classes/Framework-Autoload-Autoloader.html>`_.

.. code-block:: php

    App::autoloader()

Autoloader Config Options
*************************

.. code-block:: php

    [
        'autoloader' => [
            'default' => [
                'register' => true,
                'extensions' => '.php',
                'namespaces' => null,
                'classes' => null,
            ],
        ],
    ]

register
""""""""

Set ``true`` to register as an autoload function or ``false`` to disable. The
default is to leave it enabled.

extensions
""""""""""

A comma-separated list of extensions. The default is ``.php``.

namespaces
""""""""""

Sets the mapping from namespaces to directories. By default it is not set.

classes
"""""""

Sets the mapping of classes to files. By default it is not set.

Cache Service
^^^^^^^^^^^^^

Gets an instance of
`Framework\Cache\Cache <https://docs.aplus-framework.com/classes/Framework-Cache-Cache.html>`_.

.. code-block:: php

    App::cache()

Cache Config Options
********************

.. code-block:: php

    [
        'cache' => [
            'default' => [
                'class' => ???, // Must be set
                'configs' => [],
                'prefix' => null,
                'serializer' => Framework\Cache\Serializer::PHP,
                'logger_instance' => 'default',
            ],
        ],
    ]

class
"""""

The Fully Qualified Class Name of a class that extends ``Framework\Cache\Cache``.

There is no default value, it must be set.

configs
"""""""

The configurations passed to the class. By default it is an empty array.

prefix
""""""

A prefix for the name of cache items. By default nothing is set.

serializer
""""""""""

Sets the serializer with a value from the enum Framework\Cache\Serializer,
which can be a case of the enum or a string.

The default value is ``Framework\Cache\Serializer::PHP``.

logger_instance
"""""""""""""""

Set the `Logger Service`_ instance name. If not set, the Logger instance will
not be set in the Cache class.

Console Service
^^^^^^^^^^^^^^^

Gets an instance of
`Framework\CLI\Console <https://docs.aplus-framework.com/classes/Framework-CLI-Console.html>`_.

.. code-block:: php

    App::console()

Console Config Options
**********************

.. code-block:: php

    [
        'console' => [
            'default' => [
                'directories' => null,
                'find_in_namespaces' => false,
                'language_instance' => 'default',
                'locator_instance' => 'default',
            ],
        ],
    ]

directories
"""""""""""

Sets an array of directories where commands will be looked for. By default there
is no directory.

find_in_namespaces
""""""""""""""""""

Set ``true`` to find commands in all Commands subdirectories of all namespaces.
The default is not to find in namespaces.

language_instance
"""""""""""""""""

Set a `Language Service`_ instance name. If not set, the Language instance will
not be set in the Console class.

locator_instance
""""""""""""""""

Set the `Locator Service`_ instance name. By default it is ``default``.

Database Service
^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Database\Database <https://docs.aplus-framework.com/classes/Framework-Database-Database.html>`_.

.. code-block:: php

    App::database()

Database Config Options
***********************

.. code-block:: php

    [
        'database' => [
            'default' => [
                'config' => ???, // Must be set
                'logger_instance' => 'default',
            ],
        ],
    ]

config
""""""

Set an array of configurations. Usually just the ``username``, the ``password``
and the ``schema``.

The complete list of configurations can be seen
`here <https://docs.aplus-framework.com/guides/libraries/database/index.html#connection>`_.

logger_instance
"""""""""""""""

Set the `Logger Service`_ instance name. If not set, the Logger instance will
not be set in the Database class.

Debugger Service
^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Debug\Debugger <https://docs.aplus-framework.com/classes/Framework-Debug-Debugger.html>`_.

.. code-block:: php

    App::debugger()

Debugger Config Options
***********************

.. code-block:: php

    [
        'debugger' => [
            'default' => [
                'debugbar_view' => null,
                'options' => null,
            ],
        ],
    ]

debugbar_view
"""""""""""""

Sets the path of a file to be used instead of the debugbar view. The default is
to use the original.

options
"""""""

Sets an array of options for the Debugger. The default is to set nothing.

Exception Handler Service
^^^^^^^^^^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Debug\ExceptionHandler <https://docs.aplus-framework.com/classes/Framework-Debug-ExceptionHandler.html>`_.

.. code-block:: php

    App::exceptionHandler()

Exception Handler Config Options
********************************

.. code-block:: php

    [
        'exceptionHandler' => [
            'default' => [
                'environment' => Framework\Debug\ExceptionHandler::PRODUCTION,
                'logger_instance' => 'default',
                'language_instance' => 'default',
                'development_view' => null,
                'production_view' => null,
                'initialize' => true,
                'handle_errors' => true,
            ],
        ],
    ]

environment
"""""""""""

Set the environment, default is **production**. Use the ``ExceptionHandler::DEVELOPMENT``
or ``ExceptionHandler::PRODUCTION`` constants.

logger_instance
"""""""""""""""

Set the `Logger Service`_ instance name. If not set, the Logger instance will
not be set in the ExceptionHandler class.

language_instance
"""""""""""""""""

Set a `Language Service`_ instance name. If not set, the Language instance will
not be passed.

development_view
""""""""""""""""

Set the file path to a view when in the development environment.

production_view
"""""""""""""""

Set the file path to a view when in the production environment.

initialize
""""""""""

Set if it is to initialize by setting the class as exception handler. The
default value is ``true``. 

handle_errors
"""""""""""""

If initialize is ``true``, this option defines whether to set the class as an
error handler. The default value is ``true``.

Language Service
^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Language\Language <https://docs.aplus-framework.com/classes/Framework-Language-Language.html>`_.

.. code-block:: php

    App::language()

Language Config Options
***********************

.. code-block:: php

    [
        'language' => [
            'default' => [
                'default' => 'en',
                'supported' => null,
                'negotiate' => false,
                'request_instance' => 'default',
                'fallback_level' => Framework\Language\FallbackLevel::none,
                'directories' => [],
                'find_in_namespaces' => false,
                'autoloader_instance' => 'default',
            ],
        ],
    ]

default
"""""""

Sets the default language code. The default is ``en``.

supported
"""""""""

Set an array with supported languages. The default is to set none.

negotiate
"""""""""

Set ``true`` to negotiate the locale on the command line or HTTP request.

request_instance
""""""""""""""""

Set the `Request Service`_ instance name to negotiate the current locale. The
default is ``default``.

fallback_level
""""""""""""""

Sets the Fallback Level to a Framework\Language\FallbackLevel enum case or an
integer. The default is to set none.

directories
"""""""""""

Sets directories that contain subdirectories with the locale and language files.
The default is to set none.

find_in_namespaces
""""""""""""""""""

If set to ``true`` it will cause subdirectories called Language to be searched
in all namespaces and added to Language directories.

autoloader_instance
"""""""""""""""""""

Sets the `Autoloader Service`_ instance name of the autoloader used to find in
namespaces.

Locator Service
^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Autoload\Locator <https://docs.aplus-framework.com/classes/Framework-Autoload-Locator.html>`_.

.. code-block:: php

    App::locator()

Locator Config Options
**********************

.. code-block:: php

    [
        'locator' => [
            'default' => [
                'autoloader_instance' => 'default',
            ],
        ],
    ]

autoloader_instance
"""""""""""""""""""

The `Autoloader Service`_ instance name. The default is ``default``.

Logger Service
^^^^^^^^^^^^^^

Gets an instance of
`Framework\Log\Logger <https://docs.aplus-framework.com/classes/Framework-Log-Logger.html>`_.

.. code-block:: php

    App::logger()

Logger Config Options
*********************

.. code-block:: php

    [
        'logger' => [
            'default' => [
                'class' => Framework\Log\Logger\MultiFileLogger::class,
                'destination' => ???, // Must be set
                'level' => Framework\Log\LogLevel::DEBUG,
                'config' => [],
            ],
        ],
    ]

class
"""""

A Fully Qualified Class Name of a child class of Framework\Log\Logger.

The default is ``Framework\Log\Logger\MultiFileLogger``.

destination
"""""""""""

Set the destination where the logs will be stored or sent. It must be set
according to the class used.

level
"""""

Sets the level of logs with a case of Framework\Log\LogLevel or an integer. If
none is set, the ``DEBUG`` level will be used.

config
""""""

Sets an array with extra configurations for the class. The default is to pass an
empty array.

Mailer Service
^^^^^^^^^^^^^^

Gets an instance of
`Framework\Email\Mailer <https://docs.aplus-framework.com/classes/Framework-Email-Mailer.html>`_.

.. code-block:: php

    App::mailer()

Mailer Config Options
*********************

.. code-block:: php

    [
        'mailer' => [
            'default' => [
                'class' => Framework\Email\Mailers\SMTPMailer::class,
                'config' => ???, // Must be set
            ],
        ],
    ]

class
"""""

Sets the Fully Qualified Class Name of a child class of Framework\Email\Mailer.

The default is ``Framework\Email\Mailers\SMTPMailer``.

config
""""""

Set an array with Mailer settings. Normally you just set the ``username``, the
``password``, the ``host`` and the ``port``.

The complete list of configurations can be seen
`here <https://docs.aplus-framework.com/guides/libraries/email/index.html#mailer-connection>`_.

Migrator Service
^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Database\Extra\Migrator <https://docs.aplus-framework.com/classes/Framework-Database-Extra-Migrator.html>`_.

.. code-block:: php

    App::migrator()

Migrator Config Options
***********************

.. code-block:: php

    [
        'migrator' => [
            'default' => [
                'database_instance' => 'default',
                'directories' => ???, // Must be set
                'table' => 'Migrations',
            ],
        ],
    ]

database_instance
"""""""""""""""""

Set the `Database Service`_ instance name. The default is ``default``.

directories
"""""""""""

Sets an array of directories that contain Migrations files. It must be set.

table
"""""

The name of the migrations table. The default name is ``Migrations``.

Request Service
^^^^^^^^^^^^^^^

Gets an instance of
`Framework\HTTP\Request <https://docs.aplus-framework.com/classes/Framework-HTTP-Request.html>`_.

.. code-block:: php

    App::request()

Request Config Options
**********************

.. code-block:: php

    [
        'request' => [
            'default' => [
                'server_vars' => [],
                'allowed_hosts' => [],
                'force_https' => false,
            ],
        ],
    ]

server_vars
"""""""""""

An array of values to be set in the $_SERVER superglobal on the command line.

allowed_hosts
"""""""""""""

Sets an array of allowed hosts. The default is an empty array, so any host is allowed.

force_https
"""""""""""

Set ``true`` to automatically redirect to the HTTPS version of the current URL. 
By default it is not set.

Response Service
^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\HTTP\Response <https://docs.aplus-framework.com/classes/Framework-HTTP-Response.html>`_.

.. code-block:: php

    App::response()

Response Config Options
***********************

.. code-block:: php

    [
        'response' => [
            'default' => [
                'headers' => [],
                'auto_etag' => false,
                'auto_language' => false,
                'language_instance' => 'default',
                'cache' => null,
                'request_instance' => 'default',
            ],
        ],
    ]

headers
"""""""

Sets an array where the keys are the name and the values are the header values.
The default is to set none.

auto_etag
"""""""""

``true`` allow to enable ETag auto-negotiation on all responses. It can also be
an array with the keys ``active`` and ``hash_algo``.

auto_language
"""""""""""""

Set ``true`` to set the Content-Language header to the current locale. The
default is no set.

language_instance
"""""""""""""""""

Set `Language Service`_ instance name of the Language used in **auto_language**.

cache
"""""

Set ``false`` to set Cache-Control to ``no-cache`` or an array with key ``seconds``
to set cache seconds and optionally ``public`` to true or false to ``private``.

The default is not to set these settings.

request_instance
""""""""""""""""

Set the `Request Service`_ instance name. The default is ``default``.

Router Service
^^^^^^^^^^^^^^

Gets an instance of
`Framework\Routing\Router <https://docs.aplus-framework.com/classes/Framework-Routing-Router.html>`_.

.. code-block:: php

    App::router()

Router Config Options
*********************

.. code-block:: php

    [
        'router' => [
            'default' => [
                'files' => [],
                'placeholders' => [],
                'auto_options' => null,
                'auto_methods' => null,
                'response_instance' => 'default',
                'language_instance' => 'default',
            ],
        ],
    ]

files
"""""

Sets an array with the path of files that will be inserted to serve the routes.
The default is to set none.

placeholders
""""""""""""

A custom placeholder array. Where the key is the placeholder and the value is
the pattern. The default is to set none.

auto_options
""""""""""""

If set to ``true`` it enables the feature to automatically respond to OPTIONS
requests. The default is no set.

auto_methods
""""""""""""

If set to ``true`` it enables the feature to respond with the status 405 Method
Not Allowed and the Allow header containing valid methods. The default is no set.

response_instance
"""""""""""""""""

Set the `Response Service`_ instance name. The default value is ``default``.

language_instance
"""""""""""""""""

Set a `Language Service`_ instance name. If not set, the Language instance will
not be passed.

Session Service
^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Session\Session <https://docs.aplus-framework.com/classes/Framework-Session-Session.html>`_.

.. code-block:: php

    App::session()

Session Config Options
**********************

.. code-block:: php

    [
        'session' => [
            'default' => [
                'save_handler' => [
                    'class' => null,
                    'config' => [],
                ],
                'options' => [],
                'auto_start' => null,
                'logger_instance' => 'default',
            ],
        ],
    ]

save_handler
""""""""""""

Optional. Sets an array containing the ``class`` key with the Fully Qualified
Class Name of a child class of Framework\Session\SaveHandler. And also the
``config`` key with the configurations passed to the SaveHandler.

If the ``class`` is an instance of Framework\Session\SaveHandlers\DatabaseHandler
it is possible to set the instance of a `Database Service`_ through the key 
``database_instance``.

options
"""""""

Sets an array with the options to be passed in the construction of the Session
class.

auto_start
""""""""""

Set to ``true`` to automatically start the session when the service is called.
The default is not to start.

logger_instance
"""""""""""""""

Set the `Logger Service`_ instance name. If not set, the Logger instance will
not be set in the save handler.

Validation Service
^^^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Validation\Validation <https://docs.aplus-framework.com/classes/Framework-Validation-Validation.html>`_.

.. code-block:: php

    App::validation()

Validation Config Options
*************************

.. code-block:: php

    [
        'validation' => [
            'default' => [
                'validators' => [
                    Framework\MVC\Validator::class,
                    Framework\Validation\FilesValidator::class,
                ],
                'language_instance' => 'default',
            ],
        ],
    ]

validators
""""""""""

Sets an array of Validators. The default is an array with the Validator from the
mvc package and the FilesValidator from the Validation package.

language_instance
"""""""""""""""""

Set the `Language Service`_ instance name. The default is not to set an
instance of Language.

View Service
^^^^^^^^^^^^

Gets an instance of
`Framework\MVC\View <https://docs.aplus-framework.com/classes/Framework-MVC-View.html>`_.

.. code-block:: php

    App::view()

View Config Options
*******************

.. code-block:: php

    [
        'view' => [
            'default' => [
                'base_dir' => ???, // Must be set
                'extension' => '.php',
                'layout_prefix' => null,
                'include_prefix' => null,
            ],
        ],
    ]

base_dir
""""""""

Sets the base directory from which views will be loaded. The default is to not
set any directories.

extension
"""""""""

Sets the extension of view files. The default is ``.php``.

layout_prefix
"""""""""""""

Sets the layout prefix. The default is to set none.

include_prefix
""""""""""""""

Set the includes prefix. The default is to set none.

Extending
#########

Built-in services are designed to be extended.

Let's look at an example extending the HTTP Request class and adding two custom
methods, ``isGet`` and ``isPost``:

.. code-block:: php

    namespace Lupalupa\HTTP;

    class Request extends \Framework\HTTP\Request
    {
        public function isGet() : bool
        {
            return $this->hasMethod('get');
        }

        public function isPost() : bool
        {
            return $this->hasMethod('post');
        }
    }

Now, let's extend the App class:

The ``App::request`` method return type can be replaced by a child class thanks
to `Covariance <https://www.php.net/manual/en/language.oop5.variance.php#language.oop5.variance.covariance>`_.

The example below adds a new method, ``App::other``, which also uses
`Late Static Bindings <https://www.php.net/manual/en/language.oop5.late-static-bindings.php#language.oop5.late-static-bindings.usage>`_.

.. code-block:: php

    use Lupalupa\HTTP\Request;
    use Lupalupa\Other;

    class App extends \Framework\MVC\App
    {
        public static function request(string $instance = 'default') : Request
        {
            $service = static::getService('request', $instance);
            if ($service) {
                return $service;
            }
            $config = static::config()->get('request', $instance);
            return static::setService(
                'request',
                new Request($config['allowed_hosts'] ?? null),
                $instance
            );
        }

        public static function other(string $instance = 'default') : Other
        {
            $service = static::getService('other', $instance);
            if ($service) {
                return $service;
            }
            $config = static::config()->get('other', $instance);
            $service = new Other(
                static::request($config['request_instance'] ?? 'default')
            );
            if (isset($config['foo'])) {
                $service->setFoo($config['foo']);
            }
            return static::setService('other', $service, $instance);
        }
    }

Finally, you will be able to use the custom instance of ``Request`` and ``Other``
anywhere in your application:

.. code-block:: php

    App::request()->isGet();
    App::request()->isPost();

    App::other()->doSomething();
    App::other('other_instance')->doSomething();

Tip: Use a smart IDE. Aplus loves it. Be happy.

Models
------

Models represent tables in databases. They have basic CRUD (create, read, update
and delete) methods, with validation, localization and performance
optimization with caching and separation of read and write data.

To create a model that represents a table, just create a class that extends the
**Framework\MVC\Model** class.

.. code-block:: php

    use Framework\MVC\Model;

    class Users extends Model
    {
    }

The example above is very simple, but with it it would be possible to read data
in the **Users** table.

Table Name
##########

The table name can be set in the ``$table`` property.

.. code-block:: php

    protected string $table;

If the name is not set the first time the ``getTable`` method is called, the
table name will automatically be set to the class name. For example, if the
class is called **App\Models\PostsModel**, the table name will be **Posts**.

Database Connections
####################

Each model allows two database connections, one for reading and one for writing.

The connections are obtained through the ``Framework\MVC\App::database`` method
and the name of the instances must be defined in the model.

To set the name of the read connection instance, use the ``$connectionRead``
property.

.. code-block:: php

    protected string $connectionRead = 'default';

The name of the connection can be obtained through the ``getConnectionRead``
method and the instance of **Framework\Database\Database** can be obtained
through the ``getDatabaseToRead`` method.

The name of the write connection, by default, is also ``default``. But it can
be modified through the ``$connectionWrite`` property.

.. code-block:: php

    protected string $connectionWrite = 'default';

The name of the write connection can be taken by the ``getConnectionWrite``
method and the instance by ``getDatabaseToWrite``.

Primary Key
###########

To work with a model, it is necessary that its database table has an
auto-incrementing Primary Key, because it is through it that data is found by
the ``find`` method, and rows are also updated and deleted.

By default, the name of the primary key is ``id``, as in the example below:

.. code-block:: php

    protected string $primaryKey = 'id';

It can be obtained with the ``getPrimaryKey`` method.

The primary key field is protected by default, preventing it from being changed
in write methods such as ``create``, ``update`` and ``replace``:

.. code-block:: php

    protected bool $protectPrimaryKey = true;

You can check if the primary key is being protected by the
``isProtectPrimaryKey`` method.

If it is protected, but allowed in `Allowed Fields`_, an exception will be
thrown saying that the primary key field is protected and cannot be changed by
writing methods.

Allowed Fields
##############

To manipulate a table making changes it is required that the fields allowed for
writing are set, otherwise a **LogicException** will be thrown.

Using the ``$allowedFields`` property, you can set an array with the names of
the allowed fields:

.. code-block:: php

    protected array $allowedFields = [
        'name',
        'email',
    ];

This list can be obtained with the ``getAllowedFields`` method.

Note that the data is filtered on write operations, removing all disallowed
fields.

Return Type
###########

When reading data, by default the rows are converted into ``stdClass`` objects,
making it easier to work with object orientation.

But, the ``$returnType`` property can also be set as ``array`` (making the
returned rows an associative array) or as a class-string of a child class of
**Framework\MVC\Entity**.

.. code-block:: php

    protected string $returnType = stdClass::class;

The return type can be obtained with the ``getReturnType`` method.

Results are automatically converted to the return type in the ``find``,
``findAll`` and ``paginate`` methods.

Automatic Timestamps
####################

With models it is possible to save the creation and update dates of rows
automatically.

To do this, just set the ``$autoTimestamps`` property to true:

.. code-block:: php

    protected bool $autoTimestamps = false;

To find out if automatic timestamps are enabled, you can use the
``isAutoTimestamps`` method.

By default, the name of the field with the row creation timestamp is
``createdAt`` and the field with the update timestamp is called ``updatedAt``.

Both fields can be changed via the ``$fieldCreated`` and ``$fieldUpdated``
properties:

.. code-block:: php

    protected string $fieldCreated = 'createdAt';
    protected string $fieldUpdated = 'updatedAt';

To get the name of the automatic timestamp fields you can use the
``getFieldCreated`` and ``getFieldUpdated`` methods.

The timestamp format can also be customized. The default is like the example
below:

.. code-block:: php

    protected string $timestampFormat = 'Y-m-d H:i:s';

And, the format can be obtained through the ``getTimestampFormat`` method.

The timestamps are generated using the timezone of the write connection and,
if it is not set, it uses UTC.

A timestamp formatted in ``$timestampFormat`` can be obtained with the
``getTimestamp`` method.

When the fields of ``$fieldCreated`` or ``$fieldUpdated`` are set to
``$allowedFields`` they will not be removed by filtering, and you can set
custom values.

Validation
##########

When one of the ``create``, ``update`` or ``replace`` methods is called for the
first time, the ``$validation`` property will receive an instance of
**Framework\Validation\Validation** for exclusive use in the model, which can be
obtained by the ``getValidation`` method.

To make changes it is required that the validation rules are set, otherwise a
**RuntimeException** will be thrown saying that the rules were not set.

You can set the rules in the ``$validationRules`` property:

.. code-block:: php

    protected array $validationRules = [
        'name' => 'minLength:5|maxLength:32',
        'email' => 'email',
    ];

Or returning in the ``getValidationRules`` method.

Validators can also be customized. By default, the ones used are
**Framework\MVC\Validator** and **Framework\Validation\FilesValidator**:

.. code-block:: php

    protected array $validationValidators = [
        Validator::class,
        FilesValidator::class,
    ];

The list of validators can be obtained using the ``getValidationValidators``
method.

The labels with the name of the fields in the error messages can also be
customized, being set in the ``$validationLabels`` property:

.. code-block:: php

    protected array $validationLabels;

Or through the ``getValidationLabels`` method, as in the example below, setting
the labels in the current language:

.. code-block:: php

    protected function getValidationLabels() : array
    {
        return $this->validationLabels ??= [
            'name' => $this->getLanguage()->render('users', 'name'),
            'email' => $this->getLanguage()->render('users', 'email'),
        ];
    }

The same goes for setting custom error messages. They can be set in the
``$validationMessages`` property:

.. code-block:: php

    protected array $validationMessages;

And obtained by the ``getValidationMessages`` method.

When ``create``, ``update`` or ``replace`` return ``false``, errors can be
retrieved via the ``getErrors`` method.

Pagination
##########

Using the ``paginate`` method, you can perform a basic pagination with all the
data in a table.

Below, we take the first 30 items from the table:

.. code-block:: php

    $page = 1;
    $perPage = 30;
    $data = $model->paginate($page, $perPage); // array

After calling the ``paginate`` method, the ``$pager`` property will have an
instance of the **Framework\Pagination\Pager** class, which can be obtained by
the ``getPager`` method.

So you can render the pagination wherever you need it, like in the HTTP Link
header or as HTML in views:

.. code-block:: php

    echo $model->getPager()->render('bootstrap');

In the ``$pagerView`` property you can define the default Pager view:

.. code-block:: php

    protected string $pagerView = 'bootstrap';

So this view will always render by default:

.. code-block:: php

    echo $model->getPager()->render();

Also, it is possible to set the Pager URL in the ``$pagerUrl`` property, which
is unnecessary in HTTP requests, but required in the command line.

Cache
#####

The model has a cache system that works with individual results. For example,
once the ``$cacheActive`` property is set to ``true``, when obtaining a row
via the ``find`` method, the result will be stored in the cache and will be
available directly from it for the duration of the Time To Live, defined in the
``$cacheTtl`` property.

When an item is updated via the ``update`` method, the cached item will also be
updated.

When an item is deleted from the database, it is also deleted from the cache.

With the active caching system it reduces the load on the database server, as
the rows are obtained from files or directly from memory.

Below is the example with the cache inactive. To activate it, just set the value
to ``true``.

.. code-block:: php

    protected bool $cacheActive = false;

Whenever you want to know if the cache is active, you can use the
``isCacheActive`` method.

And the name of the service instance obtained through the method
``Framework\MVC\App::cache`` can be set as in the property below:

.. code-block:: php

    protected string $cacheInstance = 'default';

Whenever it is necessary to get the name of the cache instance, you can use the
``getCacheInstance`` method and to get the object instance of the
**Framework\Cache\Cache** class, you can use the ``getCache`` method.

The default Time To Live value for each item is 60 seconds, as shown below:

.. code-block:: php

    protected int $cacheTtl = 60;

This value can be obtained through the ``getCacheTtl`` method.

Language
########

Some features, such as validation, on labels and error messages, or pagination
need an instance of **Framework\Language\Language** to locate the displayed
texts.

The name of the instance defined in the ``$languageInstance`` property is
obtained through the service available in the ``Framework\MVC\App::language``
method, and can be obtained through the ``getLanguage`` method.

The default instance is ``default``, as shown below:

.. code-block:: php

    protected string $languageInstance = 'default';

CRUD
####

The model has methods to work with basic CRUD operations, which are:

- `Create`_
- `Read`_
- `Update`_
- `Delete`_

Create
******

The ``create`` method inserts a new row and returns the LAST_INSERT_ID() on
success or ``false`` if validation fails:

.. code-block:: php
    
    $data = [
        'name' => 'John Doe',
        'email' => 'johndoe@domain.tld',
    ];
    $id = $model->create($data); // Insert ID or false

If it returns ``false``, it is possible to get the errors through the
``getErrors`` method:

.. code-block:: php

    if ($id === false) {
        $errors = $model->getErrors();
    }

Read
****

The ``find`` method finds a row based on the Primary Key and returns the row
with the type set in the ``$returnType`` property or ``null`` if the row is not
found.

.. code-block:: php

    $id = 1;
    $row = $model->find($id);

It is also possible to find all rows, with limit and offset, by returning an
array with items in the ``$returnType``.

.. code-block:: php

    $limit = 10;
    $offset = 20;
    $rows = $model->findAll($limit, $offset);

Update
******

The ``update`` method updates based on the Primary Key and returns the number
of rows affected or ``false`` if validation fails.

.. code-block:: php

    $id = 1;
    $data = [
        'name' => 'Johnny Doe',
    ];
    $affectedRows = $model->update($id, $data);

Delete
******

The ``delete`` method deletes based on the Primary Key and returns the number
of affected rows:

.. code-block:: php

    $id = 1;
    $affectedRows = $model->delete($id);

Extra
*****

The Model has some extra methods for doing common operations:

Count
"""""

A basic function to count all rows in the table.

.. code-block:: php

    $count = $model->count();

Replace
"""""""

Replace based on Primary Key and return the number of affected rows or ``false``
if validation fails.

.. code-block:: php

    $id = 1;
    $data = [
        'name' => 'John Morgan',
        'email' => 'johndoe@domain.tld',
    ];
    $affectedRows = $model->replace($id, $data);

Save
""""

Save a row. Updates if the Primary Key field is present, otherwise inserts a
new row.

Returns the number of rows affected in updates as an integer. The
LAST_INSERT_ID(), in inserts. Or ``false`` if validation fails.

.. code-block:: php

    $data = [
        'id' => 1,
        'email' => 'john@domain.tld',
    ];
    $result = $model->save($data);

Entities
--------

Entities represent rows in a database table. They can be used as a `Return Type`_
in models.

Let's see the entity **User** below:

.. code-block:: php

    use Framework\Date\Date;
    use Framework\HTTP\URL;
    use Framework\MVC\Entity;

    class User extends Entity
    {
        protected int $id;
        protected string $name;
        protected string $email;
        protected string $passwordHash;
        protected URL $url;
        protected Date $createdAt;
        protected Date $updatedAt;
    }

And, it can be instantiated as follows:

.. code-block:: php

    $user = new User([
        'id' => 1,
        'name' => 'John Doe',
    ]);

Populate
########

The array keys will be set as the property name with their respective values in
the ``populate`` method.

If a setter method exists for the property, it will be called. For example, if
there is a ``setId`` method, it will be called to set the ``id`` property. If
the ``setId`` method does not exist, it will try to set the property, if it
exists, otherwise it will throw an exception saying that the property is not
defined. If set, it will attempt to set the value to the property's type,
casting type with the `Type Hints`_ methods.

Init
####

The init method is used to initialize settings, set custom properties, etc.
Called in the constructor just after the properties are populated.

.. code-block:: php

    protected URL $url;
    protected string $name;

    protected function init() : void
    {
        $this->name = $this->firstname . ' ' . $this->lastname;
        $this->url = new URL('https://domain.tld/users/' . $this->id);        
    }

Magic Isset and Unset
#####################

To check if a property is set:

.. code-block:: php

    $isSet = isset($user->id); // bool

To remove a property:

.. code-block:: php

    unset($user->id);

Magic Getters
#############

Properties can be called directly. But first, it is always checked if there is
a getter for it and if there is, it will be used:

.. code-block:: php

    $id = $user->id; // 1
    $id = $user->getId(); // 1

Magic Setters
#############

Properties can be set directly. But before that, it is always checked if there
is a setter for it and if there is, the value will be set through it:

.. code-block:: php

    $user->id = 3;
    $user->setId(3);

Type Hints
**********

It is common to need to convert types when setting property. For example,
setting a URL string to be converted as an object of the Framework\HTTP\URL
class.

Before a property is set, the Entity class checks the property's type and checks
the value's type. Then, try to convert the value to the property's type through
3 methods.

Each method must return the value in the converted type or null, indicating that
the conversion was not performed.

Type Hint Custom
""""""""""""""""

The ``typeHintCustom`` method must be overridden to make custom type changes.

Type Hint Native
""""""""""""""""

The ``typeHintNative`` method converts to native PHP types, which are:
``array``, ``bool``, ``float``, ``int``, ``string`` and ``stdClass``.

Type Hint Aplus
"""""""""""""""

The ``typeHintAplus`` method converts to Aplus Framework class types, which are:
``Framework\Date\Date`` and ``Framework\HTTP\URL``.

To Model
########

Through the ``toModel`` method, the object is transformed into an associative
array ready to be written to the database.

Conversion to array can be done directly, as below:

.. code-block:: php

    $data = $user->toModel(); // Associative array

Or passed directly to one of a model's methods.

Let's see how to create a row using the variable ``$user``, which is an entity:

.. code-block:: php

    $id = $model->create($user); // Insert ID or false

JSON Encoding
#############

When working with APIs, it may be necessary to convert an Entity to a JSON
object.

To set which properties will be JSON-encoded just list them in the property
``$_jsonVars``:

.. code-block:: php

    class User extends Entity
    {
        protected array $_jsonVars = [
            'id',
            'name',
            'url',
            'createdAt',
        ];
    }

Once this is done, the entity can be encoded. Let's see in the following
example:

.. code-block:: php

    echo json_encode($user, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

And then the JSON object:

.. code-block:: json

    {
        "id": 1,
        "name": "John Doe",
        "url": "https://domain.tld/users/1",
        "createdAt": "2022-06-10T18:36:52-03:00"
    }

Note that the ``url`` and ``createdAt`` property objects have been serialized.

Views
-----

To obtain a View instance, the class can be instantiated individually, as shown
in the example below:

.. code-block:: php

    use Framework\MVC\View;

    $baseDir = __DIR__ . '/views';
    $extension = '.php';
    $view = new View($baseDir, $extension);

Or getting an instance of the ``view`` service in the App class:

.. code-block:: php

    use Framework\MVC\App;

    $view = App::view();

With the View instantiated, we can render files.

The file below will be used on the home page and is located at
**views/home/index.php**:

.. code-block:: php

    <h1><?= $title ?></h1>
    <p><?= $description ?></p>

Returning to the main file, we pass the data to the file to be rendered:

.. code-block:: php

    $file = 'home/index';
    $data = [
        'title' => 'Welcome!',
        'description' => 'Welcome to Aplus MVC.',
    ];
    echo $view->render($file, $data);

And the output will be like this:

.. code-block:: html

    <h1>Welcome!</h1>
    <p>Welcome to Aplus MVC.</p>

Extending Layouts
#################

The View has a basic layout system that other view files can extend.

Let's see the layout file **views/_layouts/default.php** below:

.. code-block:: php

    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= $title ?></title>
    </head>
    <body>
    <?= $view->renderBlock('contents') ?>
    </body>
    </html>

Then, in the view that will be rendered by the ``render`` method, the file
``_layouts/default`` sets the content inside the ``contents`` block:

**views/home/index.php**

.. code-block:: php

    <?php
    $view->extends('_layouts/default');
    $view->block('contents');
    ?>
    <h1><?= $title ?></h1>
    <p><?= $description ?></p>
    <?php
    $view->endBlock();

If you want to extend views always from the same directory, you can set the
layout prefix:

.. code-block:: php

    $view->setLayoutPrefix('_layouts');

This will make it unnecessary to type the entire path. See the example below:

.. code-block:: diff

    - $view->extends('_layout/default');
    + $view->extends('default');

When working with only one file that extends a layout, it is possible to
set the default block name in the second argument of ``extends``.

Let's see how to extend the default layout and capture the content in the file
**views/home/index.php**:

.. code-block:: php

    <?php
    $view->extends('default', 'contents');
    ?>
    <h1><?= $title ?></h1>
    <p><?= $description ?></p>

So the rendered HTML file will look like this:

.. code-block:: html

    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>Welcome!</title>
    </head>
    <body>
    <h1>Welcome!</h1>
    <p>Welcome to Aplus MVC.</p>
    </body>
    </html>

View Includes
#############

It is often common to have parts of the layout that are repeated. Like for example,
a header, a footer, a sidebar.

These files are called **includes**.

Let's see an example of include with a navigation bar in the file
**views/_includes/navbar.php**:

.. code-block:: php

    <div class="navbar">
    <ul>
        <li<?= $active === 'home' ? ' class="active"' : ''?>>
            <a href="/">Home</a>
        </li>
        <li<?= $active === 'contact' ? ' class="active"' : ''?>>
            <a href="/contact">Contact</a>
        </li>
    </ul>
    </div>

This navbar will appear in several layouts, including the default one.

Let's see below how to make it appear in views that extend the layout
**views/_layouts/default.php**:

.. code-block:: php

    <body>
    <?= $view->include('_includes/navbar') ?>
    <h1><?= $title ?></h1>

As with layouts, you can set the includes path prefix:

.. code-block:: php

    $view->setIncludePrefix('_includes');

Once the includes path is set, it is no longer necessary to include it
in the include method call:

.. code-block:: diff

    - $view->include('_includes/navbar');
    + $view->include('navbar');

The call in the default layout will be like this:

.. code-block:: php

    <body>
    <?= $view->include('navbar') ?>
    <h1><?= $title ?></h1>

When necessary, you can pass an array of data to the include.

Let's see how to pass the variable ``active`` with the value ``home``:

.. code-block:: php

    <body>
    <?= $view->include('navbar', ['active' => 'home']) ?>
    <h1><?= $title ?></h1>

When rendered, the include will show the ``active`` class on the Home line in the
navbar:

.. code-block:: html

    <div class="navbar">
    <ul>
        <li class="active">
            <a href="/">Home</a>
        </li>
        <li>
            <a href="/contact">Contact</a>
        </li>
    </ul>
    </div>

View Blocks
###########

Below we will see how to create a block called ``contents`` and another
called ``scripts`` in the **views/home/index.php** file:

.. code-block:: php

    <?php
    $view->extends('default');
    
    $view->block('contents');
    ?>
    <h1><?= $title ?></h1>
    <p><?= $description ?></p>
    <?php
    $view->endBlock();

    $view->block('scripts');
    ?>
    <script>
        console.log('Hello!');
    </script>
    <?php
    $view->endBlock();

In the **views/_layouts/default.php** file we can render the two blocks:

.. code-block:: php

    <body>
    <?= $view->renderBlock('contents') ?>
    <?= $view->renderBlock('scripts') ?>
    </body>

And the output will be like this:

.. code-block:: html

    <body>
    <h1>Welcome!</h1>
    <p>Welcome to Aplus MVC.</p>
    <script>
        console.log('Hello!');
    </script>
    </body>

Controllers
-----------

The abstract class **Framework\MVC\Controller** extends the class
**Framework\Routing\RouteActions**, inheriting the characteristics necessary
for your methods to be used as route actions.

Below we see an example with the **Home** class and the ``index`` action method
returning a string that will be appended to the HTTP Response body:

.. code-block:: php

    use Framework\MVC\Controller;

    class Home extends Controller
    {
        public function index() : string
        {
            return 'Home page.'
        }
    }

Render Views
############

Instead of building all the page content inside the ``index`` method, you can
use the ``render`` method, with the name of the file that will be rendered,
building the HTML page as a view.

In this case, we render the ``home/index`` view:

.. code-block:: php

    use Framework\MVC\Controller;

    class Home extends Controller
    {
        public function index() : string
        {
            return $this->render('home/index');
        }
    }

Validate Data
#############

When necessary, you can validate data using the ``validate`` method.

In it, it is possible to put the data that will be validated, the rules, and,
optionally, the labels, the messages and the name of the validation service
instance, which by default is ``default``.

In the example below we highlight the ``create`` method, which can be called by
the HTTP POST method to create a contact message.

Note that the rules are set and then the POST data is validated, returning an
array with the errors and showing them on the screen in a list or an empty array,
showing that no validation errors occurred and the message that was created
successfully:

.. code-block:: php

    use Framework\MVC\Controller;

    class Contact extends Controller
    {
        public function index() : string
        {
            return $this->render('contact/index');
        }

        public function create() : void
        {
            $rules = [
                'name' => 'required|minLength:5|maxLength:32',
                'email' => 'required|email',
                'message' => 'required|minLength:10|maxLength:1000',
            ];
            $errors =  $this->validate($this->request->getPost(), $rules);
            if ($errors) {
                echo '<h2>Validation Errors</h2>';
                echo '<ul>';
                foreach($errors as $error) {
                    echo '<li>' . $error . '</li>';
                }
                echo '</ul>';
                return;
            }
            echo '<h2>Contact Successful Created</h2>';
        }
    }

HTTP Request and Response
#########################

The Controller has instances of the two HTTP messages, the Request and the
Response, accessible through properties that can be called directly.

Let's see below how to use Request to get the current URL as a string and put it
in the Response body:

.. code-block:: php

    use Framework\HTTP\Response;
    use Framework\MVC\Controller;

    class Home extends Controller
    {
        public function index() : Response
        {
            $url = (string) $this->request->getUrl();
            return $this->response->setBody(
                'Current URL is: ' . $url
            );
        }
    }

The example above is simple, but $request and $response are powerful, having
numerous useful methods for working on HTTP interactions.

Model Instance
##############

Often, a controller works with a specific model and through the $modelClass
property it is possible to set the Fully Qualified Class Name of a
``ModelInterface`` child class so that an instance of it is automatically loaded,
in all requests, in the construction of the controller.

Let's see below that $modelClass receives the name of the ``App\Models\UsersModel``
class and in the ``show`` method the direct call to the $model property is used,
which has the instance of ``App\Models\UsersModel``:

.. code-block:: php

    use App\Models\UsersModel;
    use Framework\MVC\Controller;

    class Users extends Controller
    {
        protected string $modelClass = UsersModel::class;

        public function show(int $id) : string
        {
            $user = $this->model->find($id);
            return $this->render('users/show', [
                'user' => $user,
            ]);
        }
    }

JSON Responses
##############

As with the Framework\Routing\RouteActions class, the controller action methods
can return an array, stdClass, or JsonSerializable instance so that the Response
is automatically set with the JSON Content-Type and the message body as well.

In the example below, we see how to get the users of a page, with an array
returned from the model's ``paginate`` method, and then returned to be
JSON-encoded and added to the Response body:

.. code-block:: php

    use App\Models\UsersModel;
    use Framework\MVC\Controller;

    class Users extends Controller
    {
        protected string $modelClass = UsersModel::class;

        public function index() : array
        {
            $page = $this->request->getGet('page')
            $users = $this->model->paginate($page);
            return $users;
        }
    }

Before and After Actions
########################

Every controller has two methods inherited from Framework\Routing\RouteActions
that can be used to prepare configurations, filter input data, and also to
finalize configurations and filter output data.

They are ``beforeAction`` and ``afterAction``.

Let's look at a simple example to validate a user's access to a dashboard's pages.

We create the **AdminController** class and put a check in it to see if the
``user_id`` is set in the session. If not, the page will be redirected to the
location ``/login``. Otherwise, access to the action method is released and
the user can access the admin area:

.. code-block:: php

    use Framework\MVC\App;
    use Framework\MVC\Controller;

    abstract class AdminController extends Controller
    {
        protected function beforeAction(string $method, array $arguments) : mixed
        {
            if ( ! App::session()->has('user_id')) {
                return $this->response->redirect('/login');
            }
            return null;
        }
    }

Below, the Dashboard methods will only be executed if ``beforeAction`` returns
``null`` in the parent class, AdminController:

.. code-block:: php

    final class Dashboard extends AdminController
    {
        public function index() : string
        {
            return 'You are in Admin Area!';
        }
    }

Conclusion
----------

Aplus MVC Library is an easy-to-use tool for, beginners and experienced, PHP developers. 
It is perfect to create simple, fast and powerful MVC applications. 
The more you use it, the more you will learn.

.. note::
    Did you find something wrong? 
    Be sure to let us know about it with an
    `issue <https://gitlab.com/aplus-framework/libraries/mvc/issues>`_. 
    Thank you!

