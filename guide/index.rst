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
- `Debugger Service`_
- `Database Service`_
- `Mailer Service`_
- `Migrator Service`_
- `Language Service`_
- `Locator Service`_
- `Logger Service`_
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

Debugger Service
^^^^^^^^^^^^^^^^

Gets an instance of
`Framework\Debug\Debugger <https://docs.aplus-framework.com/classes/Framework-Debug-Debugger.html>`_.

.. code-block:: php

    App::debugger()

Debugger Config Options
***********************

debugbar_view
"""""""""""""

Sets the path of a file to be used instead of the debugbar view. The default is
to use the original.

options
"""""""

Sets an array of options for the Debugger. The default is to set nothing.

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

Set an array of configurations. Usually just the ``username``, the ``password``
and the ``schema``.

The complete list of configurations can be seen
`here <https://docs.aplus-framework.com/guides/libraries/database/index.html#connection>`_.

logger_instance
"""""""""""""""

Set the `Logger Service`_ instance name. If not set, the Logger instance will
not be set in the Database class.

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

database_instance
"""""""""""""""""

Set the `Database Service`_ instance name. The default is ``default``.

directories
"""""""""""

Sets an array of directories that contain Migrations files. It must be set.

table
"""""

The name of the migrations table. The default name is ``Migrations``.

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

Request Service
^^^^^^^^^^^^^^^

Gets an instance of
`Framework\HTTP\Request <https://docs.aplus-framework.com/classes/Framework-HTTP-Request.html>`_.

.. code-block:: php

    App::request()

Request Config Options
**********************

server_vars
"""""""""""

An array of values to be set in the $_SERVER superglobal on the command line.

allowed_hosts
"""""""""""""

Sets an array of allowed hosts. The default is ``null`` so any host is allowed.

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

Sets an array where the keys are the name and the values are the header values.
The default is to set none.

auto_etag
"""""""""

``true`` arrow to enable ETag auto-negotiation on all responses. It can also be
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

save_handler
""""""""""""

Optional. Sets an array containing the ``class`` key with the Fully Qualified
Class Name of a child class of Framework\Session\SaveHandler. And also the
``config`` key with the configurations passed to the SaveHandler.

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

Entities
########

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

Conclusion
----------
