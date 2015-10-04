## Phalcon Debugbar

Integrates [PHP Debug Bar](http://phpdebugbar.com/) with  [Phalcon FrameWork](http://phalconphp.com).

Thanks laravel-debugbar, I use some codes of it!

[Demo Online](http://invo.coding.io/)

[中文文档](https://github.com/snowair/phalcon-debugbar/blob/master/%E8%AF%B4%E6%98%8E%E6%96%87%E4%BB%B6.md)

## Features

1. Normal request capturing
2. Ajax request capturing
3. Redirect request chain capturing
4. Data collected persistent : save to Local File or MongoDB
5. Simple App, Mulit module App and Micro App support

### Support Collectors

- **MessagesCollector** : Collect custom message, support scalar, array and object
- **TimeDataCollector** : Collect custom time measure.
- **ExceptionsCollector** : Add a exception object to debugbar.
- **MemoryCollector** : Collect memory usage
- **QueryCollector**: Capture each SQL statement, measure spent time of each SQL, show EXPLAIN result of each SELECT statement
    * collect infomations from the `db` service. Only for Phalcon ORM.
- **DoctrineCollector**: Capture each SQL statement in Dortrine, measure spent time of each SQL.
    * collect infomations from the `entityManager` service. Only for Doctrine ORM.
- **RouteCollector**: Show Route info of currect request.
    * collect infomations from the `router` service.
- **ViewCollector**:  Show all the rendered templates, measure spent time of each template, show all the templates variables.
    * collect infomations from the `view` service.
- **PhalconRequestCollector**: Show request headers, cookies, server variables, response headers, querys, post data,raw body
    * collect infomations from the `request` service.
- **ConfigCollector**: Show the data in the config service.
    * collect infomations from the `config` service.
- **SessionCollectior**: Show session data
    * collect infomations from the `session` service.
- **SwiftMailCollector**: mailer info
    * collect infomations from the `mail` service.
- **LogsCollectors**: Show logs of current request.
    * collect infomations from the `log` service.
- **CacheCollectors**: Show caches summary(saved,gets,incs,decs,failds), and each cache operation detail.
    * collect infomations from the `cache` service.

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

### data pesistent

For **file** driver, The default directory for store debugbar data is `Runtime/phalcon`. If it not exists, will try to create auto. You can change it by reconfig.

For **mongodb** driver, The default connection is `mongodb://localhost:27017`, the database  and collection are both named **debugbar**. You must install the **mongo** extension for php.

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

### about baseUri

Be aware of the **baseUri** configuration of your project, you **must** set a currect baseUri for your **uri** service.

If you are using apache, you should enable the Rewirte mod and have a `.htaccess` file under the baseUri directory.

If you are using nginx, you should enable the Rewirte mod and edit the location block of the server configuration like this:

```
    location @rewrite {
        # replace 'baseuri' to your real baseuri
        rewrite ^/baseuri/(.*)$ /baseuri/index.php?_url=/$1;
    }
```


## More

### send custom messages to debugbar

```
\PhalconDebug::startMeasure('start-1','how long');        // startMeasure($internal_sign_use_to_stop_measure, $label)
\PhalconDebug::addMeasurePoint('start');                  // measure the spent time from latest measurepoint to now.
\PhalconDebug::addMessage('this is a message', 'label');  // add a message using a custom label.
\PhalconDebug::info($var1,$var2, $var3, ...);  // add many messages once a time. See PSR-3 for other methods name.(debug,notice,warning,error,...)
\PhalconDebug::addMessageIfTrue('1 == "1"', 1=='1','custom_label'); // add message only when the second parameter is true
\PhalconDebug::addMessageIfTrue('will not show', 1=='0');
\PhalconDebug::addMessageIfFalse('1 != "0" ', 1=='0');       // add message only when the second parameter is false
\PhalconDebug::addMessageIfNull('condition is null', Null ); // add message only when the second parameter is NULL
\PhalconDebug::addMessageIfEmpty('condition is emtpy', $condtion ); // add message only when the second parameter is empty
\PhalconDebug::addMessageIfNotEmpty('condition is not emtpy', $condtion=[1] ); // add message only when the second parameter is not empty
\PhalconDebug::addException(new \Exception('oh , error'));
\PhalconDebug::addMeasurePoint('stop');
\PhalconDebug::stopMeasure('start-1');                    // stopMeasure($internal_sign_use_to_stop_measure)
```

### Volt Functions:

```
addMessage
addMessageIfTrue
addMessageIfFalse
addMessageIfNull
addMessageIfEmpty
addMessageIfNotEmpty
addException
addMeasurePoint
startMeasure
stopMeasure
debug/info/notice/warning/error/emergency/critical
```

#### examples

```
{{ debug( var1, var2 )}}
{{ info( var1, var2 )}}
{{ addMessageIfTrue('$var === true', var ) }}
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

* I strongly suggest you to assign a **host domain** to your project, and set the **baseUri** of uri service to `/`. 

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
