## Phalcon Debugbar

Integrates [PHP Debug Bar](http://phpdebugbar.com/) with  [Phalcon FrameWork](http://phalconphp.com).

Thanks laravel-debugbar, I use some codes of it!


## Features

1. Ajax request support
2. Redirect support
3. persistent support
4. Simple App, Mulit Moudle App and Micro App support

### Support Collectors

- MessagesCollector : support scalar, array, object
- TimeDataCollector : custom time measure
- MemoryCollector : memory usage
- ExceptionsCollector : exception chain
- QueryCollector: SQL statement, spend time of every SQL, EXPLAIN result of SELECT statement
- RouteCollector: Route info
- ViewCollector:  All the rendered templates, spend time of every template, all the templates variables.
- PhalconRequestCollector: request headers, cookies, server variables, response headers, querys, post data,raw body
- ConfigCollector: data in the config service.
- SessionCollectior: session data
- SwiftMailCollector: mailer info

## Install package

### composer install

```
php composer.phar require --dev snowair/phalcon-debugbar
```


### manual install

Download and unpack to your project. Then register namespace in your code: 

```
$loader = new \Phalcon\Loader();
$loader->registerNamespaces(array(
'Snowair\Debugbar' => 'Path-To-PhalconDebugbar',  
));
$loader->register();
```

### settging

Make sure your project directory is writeable for php. Or your can create `Runtime` directory under your project directory and make it writeable. You can change `Runtime/phalcon` directory to other by edit debugbar config file.


1. Set your App Instance to DI:

    ```
    $di = new Phalcon\DI\FactoryDefault();
    $application = new Phalcon\Mvc\Application( $di ); // Important: mustn't ignore $di param . The Same Micro APP: new Phalcon\Mvc\Micro($di);
    $di['app'] = $application; //  Important
    ```

2. Before handle app, register and boot debugbar provider. 

    ```
    $provider = new Snowair\Debugbar\ServiceProvider();
    $provider->register();
    $provider->boot();
    echo $application->handle()->getContent();
    ```

3. Copy `config/debugbar.php` to your config directory, and change some setting your want. Than use it :

    ```
    $provider = new Snowair\Debugbar\ServiceProvider('your-debugbar-config-file-path');
    ```


4. For multi modules application, you may need attach db and view service instance to debugbar:

    ```
    $di->set('db',function(...));
    $di->set('view',function(...));

    if ( $di->has('debugbar') ) {
        $debugbar = $di['debugbar'];
        $debugbar->attachDb($di['db']);
        $debugbar->attachView($di['view']);
    }
```

5. Your can attach many db service to debugbar:

    ```
    $debugbar->attachDb($di['dbRead']);
    $debugbar->attachDb($di['dbWrite']);
    $debugbar->attachDb($di['anyOtherDb']);
    ```

6. You can enable/disable debugbar in runtime.

    ```
    $debugbar->enable();
    $debugbar->disable();
    ```

### Snapshots


* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/message.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/timeline.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/exception.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/route.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/database.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/views.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/config.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/session.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/request.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/stackdata.png)
