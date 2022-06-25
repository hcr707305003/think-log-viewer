# think-log-viewer
thinkphp6的视图扩展

### 页面展示
![image](https://raw.githubusercontent.com/hcr707305003/think-log-viewer/main/src/view/image/show.png)

### 1.配置路由
~~~
Route::get('log_view', "\Shiroi\ThinkLogViewer\LogServer@index");
~~~

### 2.运行thinkphp服务
~~~ 
php think run
~~~

### 3.浏览器访问`http://127.0.0.1:8000/log_view`即可