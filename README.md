## Phalcon Debugbar

Integrates [PHP Debug Bar](http://phpdebugbar.com/) with  [Phalcon FrameWork](http://phalconphp.com).

Thanks laravel-debugbar, I use some codes of it!


## Features

1. Ajax request support
2. Redirect support
3. persistent support
4. Simple App, Mulit module App and Micro App support

### Support Collectors

- `MessagesCollector` : Collect custom message, support scalar, array and object
- `TimeDataCollector` : Collect custom time measure.
- `ExceptionsCollector` : Add a exception object to debugbar.
- `MemoryCollector` : Collect memory usage
- `QueryCollector`: Capture each SQL statement, measure spent time of each SQL, show EXPLAIN result of each SELECT statement
- `RouteCollector`: Show Route info of currect request.
- `ViewCollector`:  Show all the rendered templates, measure spent time of each template, show all the templates variables.
- `PhalconRequestCollector`: Show request headers, cookies, server variables, response headers, querys, post data,raw body
- `ConfigCollector`: Show the data in the config service.
- `SessionCollectior`: Show session data
- `SwiftMailCollector`: mailer info
- `LogsCollectors`: Show logs of current request.
- `CacheCollectors`: Show caches summary(saved,gets,incs,decs,failds), and each cache operation detail.

## Quick start

### composer

* install

    ```
    php composer.phar require --dev snowair/phalcon-debugbar
    ```
* update

    ```
    php composer.phar update snowair/phalcon-debugbar
    ```

### pesistent directory

The default directory for store debugbar data is `Runtime/phalcon`. If it not exists, will try to create auto.


### modify index.php

1. Set your App Instance to DI:

    ```
    $application = new Phalcon\Mvc\Application( $di ); // Important: mustn't ignore $di param . The Same Micro APP: new Phalcon\Mvc\Micro($di);
    $di['app'] = $application; //  Important
    ```

2. Before handle app, register and boot debugbar provider. 

    ```
    (new Snowair\Debugbar\ServiceProvider())->start();
    // after start the debugbar, you can do noting but handle your app right now.
    echo $application->handle()->getContent();
    ```

## More

### send custome meessage to debugbar

```
\PhalconDebug::startMeasure('start-1','how long');
\PhalconDebug::addMeasurePoint('start');
\PhalconDebug::info('this is info');
\PhalconDebug::addMessageIfTrue('1 == "1"', 1=='1');
\PhalconDebug::addMessageIfTrue('will not show', 1=='0');
\PhalconDebug::addMessageIfFalse('1 != "0" ', 1=='0');
\PhalconDebug::addException(new \Exception('oh , error'));
\PhalconDebug::addMeasurePoint('stop');
\PhalconDebug::stopMeasure('start-1');
```

### use your config


Copy `config/debugbar.php` to your config directory, and change any settings your want. Than use your debugbar config file by:

```
(new Snowair\Debugbar\ServiceProvider('your-debugbar-config-file-path'))->start();
```

### Mutlti Modules

Usually, You needn't modify any other files, if you follow rules bellow:

1. All the sevices for cahce has a name with `cache`.
2. All the sevices for db has a name start with `db` or end with `db`.

If your service name is't match these rules, you need attach it to debugbar: 

```
// service.php
$di->set('read-db-test',function(...)); // db service
$di->set('redis',function(...)); // cache service
if ( $di->has('debugbar') ) {
    $debugbar = $di['debugbar'];
    $debugbar->attachDb('read-db-test');
    $debugbar->attachCache('redis');
}
```

### TroubleShooting

* For ajax/json request, the debug data only stored in the persistent directory as a json file. You can
 Load it to the debugbar form Openhandler(Open icon on the right).

* If the debugbar does not work, the most possible reason is one or more collectors tirgger a error in the runtime.
Your can modify the debugbar config file, close collector one by one, retry it until found the collector cause problem.

* For any problem, you can open a Issue on Github.

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

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/caches.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/config.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/session.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/request.png)

* * * 

![Screenshot](http://git.oschina.net/zhuyajie/phalcon-debugbar/raw/master/snapshots/stackdata.png)
