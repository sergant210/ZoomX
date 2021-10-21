(Documentation in the process of creation).

ZoomX offers an alternative way to handling a request and preparing a response. The main goal is not to use elements from the database (as far as it's possible) and use your favorite IDE and versioning support for convinient development. The modRequest class has been refactored and optimized. In addition, a routing mechanism is built in. [FastRoute](https://github.com/nikic/FastRoute) is used for this. Usual DB templates are ignored and can only be used to bind TVs to resources. 
When preparing a response, the standard parser is not used (in the strict router mode). PHP template engines are used instead. [Smarty template engine](https://www.smarty.net) comes out of the box. Because it's installed along with MODX. 

## IMPORTANT
It's required PHP >= 7.1.
  
* [How to use](./README.md#how-to-use)
  * [Error templates](./README.md#error-templates)
* [Routing](./README.md#routing)
  * [Creating routes](./README.md#creating-routes)
  * [Controllers](./README.md#controllers)
  * [Routing mode](./README.md#routing-mode)
* [Virtual pages](./README.md#virtual-pages)
* [Working in API mode](./README.md#working-in-api-mode)
* [Smarty template engine](./README.md#smarty-template-engine)
  * [Using Smarty](./README.md#using-smarty)
  * [ZoomX modifiers](./README.md#zoomx-modifiers)
  * [Caching](./README.md#caching)
  * [ZoomX blocks](./README.md#zoomx-blocks)
  * [ZoomX functions](./README.md#zoomx-functions)
  * [File elements](./README.md#file-elements)
    * [File chunks](./README.md#file-chunks)
    * [File snippets](./README.md#file-snippets)
* [Helpers](./README.md#helpers)
* [System settings](./README.md#system-settings)
  * [Main area](./README.md#main-area) 
  * [Smarty area](./README.md#smarty-area) 
* [System settings for extending classes](./README.md#system-settings-for-extending-classes)
***

## How to use
Install this package over the Package Manager. Switch On the system setting "friendly_urls". After that open `core/config/routes.php` and uncomment the required routes or define your own. Initially, templates are located in the folder `core
/components/zoomx/templates/default/`. But it can be redefined. Two system settings are responsible for this -  `zoomx_template_dir` (by default, `core/components/zoomx/templates/`) and `zoomx_theme` (by default, `default`). I advise to move the template folder to `core/templates/`. Otherwise, all changes will be lost on next updates.

There are 3 templates out of the box - "base.tpl", "index.tpl" and "error.tpl". Create your own templates and chunks (partials or subtemplates) using them as template. 

#### Error templates
By default, the `error.tpl` template is used for all errors. If you need your own template for a specific error, create a template with a name in the form of an error code. For example, the template for error 404 should be called '404.tpl'.

## Routing
### Creating routes
Next, you need to associate the created templates with resources. To do this, open the file `core/config/routes.php` and add a route for the corresponding URI.
```php
$router->get('hello.html', function() {
    return new ZoomView('hello.tpl', ['name' => 'John']);
});
$router->get('users/{id}', function($id) use($modx) {
    $user = $modx->getObject('modUser', ['id' => (int)$id]);
    return viewx('profile.tpl', $user ? $user->toArray() : []);
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
- Soft (mixed). If no route is found for the request URI, MODX will continue processing the request as usual. If the route was found and the resource was not found, the 404 error will be fired as in Strict mode.
- Strict (exclusive). If no route is found for the request URI, 404 error will occur and processing of the request will be stopped.

## Virtual pages
By default, MODX searches for a resource for the URI specified in the route. But if you want to define the resource yourself, for example for RESTful mode, then disable the resource autoloading in the `zoomx_autoload_resource` system setting. If you need to disable the resource autoloading only in a particular route, you can change the setting directly in the route.
```php
$router->get('users/{id}/profile',  function ($id) use ($modx) {
    zoomx()->autoloadResource(false);  // === $modx->setOption('zoomx_autoload_resource', false); 
    $user = $modx->getObject('modUser', ['id' => (int)$id])
    if (!user) {
        abortx(404, 'User not found');
    } 
    return viewx('profile.tpl', ['user' => $user]);
});
```

## Working in API mode
Now you don't need to create a particular controller for API requests. Just define a corresponding route. From the frontend you have to pass the header "Accept" with "application/json" value in the request. In this case, MODX will not search for the resource by URI and will return only the specified data.

Return an array and you get back a json encoding response.
```php
// Return an array
$router->get('api/foo', function() {
    return ['foo' => 'bar']);
});
// Or a JSON Response with custom headers.
$router->get('api', function() {
    return jsonx(['foo' => 'bar'], ['Foo' => 'Bar']);
});
```
The response will be converted to json format
```js
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
Meta information can be switched off by the system setting `zoomx_include_request_info`.  
  
Get a resource either from the cache or from the database
```php
$router->get('api/resource/{id}', function($id) {
    $resource = zoomx()->getResource[(int)$id]);
    return jsonx($resource->toArray());
});
```
To return a failure response use the `abortx` function with corresponding HTTP code
```php 
$router->get('profile', function() use($modx) {
    if (!$modx->user->isAuthenticated()) {
        abortx(401, 'You must log in.');
    } 
return jsonx($modx->user->Profile->toArray());
});
```

## Smarty template engine
Smarty is a fast and powerful template engine and it comes out of the box. Besides, it has many predefined plugins ([built-in functions](https://www.smarty.net/docs/en/language.builtin.functions.tpl), [custom functions](https://www.smarty.net/docs/en/language.custom.functions.tpl), [built-in modifiers](https://www.smarty.net/docs/en/language.modifiers.tpl. You can find all the default modifiers in the [Smarty documentation](https://www.smarty.net/docs/en/language.modifiers.tpl). ZoomX adds its own plugins.

### Using Smarty
By default, Smarty works only when a route for the specified URI is found. In other cases, the parser specified in the `parser_class` setting works. Using the system setting `zoomx_use_zoomx_parser_as_default`, you can specify that Smarty is always used as the default template engine. But only the template content will be parsed. The content of the resource will not be processed.  Here is an expample of a MODX Template:
```html
<!doctype html>
<html lang="{'cultureKey'|config}">
<head>
    {block "title"}<title>{'pagetitle'|resource} - {'site_name'|config}</title>{/block}
    <base href="{'site_url'|config}" />
    <meta charset="{'modx_charset'|config}" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=1" />
    {block "styles"}{/block}
</head>
<body>
    <div class="container">
    {block "content"}
        {'content'|resource}  // to parse the resource content - {'content'|resource|parse}
    {/block}
    </div>
    {block "scripts"}{/block}
</body>
</html>
```
Standard MODX syntax is available via the special block `parse`:
```php
{parse}
[[!snippet?foo=`bar`]]
{/parse}
```
Templates can be extended. To do this, create a basic template in the templates folder specified in the corresponding system setting. And in the MODX template, specify this basic template.
```html
{extends "base.tpl"}

{block "content"}
<h1>{'longtitle'|resource:'pagetitle'}</h1>
<section>
	{'content'|resource}
</section>
{/block}
```
### Caching
To cache an entire page, you need to enable the `zoomx_caching` system setting. It is disabled by default. You can control the caching of individual tags in the same way as it is done in MODX using the sign `!`, but in a slightly different way - using a special `nocache` attribute or a block of the same name.
```html
<!-- Attribute -->
{'username'|user nocache}
<!-- Block --> 
{nocache}
{include 'navigation.tpl'}
{include 'header'}  // extension (.tpl) can be omitted (specified in the "zoomx_template_extension" system setting)
{/nocache}
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
* declension - declension of words for the specified number.
Arguments:  
\- words - array of options;
\- include - include the number.
```php
{10|declension:['apple', 'apples']}  // apples  
{10|declension:['apple', 'apples']:true}  // 10 apples  
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
* js, 
* jstobottom - register js to the end of the page.
Arguments:  
\- plaintext - `true`/`false`.  
```php
{'assets/js/scripts.js'|jstobottom}
// Equivalent to
{'assets/js/scripts.js'|js}
{'<script>let foo = "bar";</script>'|js:true}
```
* jstohead - register js to the head of the page.
Arguments:  
\- plaintext - `true`/`false`.  
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
* ismember - states whether the current user is a member of a group or groups.
```php
{if 'Users'|ismember}
  <p>Hello, member!</p>
{else}
  <p>No content</p>
{/if}
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
\- format (bool) - wrap the output with <pre> tag;  
\- escape - (bool) - use htmlspecialchars function.  
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
* modx - can be used to parse content with MODX tags by a parser specified in the "parser_class" system setting.
Arguments:  
\- parser -parser class.
```php
{modx}
<a href="[[~[[*id]]]]">[[*pagetitle]]</a>
{/modx}
```
* parse - can be used to parse content with Smarty tags.
Arguments:  
\- parser - parser class. By default, ZoomSmarty.
```php
// Using a MODX parser parser by default.
{parse}
{$modx->resource->tv} // TV contains Smarty tags. 
{/parse}
```
### ZoomX functions
* run - runs a MODX snippet or a file snippet.
```html
<!-- MODX snippet -->
{run snippet='usual_snippet' params=['foo' => 'bar']}
<!-- File snippet -->
{run file='file_snippet' params=['foo' => 'bar']}
```

### File elements
#### File chunks
Files from the template directory will be used.
```html
<!-- Smarty syntax -->
{include 'article.tpl'}
<!-- using the @FILE binding -->
{'@FILE article.tpl'|chunk}
```
#### File snippets
Before using, define a directory for storing file snippet and create it if it doesn't exist. 
```html
<!-- Smarty syntax -->
{run file='some_file_snippet' params=['foo' => 'bar', 'tpl' => '@INLINE <span>{$param}</span>']}
<!-- using the @FILE binding -->
{'@FILE some_file_snippet'|snippet:['foo' => 'bar', 'tpl' => '@FILE file_chunk.tpl']}
<!-- using $zoomx object -->
{$zoomx->runFileSnippet('some_file_snippet', ['foo' => 'bar', 'tpl' => 'modx_chunk'])}
```

## Service class
You can get a service class using the `zoomx` function. It contains a number of useful methods.
- `shouldBeJson` - determines if the given content should be turned into JSON.
- `isAjax` - checks for the presence of the HTTP header `HTTP_X_REQUESTED_WITH`.
- `autoloadResource` - resource auto-loading switch. Pass `true` or `false` as an argument. Can be used for virtual pages.
- `getResource` - gets a requested resource and all required data. Pass resource alias or id as an argument.
- `config` - replacement for the `modX::getOption()` method.
- `getChunk` - replacement for the `modX::getChunk()` method. It allows you to use the @FILE and @INLINE bindings in the name of the chunk.
- `getSnippet` - replacement for the `modX::snippet()` method.
- `runFileSnippet` - executes a file like a snippet.

## Helpers
- abortx() - throws an HttpException with the given data.
- Arguments:  
  \- `(int)` code - HTTP code.
  \- `(string)` message - error message.
  \- `(string)` title - page title.
  \- `(array)` headers - headers.
```php
$router->get('profile', function() {
    if ($) {
        abortx('404', 'Item not found!');
    }
    return jsonx($item->toArray(), ['X-Some-Header' => 'Some value']);
});
```
The following codes are available out of the box - 
- 400 - Bad request.
- 401 - Unauthorized.
- 403 - Forbidden.
- 404 - Not found.
- 406 - Not Acceptable.
- 415 - Unsupported media type.
- 500 - Internal Server Error.
- 503 - Service Unavailable.

For codes 400, 406, 415, 500 and 503 the OnRequestError event is added. This event also will be fired for custom exceptions.

You can create your own codes. To do this you have to specify the config of the custom exceptions in the file `core/config/exceptions.php`.

- redirectx() - returns a redirect response. Can be used insteadof `$modx->sendRedirect($url)`;
- Arguments:  
  \- `(string)` url - new URL to redirect.
  \- `(int)` status - HTTP code. Available values - 201, 301, 302, 303, 307, 308. By default, 302.
  \- `(array)` headers - headers.
```php
$router->get('some-url', function() {
    return redirectx('new-url', 301);
});
- jsonx() - returns a JSON response.
- Arguments:  
  \- `(array)` data - array to return to the user.
  \- `(array)` headers - headers.
```php
$router->get('items/{id}', function($id) {
    if (!$item = zoomx('modx')->getObject('Item', ['id' => (int)$id])) {
        abortx('404', 'Item not found!');
    }
    return jsonx($item->toArray(), ['X-Some-Header' => 'Some value']);
});
```
- parserx() - returns an object of the specified parser.
```php
parserx();  // === zoomx('parser') === zoomx()->getParser();
```
- viewx() - get a view object for the given template.
- Arguments:  
  \- `(string)` tpl - template name.
  \- `(array)` data - template variables.
```php
$router->get('articles/{alias}', function($alias) {
    return viewx('article.tpl', ['foo' => 'bar']);
});
```
- zoomx() - returns an instance of the ZoomX service class.
  Arguments:  
  \- `(string)` property - property name. A simplified version of the call via the corresponding get'Property' method. Available properties - `modx`, `parser`, `request`, `response`, `elementService`.
```php
zoomx('request');  // === zoomx()->getRequest();
// get an usual MODX chunk. 
$content = zoomx()->getChunk('modx.chunk', $params);
// run an usual MODX snippet.
zoomx()->runSnippet('modx.snippet', $params);
```

- filex() - returns a specified file. Can be used for managing of the files.
- Arguments:  
  \- `(string)` path - absolute path to file.
  \- `(bool)` isAttachment - to return as an attachment.
  \- `(bool)` deleteFileAfterSend - to delete after response.
```php
$router->get('files/{file}',  function ($file) use ($modx) {
    // Check permission
    if (!$modx->user->isMember('Subscribers)) {
        abortx(403, 'Only members of the "Subscribers" group can download files.');
    }
    zoomx()->autoloadResource(false);  // Don't search the resources with the URI 'files/modx.pdf'.
    // Don't forget to sanitize the file name $file 
    return filex(MODX_CORE_PATH . "path/to/subscribers/files/$file", true);
});
$router->get('file.tpl',  function () {
    zoomx()->autoloadResource(false);
    // Download with new name.
    return filex(MODX_CORE_PATH . "path/to/file.pdf", true)->downloadAs('newFileName.pdf');
});
```


## System settings
#### Main area
* zoomx_autodetect_content_type - enables automatic detection of the Content-Type in the disabled resource autoloading mode.
* zoomx_autoload_resource - disables searching and auto-loading the resource. This allows to use fully virtual pages.
* zoomx_caching - to cache template files. By default, `false`. In development mode it's better to disable it.
* zoomx_enable_pdotools_adapter - replaces the Fenom template engine with the ZoomX one for parsing chunks in the pdoTools snippets.
* zoomx_enable_exception_handler - enable its own exception handler for strict routing mode. 
* zoomx_http_method_override - allows to specify the HTTP methods "PATCH", "PUT" and "DELETE" (not supported in HTML forms) by setting a form input element named as "_method" (`<input type="hidden" name="_method" value="PUT">`).
* zoomx_include_modx - include $modx and $zoomx objects into templates. By default, `true`.
* zoomx_include_request_info - adds information about the request to the response in API mode.
* zoomx_modx_tag_syntax - allows to use MODX style tags - {'*pagetitle'}, {'++site_name'}, {'~5'} and {'%lexicon'}. A negative impact on performance.
* zoomx_parser_class - parser class. It should implement the Zoomx\ParserInterface interface. By default, `ZoomSmarty`.
* zoomx_routing_mode - routing mode. 0 - disabled (routes are ignored); 1 - mixed (if no route is found, MODX will continue the search); 2 - strict (if no route is found, error 404 will occur). By default, `1`.
* zoomx_show_error_details - show full error information in the error page.
* zoomx_file_snippets_path - absolute path to file snippets. By default, `{core_path}elements/snippets/`.
* zoomx_template_dir - full path to [template files](https://www.smarty.net/docs/en/variable.template.dir.tpl). By default, `{core_path}components/zoomx/templates/`.
* zoomx_template_extension - template file extension. It is used for security reasons. By default, `tpl`.
* zoomx_theme - site theme. it's a folder name in the template directory. It allows you to manage site themes. By default, `default`.
* zoomx_use_zoomx_parser_as_default - use the specified template engine instead of the MODX parser.
#### Smarty area
* zoomx_default_tpl - it's used to output errors for which a custom template is not defined. By default, `error.tpl`.
* zoomx_modx_tag_syntax - allows to use MODX style tags - {'*pagetitle'}, {'++site_name'}, {'~5'} and {'%lexicon'}. A negative impact on performance.
* zoomx_smarty_cache_dir - path to [cached template files](https://www.smarty.net/docs/en/variable.cache.dir.tpl) relative to `core/cache/`. By default, `zoomx/smarty/cache/`.
* zoomx_smarty_compile_dir - path to [compiled template files](https://www.smarty.net/docs/en/variable.compile.dir.tpl) relative to `core/cache/`. By default, `zoomx/smarty/compile/`.
* zoomx_smarty_config_dir - full path to [config files](https://www.smarty.net/docs/en/variable.config.dir.tpl). By default, `{core_path}config/`.
* zoomx_smarty_custom_plugin_dir - full path to custom Smarty plugins. By default, `{core_path}components/zoomx/smarty/custom_plugins/`.
* zoomx_smarty_security_enable - enables the mode for managing Smarty security, which is defined in the security class.
* zoomx_smarty_security_class - the class in which the [security settings](https://www.smarty.net/docs/en/advanced.features.tpl#advanced.features.security) are defined.
* zoomx_template_extension - template file extension. It is used for convenience and for security reasons. By default, tpl.

## System settings for extending classes
You can override these settings to replace the base classes with custom ones.  
 Setting name | Default   
 ------------- |-------------  
 zoomx_parser_class   | ZoomSmarty   
 zoomx_response_class | ZoomResponse   
 zoomx_request_class | ZoomRequest   
 zoomx_json_request_class | Zoomx\Json\Request   
 zoomx_json_response_class | Zoomx\Json\Response   
 zoomx_alias_request_handler_class | Zoomx\AliasRequestHandler   
 zoomx_id_request_handler_class | Zoomx\IdRequestHandler   
 zoomx_file_response_class | Zoomx\FileResponse   
 zoomx_view_class | Zoomx\View   
 zoomx_element_service_class | Zoomx\Support\ElementService   
 zoomx_exception_handler_class | Zoomx\ExceptionHandler   
