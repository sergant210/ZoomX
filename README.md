ZoomX offers an alternative way to handling a request and preparing a response. The main goal is not to use elements from the database (as far as it's possible) and use your favorite IDE and versioning support for convinient development. The modRequest class has been refactored and optimized. In addition, a routing mechanism is built in. [FastRoute](https://github.com/nikic/FastRoute) is used for this. Usual DB templates are ignored and can only be used to bind TVs to resources. 
When preparing a response, the standard parser is not used (for the strict router mode). PHP template engines are used instead. [Smarty template engine](https://www.smarty.net) comes out of the box. Because it's installed along with MODX. 

## IMPORTANT
It's required PHP >= 7.0.
  
 
## How to use
Install this package over the Package Manager. Switch On the system setting "friendly_urls". After that open `core/config/routes.php` and uncomment the required routes or define your own. Initially, templates are located in the folder `core
/components/zoomx/templates/default/`. But it can be redefined. Two system settings are responsible for this -  `zoomx_template_dir` (by default, `core/components/zoomx/templates/`) and `zoomx_theme` (by default, `default`). I advise to move the template folder to `core/templates/`.

There are 3 templates out of the box - "base.tpl", "index.tpl" and "404.tpl". Create your own templates and chunks (partials or subtemplates) using them as template. 

## Routing
Next, you need to associate the created templates with resources. To do this, open the file `core/config/routes.php` and define a route for the corresponding URI.
```php
$router->get('hello.html', function() {
    return new ZoomView('hello.tpl', ['name' => 'John']);
});
$router->get('users/{id}', function($id) use($modx) {
    $user = $modx->getObject('modUser', ['id' => (int)$id]);
    return viewx('profile.tpl', $user->toArray());
});
```
Read more about routes in the documentation for [FastRoute](https://github.com/nikic/FastRoute).

You can return a string.
```php
$router->get('hello.html', function() {
    return '<h1>Hello, John!</h1>';
});
```
Example of redirecting
```php
$router->get('product1.html', function() use($modx) {
    $modx->sendRedirect('catalog/product2.html');
});
// use the resource identifier
$router->get('resource.html', function() use($modx) {
    // Specify resource id
    $modx->resourceIdentifier = 2;  
    // Or resource URI
    $modx->resourceIdentifier = 'another.html';
    
    return viewx('page.tpl');
});
```

### Controllers
You can use controllers instead of functions.
```php
$router->get('users', ['Zoomx\Controllers\UserController', 'index']);
// The index method can be omitted
$router->get('users', Zoomx\Controllers\UserController::class);
```
Controllers must extend the base controller `Zoomx\Controllers\Controller`.
```php
<?php

namespace Zoomx\Controllers;

class UserController extends Controller
{
    public function index()
    {
        return viewx('users.tpl');
    }
}
```

### Routing mode
The router can work in 3 modes:
- Disabled. All specified routes are ignored. MODX will work as usual.
- Mixed (soft). If no route is found for the request URI, MODX will continue processing the request as usual. 
- Strict. If no route is found for the request URI, 404 error will occur and processing of the request will be stopped.

## Working in API mode
To do this you just need to pass the header "Accept" with "application/json" value in the request.
```php
$router->get('api/v1/foo', function() {
    return ['foo' => 'bar'];
});
```
The response will be converted to json format
```javascript
{
  success: true,
  data: {
      foo: "bar"
  },
  meta: {
  	total_time: "0.0230 s",
  	query_time: "0.0000 s",
  	php_time: "0.0230 s",
  	queries: 1,
  	memory: "2 048 kb"
  }
}
```
If you need to add a custom header to the response use jsonx() helper
```php
$router->get('api/v1/foo', function() {
return jsonx(['foo' => 'bar'])->header('X-Custom-Header', 'Value');
});
```


## Smarty template engine
Smarty is a fast and powerful template engine and it comes out of the box. Besides it has many predefined plugins ([built-in functions](https://www.smarty.net/docs/en/language.builtin.functions.tpl), [custom functions](https://www.smarty.net/docs/en/language.custom.functions.tpl), [built-in modifiers](https://www.smarty.net/docs/en/language.modifiers.tpl. Default modifiers you can found in the [Smarty documentation](https://www.smarty.net/docs/en/). ZoomX adds its own plugins.

Standard MODX syntax is available via the special block `parse`:
```php
{parse}
[[!snippet?foo=`bar`]]
{/parse}
```

### ZoomX modifiers
* chunk - get a MODX chunks.  
Arguments:  
\- array of parameters.  
```php
// Cached chunk
{'chunkName'|chunk:['foo' => 'bar']}
// Noncached snippet (Smarty syntax)
{'chunkName'|chunk:['foo' => 'bar'] nocache}
```
* config - get a system setting.
```php
//[[++site_name]]
{'site_name'|config}
```
* css,
* csstohead - register CSS to be injected inside the HEAD tag of a resource.  
Arguments:  
\- media;  
```php
{'assets/css/styles.css'|csstohead}
// Equivalent to
{'assets/css/styles.css'|css}
{'assets/css/styles.css'|css:all}
```
* html,
* htmltobottom - register a html block to the end of the page.
```php
{'HTML content'|htmltobottom}
```
* htmltohead - register a html block to the head section of the page. 
```php
{'HTML content'|htmltohead}
```
* js. 
* jstobottom - register js to the end of the page.
Arguments:  
\- plaintext - true/false;  
```php
{'assets/js/scripts.js'|jstobottom}
// Equivalent to
{'assets/js/scripts.js'|js}
{'<script>let foo = "bar";</script>'|js:true}
```
* jstohead - register js to the head of the page.
Arguments:  
\- plaintext - true/false;  
```php
{'assets/js/scripts.js'|jstohead}
{'<script>let foo = "bar";</script>'|jstohead:true}
```
* ignore - output an unparsed tag.
```php
{'content'|resource|ignore}  // output: {'content'|resource}
// the same as 
{literal}
{'content'|resource}
{/literal}
```
* lexicon - output a lexicon entry for a given key.  
Arguments:  
\- array of parameters;  
\- language or Namespace-specific options to load the desired topic (see [documentation](https://docs.modx.com/current/en/extending-modx/internationalization).  
```php
//[[%lang]]
{'lang'|lexicon}
// with parameters
{'lang'|lexicon:['foo' => 'bar']}
// To load a specific topic
{'lang'|lexicon:[]:'es:school:default'} 
```
* modx - parse content with the MODX parser.  
```php
{'[[*pagetitle]] - [[++site_name]]'|modx}
```
* parse - can be used for resource fields or TVs containing tags.  
Arguments:  
\- parser class. By default, '' that means to use default ZoomX parser.  
```php
// Use a ZoomX parser
{'content'|resource|parse}
// Use a MODX parser
{'[[*pagetitle]] - [[++site_name]]'|parse:'modParser'}
```
* ph - get a MODX placeholder.
```php
//[[+modx.user.id]]
{'modx.user.id'|ph}
```
* print - outputs an escaped and formatted string.  
Arguments:  
\- format - true/false;  
\- escape - true/false.  
```php
{$array|print}
// print a raw string
{$array|print:false:false}
```
* resource - get a specified field value of the current resource.
```php
{'pagetitle'|resource}
// with default value from another resource field
{'longtitle'|resource:'pagetitle'}
// with default value as a simple string
{'longtitle'|resource|default:'default value'}
// tv value
{'tv'|resource}
```
* snippet - run a specified MODX snippet.  
Arguments:  
\- array of parameters.
```php
// Cached snippet
{'snippetName'|snippet:['foo' => 'bar']}
// Noncached snippet (Smarty syntax)
{'snippetName'|snippet:['foo' => 'bar'] nocache}
// Noncached snippet with a propertySet
{'snippetName@PropSet'|snippet:['foo' => 'bar'] nocache}
```
* tv - get a TV of the current resource.
```php
{'tv_name'|tv}
```
* url - generate a URL representing a specified resource.  
Arguments:  
\- context. Can be a string or an array of ;  
\- url arguments;  
\- scheme;  
\- options.  
```php
{5|url}
{5|url:'web':['foo' => 'bar']:-1}
// Use arguments in array
{5|url:['scheme' => 'abs']}
```
* user - get a user object field.
```php
{'username'|user}
{'email'|user}
```

### ZoomX blocks
* auth - returns a block content only for authenticated users.
```php
{auth}
content only for authenticated users.
{/auth}
```
* guest - returns a block content only for guest.
```php
{guest}
content only for guests.
{/guest}
```
* parse - can be used to parse content with MODX tags.
Arguments:  
\- parser -parser class.
```php
// Using a MODX parser parser by default.
{parse}
<a href="[[~[[*id]]]]">[[*pagetitle]]</a>
{/parse}
```

## JSON API
Now you don't need to create a particular controller for API requests. Just define a corresponding route.

#### Usual request
Return an array and you get back an json encoding response.
```php
// Return an array
$router->get('api', function() {
    return ['foo' => 'bar']);
});
// Or a JSON Response object with custom headers.
$router->get('api', function() {
    return jsonx(['foo' => 'bar'])->header('MyHeader', 'Value');
});
```

#### Ajax request
Ajax requests must have the header "Accept" with value "application/json".
```php
// Ajax request
$router->get('api/resource/{id}', function($id) use($modx) {
    $resource = $modx->getObject('modResource', ['id' => (int)$id]);
    return jsonx($resource->toArray());
});
```

## Settings
* zoomx_caching - to cache template files. By default, `true`. In development mode it is better to disable it.
* zoomx_default_tpl - default template. It's used when the router works in the strict mode and no route is found and the error page not found. By default, `base.tpl`.
* zoomx_modx_tag_syntax - allows to use MODX style tags - {'*pagetitle'}, {'++site_name'}, {'~5'} and {'%lexicon'}. A negative impact on performance.
* zoomx_routing_mode - routing mode. 0 - disabled (routes are ignored); 1 - mixed (if no route is found, MODX will continue the search); 2 - strict (if no route is found, error 404 will occur). By default, `1`.
* zoomx_include_modx - allow the `$modx` object in templates. By default, `true`.
* zoomx_parser_class - parser class. It should implement the Zoomx\ParserInterface interface. By default, `ZoomSmarty`.
* zoomx_theme - site theme. It's a folder name in the template directory. It allows you to manage site themes. By default, `default`.
* zoomx_template_dir - full path to [template files](https://www.smarty.net/docs/en/variable.template.dir.tpl). By default, `{core_path}components/zoomx/templates/`.
  
* zoomx_smarty_cache_dir - path to [cached template files](https://www.smarty.net/docs/en/variable.cache.dir.tpl) relative to `core/cache/`. By default, `zoomx/smarty/cache/`.
* zoomx_smarty_compile_dir - path to [compiled template files](https://www.smarty.net/docs/en/variable.compile.dir.tpl) relative to `core/cache/`. By default, `zoomx/smarty/compile/`.
* zoomx_smarty_config_dir - full path to [config files](https://www.smarty.net/docs/en/variable.config.dir.tpl). By default, `{core_path}config/`.
* zoomx_smarty_custom_plugin_dir - full path to custom Smarty plugins. By default, ``.

