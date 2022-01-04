MVC
===

.. image:: image.png
    :alt: Aplus Framework MVC Library

Aplus Framework MVC (Model-View-Controller) Library.

- `Installation`_
- `App`_
- `Models`_
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
                    ->setStatus(Response::CODE_BAD_REQUEST)
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
                ->setStatus(Response::CODE_CREATED)
                ->setHeader(
                    Response::HEADER_LOCATION,
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

- `Autoloader Service`_
- `Cache Service`_
- `Console Service`_
- `Anti-CSRF Service`_
- `Database Service`_
- `Mailer Service`_
- `Language Service`_
- `Locator Service`_
- `Logger Service`_
- `Router Service`_
- `Request Service`_
- `Response Service`_
- `Session Service`_
- `Validation Service`_
- `View Service`_

Autoloader Service
^^^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Autoload\Autoloader <https://docs.aplus-framework.com/classes/Framework-Autoload-Autoloader.html>`_.

.. code-block:: php

    App::autoloader()

Autoloader Config Options
*************************

register
""""""""

extensions
""""""""""

namespaces
""""""""""

classes
"""""""

Cache Service
^^^^^^^^^^^^^

Gets an instance of
`Framework\Cache\Cache <https://docs.aplus-framework.com/classes/Framework-Cache-Cache.html>`_.

.. code-block:: php

    App::cache()

Cache Config Options
********************

class
"""""

configs
"""""""

prefix
""""""

serializer
""""""""""

logger_instance
"""""""""""""""

A `Logger Service`_ instance name.

Console Service
^^^^^^^^^^^^^^^

Gets an instance of
`Framework\CLI\Console <https://docs.aplus-framework.com/classes/Framework-CLI-Console.html>`_.

.. code-block:: php

    App::console()

Console Config Options
**********************

directories
"""""""""""

find_in_namespaces
""""""""""""""""""

language_instance
"""""""""""""""""

A `Language Service`_ instance name.

locator_instance
""""""""""""""""

A `Locator Service`_ instance name.

Anti-CSRF Service
^^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\HTTP\AntiCSRF <https://docs.aplus-framework.com/classes/Framework-HTTP-AntiCSRF.html>`_.

.. code-block:: php

    App::antiCsrf()

Anti-CSRF Config Options
************************

enabled
"""""""

token_name
""""""""""

session_instance
""""""""""""""""

A `Session Service`_ instance name.

request_instance
""""""""""""""""

A `Request Service`_ instance name.

Database Service
^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Database\Database <https://docs.aplus-framework.com/classes/Framework-Database-Database.html>`_.

.. code-block:: php

    App::database()

Database Config Options
***********************

config
""""""

logger_instance
"""""""""""""""

A `Logger Service`_ instance name.

Mailer Service
^^^^^^^^^^^^^^

Gets an instance of
`Framework\Email\Mailer <https://docs.aplus-framework.com/classes/Framework-Email-Mailer.html>`_.

.. code-block:: php

    App::mailer()

Mailer Config Options
*********************

class
"""""

config
""""""

Language Service
^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Language\Language <https://docs.aplus-framework.com/classes/Framework-Language-Language.html>`_.

.. code-block:: php

    App::language()

Language Config Options
***********************

default
"""""""

supported
"""""""""

negotiate
"""""""""

request_instance
""""""""""""""""

A `Request Service`_ instance name.

fallback_level
""""""""""""""

directories
"""""""""""

find_in_namespaces
""""""""""""""""""

autoloader_instance
"""""""""""""""""""

A `Autoloader Service`_ instance name.

Locator Service
^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Autoload\Locator <https://docs.aplus-framework.com/classes/Framework-Autoload-Locator.html>`_.

.. code-block:: php

    App::locator()

Locator Config Options
**********************

autoloader_instance
"""""""""""""""""""

A `Autoloader Service`_ instance name.

Logger Service
^^^^^^^^^^^^^^

Gets an instance of
`Framework\Log\Logger <https://docs.aplus-framework.com/classes/Framework-Log-Logger.html>`_.

.. code-block:: php

    App::logger()

Logger Config Options
*********************

directory
"""""""""

level
"""""

Router Service
^^^^^^^^^^^^^^

Gets an instance of
`Framework\Routing\Router <https://docs.aplus-framework.com/classes/Framework-Routing-Router.html>`_.

.. code-block:: php

    App::router()

Router Config Options
*********************

files
"""""

placeholders
""""""""""""

auto_options
""""""""""""

auto_methods
""""""""""""

response_instance
"""""""""""""""""

The `Response Service`_ instance name.

language_instance
"""""""""""""""""

A `Language Service`_ instance name.

Request Service
^^^^^^^^^^^^^^^

Gets an instance of
`Framework\HTTP\Request <https://docs.aplus-framework.com/classes/Framework-HTTP-Request.html>`_.

.. code-block:: php

    App::request()

Request Config Options
**********************

allowed_hosts
"""""""""""""

Response Service
^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\HTTP\Response <https://docs.aplus-framework.com/classes/Framework-HTTP-Response.html>`_.

.. code-block:: php

    App::response()

Response Config Options
***********************

headers
"""""""

auto_etag
"""""""""

auto_language
"""""""""""""

cache
"""""

request_instance
""""""""""""""""

A `Request Service`_ instance name.

Session Service
^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Session\Session <https://docs.aplus-framework.com/classes/Framework-Session-Session.html>`_.

.. code-block:: php

    App::session()

Session Config Options
**********************

save_handler
""""""""""""

options
"""""""

auto_start
""""""""""

logger_instance
"""""""""""""""

A `Logger Service`_ instance name.

Validation Service
^^^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Validation\Validation <https://docs.aplus-framework.com/classes/Framework-Validation-Validation.html>`_.

.. code-block:: php

    App::validation()

Validation Config Options
*************************

validators
""""""""""

language_instance
"""""""""""""""""

A `Language Service`_ instance name.

View Service
^^^^^^^^^^^^

Gets an instance of
`Framework\MVC\View <https://docs.aplus-framework.com/classes/Framework-MVC-View.html>`_.

.. code-block:: php

    App::view()

View Config Options
*******************

base_dir
""""""""

extension
"""""""""

layout_prefix
"""""""""""""

include_prefix
""""""""""""""

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

Entities
########

Views
-----

Controllers
-----------

Conclusion
----------
