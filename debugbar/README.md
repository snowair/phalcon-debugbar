## Laravel Debugbar

这个扩展包将 [PHP Debug Bar](http://phpdebugbar.com/) 与  [Phalcon FrameWork](http://phalconphp.com) 集成在了一起.
 
要感谢 laravel-debugbar, 我从中得到了启发, 使用了其中的一些代码, 经过几天夜以继日的工作, PhpDebugbar 终于可以用在Phalcon项目上了!

我在 Mac/PHP5.6/Phalcon 1.3.4 之下开发, 时间关系, 还为其他环境下的全面测试, 如果有问题, 欢迎提Issue或者Pull Reqeust. 

![Screenshot](https://cloud.githubusercontent.com/assets/973269/4270452/740c8c8c-3ccb-11e4-8d9a-5a9e64f19351.png)

注意: 这是一个开发辅助扩展, 切勿部署生产环境. 

## 功能特性

1. 常规请求调试信息收集
2. Ajax请求调试信息收集
3. Redirect请求调试信息
4. 调试信息本地持久化支持
5. 支持 多模块,单模块,微模块应用.
 
### 支持收集的调试数据

 - QueryCollector: 收集所有SQL查询, 每条SQL的执行时间, SELECT语句的EXPLAIN信息
 - RouteCollector: 收集当前路由的相关信息
 - ViewCollector:  收集当前请求渲染的所有模板, 每个模板的渲染耗时, 赋值到视图的视图变量
 - PhalconRequestCollector: 收集请求头信息, 请求数据, 解密后的cookie, RAW BODY, 以及响应头信息
 - ConfigCollector: 收集 config service中的数据.
 - SessionCollectior 收集session数据
 - SwiftMailCollector 收集邮件发送信息

### 基本调试数据:

 - MessagesCollector : 收集自己发送的调试数据
 - TimeDataCollector : 收集时间计算信息
 - MemoryCollector : 请求的内存占用
 - ExceptionsCollector : 异常信息收集

