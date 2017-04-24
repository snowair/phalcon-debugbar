[![Latest Stable Version](https://poser.pugx.org/snowair/phalcon-debugbar/v/stable)](https://packagist.org/packages/snowair/phalcon-debugbar) 
[![Total Downloads](https://poser.pugx.org/snowair/phalcon-debugbar/downloads)](https://packagist.org/packages/snowair/phalcon-debugbar) 
[![Latest Unstable Version](https://poser.pugx.org/snowair/phalcon-debugbar/v/unstable)](https://packagist.org/packages/snowair/phalcon-debugbar) 
[![License](https://poser.pugx.org/snowair/phalcon-debugbar/license)](https://packagist.org/packages/snowair/phalcon-debugbar)

## Phalcon Debugbar

Integrates [PHP Debug Bar](https://github.com/maximebf/php-debugbar) with [Phalcon Framework](https://github.com/phalcon/cphalcon).


[中文说明](https://github.com/snowair/phalcon-debugbar/blob/master/README_zh.md)

## Features

1. Normal request capturing
2. Ajax request capturing
3. Redirect request chain capturing
4. Simple App, Multi module App and Micro App support
5. Data collected persistent : save to **Local File** or **MongoDB**, or **ElasticSearch**
6. Data storaged by sessionid, it's more firendly for team development.
7. You can close inject debugbar, and on a new browser tab, visit `/_debugbar/open` to see data(and it alse can be disabled).
8. Whoops Integration, and debugbar works well with it.
9. Support palcon 1.3.x,2.x,3.x, PHP5.5~7.1

### Support Collectors

- **MessagesCollector**: Collect custom message, support scalar, array and object
- **TimeDataCollector**: Collect custom time measure.
- **ExceptionsCollector**: Add a exception object to debugbar.
- **MemoryCollector**: Collect memory usage
- **QueryCollector**: Capture each SQL statement, measure spent time of each SQL, show EXPLAIN result of each SELECT statement
    * collect information from the `db` service. Only for Phalcon ORM.
- **DoctrineCollector**: Capture each SQL statement in Doctrine, measure spent time of each SQL.
    * collect information from the `entityManager` service. Only for Doctrine ORM.
- **RouteCollector**: Show Route info of current request.
    * collect information from the `router` service.
- **ViewCollector**:  Show all the rendered templates, measure spent time of each template, show all the templates variables.
    * collect information from the `view` service.
- **PhalconRequestCollector**: Show request headers, cookies, server variables, response headers, querys, post data,raw body
    * collect information from the `request` service.
- **ConfigCollector**: Show the data in the config service.
    * collect information from the `config` service.
- **SessionCollectior**: Show session data
    * collect information from the `session` service.
- **SwiftMailCollector**: mailer info
    * collect information from the `mail` service.
- **LogsCollectors**: Show logs of current request. Support `Phalcon\Logger` and **Monolog**
    * collect information from the `log` service.
- **CacheCollectors**: Show caches summary (saved,gets,incs,decs,failds), and each cache operation detail.
    * collect information from the `cache` service.

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

### Modify index.php

1. Set your App Instance to DI:

    ```php
    $application = new Phalcon\Mvc\Application( $di ); // Important: mustn't ignore $di param . The Same Micro APP: new Phalcon\Mvc\Micro($di);
    $di['app'] = $application; //  Important
    ```

2. Before handle app, register and boot debugbar provider. 

    ```php
    (new Snowair\Debugbar\ServiceProvider())->start();
    // after start the debugbar, you can do noting but handle your app right now.
    echo $application->handle()->getContent();
    ```
    
3. **optional**  to use Whoops, modify the index.php, add follow codes bellow the debugbar service `start()` method.

    ```
    (new \Snowair\Debugbar\Whoops\WhoopsServiceProvider($di));
    ```
    
### Modify The ACL Code

Here is a example for INVO:

```
public function beforeDispatch(Event $event, Dispatcher $dispatcher)
    {
        $auth = $this->session->get('auth');
        if (!$auth){
            $role = 'Guests';
        } else {
            $role = 'Users';
        }
        
        $controller = $dispatcher->getControllerName();
        $action = $dispatcher->getActionName();
        
        /* Debugbar start */
        $ns = $dispatcher->getNamespaceName();
        if ($ns=='Snowair\Debugbar\Controllers') {
            return true;
        }
        /* Debugbar end */
        
        $acl = $this->getAcl();
        $allowed = $acl->isAllowed($role, $controller, $action);
        if ($allowed != Acl::ALLOW) {
            $dispatcher->forward(array(
                'controller' => 'errors',
                'action'     => 'show401'
            ));
                        $this->session->destroy();
            return false;
        }
    }
```

### Data Persistent

For **file** driver, the default directory for store debugbar data is `Runtime/phalcon`. If it doesn't exist, it will be created automatically. You can change it by reconfig.

For **mongodb** driver, You must install the **mongodb** extension and install the phplib : `composer require mongodb/mongodb`
    
For **elastic** driver, You must install the phplib : `composer require elasticsearch/elasticsearch:some-version`

### About baseUri

Be aware of the **baseUri** configuration of your project, you **must** set a currect baseUri for your **uri** service.

If you are using apache, you should enable the Rewrite mod and have a `.htaccess` file under the baseUri directory.

If you are using nginx, you should enable the Rewrite mod and edit the location block of the server configuration like this:

```
    location @rewrite {
        # replace 'baseuri' to your real baseuri
        rewrite ^/baseuri/(.*)$ /baseuri/index.php?_url=/$1;
    }
```


## More

### Use your config

Copy `config/debugbar.php` to your config directory, and change any settings you want. Then use your debugbar config file by:

```php
(new Snowair\Debugbar\ServiceProvider('your-debugbar-config-file-path'))->start();
```

### Send custom messages to debugbar

```php
\PhalconDebug::startMeasure('start-1','how long');        // startMeasure($internal_sign_use_to_stop_measure, $label)
\PhalconDebug::addMeasurePoint('start');                  // measure the spent time from latest measurepoint to now.
\PhalconDebug::addMessage('this is a message', 'label');  // add a message using a custom label.
\PhalconDebug::info($var1,$var2, $var3, ...);  // add many messages once a time. See PSR-3 for other methods name.(debug,notice,warning,error,...)
\PhalconDebug::addMessageIfTrue('1 == "1"', 1=='1','custom_label'); // add message only when the second parameter is true
\PhalconDebug::addMessageIfTrue('will not show', 1=='0');
\PhalconDebug::addMessageIfFalse('1 != "0" ', 1=='0');       // add message only when the second parameter is false
\PhalconDebug::addMessageIfNull('condition is null', Null ); // add message only when the second parameter is NULL
\PhalconDebug::addMessageIfEmpty('condition is emtpy', $condition ); // add message only when the second parameter is empty
\PhalconDebug::addMessageIfNotEmpty('condition is not emtpy', $condition=[1] ); // add message only when the second parameter is not empty
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

#### Examples

```
{{ debug( var1, var2 )}}
{{ info( var1, var2 )}}
{{ addMessageIfTrue('$var === true', var ) }}
```

### Multi Modules

Usually, You needn't modify any other files, if you follow rules bellow:

1. All the services for cache has a name contain `cache`.
2. All the services for db has a name start with `db` or end with `db`.
3. Visit `/_debugbar/open?m={modulename}` to open a independent  debugbar page.

If your service name is't match these rules, you need attach it to debugbar: 

```php
// service.php
$di->set('read-db-test',function(...)); // db service
$di->set('redis',function(...)); // cache service
if ( $di->has('debugbar') ) {
    $debugbar = $di['debugbar'];
    $debugbar->attachDb('read-db-test');
    $debugbar->attachCache('redis');
}
```

### Troubleshooting

* I strongly suggest you to assign a **host domain** to your project, and set the **baseUri** of `uri` service to `/`.

* For ajax/json request, the debug data only stored in the persistent directory as a json file. You can
 Load it to the debugbar form Openhandler(Open icon on the right).

* If the debugbar does not work, the most likely reason is that one or more collectors triggered a error in the runtime.
You can modify the debugbar config file, close collector one by one, retry it until found the collector cause problem.

* For any problems, you can open an Issue on Github.

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
