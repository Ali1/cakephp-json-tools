# CakePHP Json Tools Plugin

[![Framework](https://img.shields.io/badge/Framework-CakePHP%203.x-orange.svg)](http://cakephp.org)
[![license](https://img.shields.io/github/license/ali1/cakephp-json-tools.svg?maxAge=2592000)](https://github.com/LeWestopher/cakephp-monga/blob/master/LICENSE)
[![Github All Releases](https://img.shields.io/packagist/dt/ali1/cakephp-brute-force-protection.svg?maxAge=2592000)](https://packagist.org/packages/ali1/cakephp-brute-force-protection)
[![Travis](https://img.shields.io/travis/ali1/cakephp-brute-force-protection.svg?maxAge=2592000)](https://travis-ci.org/ali1/cakephp-brute-force-protection)
[![Coverage Status](https://coveralls.io/repos/github/ali1/cakephp-brute-force-protection/badge.svg)](https://coveralls.io/github/ali1/cakephp-brute-force-protection)

A CakePHP plugin to assist with creating Json responses from controllers. 

Json Tools has been created to be used by traditional CakePHP projects
which are mostly browser-based, but have a few AJAX or API methods.
The Json Tools component makes creating these a breeze.

## Features
* A component that quickly lets you set up ajax methods.
* Works with CakePHP's ResponseHandler so you don't have to
* Can be used in methods that sometimes output Html and other times Json depending on request headers, just like normal CakePHP behavior

## Requirements

* Composer
* CakePHP 4.0+ (see releases for working 3.7+ release)
* PHP 7.2+

## Installation

In your CakePHP root directory: run the following command:

```
composer require ali1/cakephp-json-tools
```

Then in your Application.php in your project root, add the following snippet:

```php
// In project_root/Application.php:
        $this->addPlugin('JsonTools');
```

or you can use the following shell command to enable to plugin in your bootstrap.php automatically:

```
bin/cake plugin load JsonTools
```

Now add it to your src/AppController.php or to specific controllers
```
class AppController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('JsonTools.Json');
    }
}
```

## Usage (in controller methods that may need to output Json)

### Understanding the boiler plate Json output

This component primes ResponseHandler to output something that looks like this:

```php
[
    'error' => false,
    'field_errors' => [],
    'message' => 'OK',
    '_redirect' => false,
    'content' => null,
];
```

Which corresponds to a json output of:
```json
{"error": false, "field_errors": {}, "message": "OK", "_redirect": false, "content": false }
```

Your controller method can then override these keys or add new ones easily using this component.

### Priming the method with boiler-plate Json output

```php
// All Json actions where you want to use this component should have one of the following lines

/**
* The most basic priming. Will set the boiler-plate variables (see below) that can be processed by ResponseHandler
* should there be a json request. If the request is not XHR/JSON, then this method would not have an effect.
*/
$this->Json->prepareVars();


/**
* Will return true if is Json and is POST/PUT, otherwise false
* Can replace something like $this->getRequest()->is(['post', 'put']) that is often used to check if form is submitted.
* You don't need to run prepareVars() if you use this line
*/
if($this->Json->isJsonSubmit()){}


/**
* Will force the output to be Json regardless of HTTP request headers
* You don't need to run prepareVars() if you use this line
*/
$this->Json->forceJson(); // will force the output to be Json regardless of HTTP request headers


/**
* Throw exception if request is not Json or not POST/PUT
* You don't need to run prepareVars() if you use this line
*/
$this->Json->requireJsonSubmit(); // throw exception if request is not Json or not POST/PUT
```

### Setting the JSON output

Boiler plate output keys (see above) could be overwritten later in your method using one of these methods
```php
$this->Json->set('data', $data); // add a new key called data
$this->Json->set('field_errors', $errors); // replace a key
$this->Json->setMessage('Great'); // shortcut to replace message
$this->Json->setError('Bad input'); // sets error to true and message to 'Bad Input' (can be configured to set error to string 'Bad Input' rather than bool true
$this->Json->redirect(['action' => 'index']); // sets _redirect key to a URL (for javascript client to handle the redirect)
$this->Json->entityErrorVars($user); // change the Json output to error: true, and message: a list of validation errors as a string (e.g. Username: Too long, Email: Incorrect email address)
```

## Example controller

```php
// UsersController.php
    public function ajaxUpdateUser()
    {
        /*
        Json->requireJsonSubmit() will throw exception if not Json and a Post/Put request and also
        It will also prepare boiler plate variables that can be handled by RequestHandler
                    'error' => false,
                    'field_errors' => [],
                    'message' => 'OK',
                    '_redirect' => false,
                    'content' => null,
        In other words, the action output will be {"error": false, "field_errors": {}, "message": "OK", "_redirect": false, "content": false }
        All of these variables can be overridden in the action if errors do develop or example
        */
        $this->Json->requireJsonSubmit();
        if(!$user = $this->Users->save($this->getRequest()->getData()) {
            // Json->entityErrorVars($entity) will change the Json output to error: true, and message: a list of validation errors as a string (e.g. Username: Too long, Email: Incorrect email address)
            $this->Json->entityErrorVars($user);
        } else {
            // will make the Json output _redirect key into a URL. If you use this, your javascript needs to recognise this (see example javascript)
            $this->Flash->success("Saved");
            $this->Json->redirect(['action' => 'view', $user->id]);
        }
    }

    public ajaxGetUser($user_id) {
        $user = $this->Users->get($user_id);
        $this->Json->forceJson(); // output will be Json. As of this line, the Json output will be the boilerplate output (error: false, message: OK etc.)
        $this->Json->set('data', $user); // the output will now have a data field containing the user object
    }

    public userCard($user_id) {
        $user = $this->Users->get($user_id);
        $this->Json->forceJson(); // output will be Json. As of this line, the Json output will be the boilerplate output (error: false, message: OK etc.)
        $this->set(compact('user')); // for use by the template. don't use $this->Json->set so that the user object does not get send in the output 
        $this->Json->sendContent('element/Users/card'); // the Json output will have a 'content' key containing Html generated by the template
    }

    public otherExamples() {
        // Configuration
        $this->Json->setErrorMessageInErrorKey(true); // (default false)
            // true: if $this->Json->setError('error message') is called, the error key and the message key will contain the error message
            // false:  if $this->Json->setError('error message') is called, the error message will be in the message key and the error key will be true and
        $this->Json->setHttpErrorStatusOnError(true); // (default false)
            // by default, the HTTP response is always 200 even in error situations
        $this->Json->setMessage('Great, all saved'); // shortcut to set the message key
        $this->Json->set('count', 5); // set any other json output keys you want to output
    }
```

## Example AJAX form

This is an example form that corresponds to the ajaxUpdateUser method above.

templates/Users/edit.php
```
    <?php
    $this->Html->script('ajax_form', ['block' => true]);
    // optionally, also install BlockUI and include it, for a loading indicator while ajax is running (http://malsup.com/jquery/block/)
    ?>
    <?= $this->Form->create($user, ['url' => ['controller' => 'Users', 'action' => 'ajaxUpdateUser'], 'onsubmit' => 'return dynamic_submit(this);']) ?>
    <fieldset>
        <legend><?= __('Update User') ?></legend>
        <?php
        echo $this->Form->control('username');
        echo $this->Form->control('name');
        echo $this->Form->control('email');
        ?>
    </fieldset>
    <?= $this->Form->button(__('Submit')) ?>
    <?= $this->Form->end() ?>
```

webroot/ajax_submit.js
```js
/*

Flexible Ajax Form Submission Function
Usage: <form ... onsubmit="return ajax_submit(this);"> or <form ... onsubmit="return ajax_submit(this, {config});">
Will expect json response from server
* If error: true, will alert error message and no further callbacks will occur
* If success (error: false), what happens next depends on the success config
* * (note if server return _redirect key in JSON, then this will take precedence and the page will be redirected)
* * DEFAULT { success: true } By default, the page will just reload on success
* * { success: false } If false is given, do nothing on success
* * { success: function(data, form){} } If a function is given, the data will be passed to that callback function along with the form element. this callback function will be responsible for taking further action
* * { success: $('.results') } If an object(element) is given, then HTML from the JSON content key will be loaded into the given element
* * { success: '/url/to/success' } If a string is given, will redirect to this URL on success

Other config:
blockElement - if element given, only that element is blocked while loading, rather than the whole page

 */

window.ajax_submit = function(form, config){
    config = config || {}; // config is optional
    config.success || (config.success = true);

    let mode;
    if (config.success === true) { // refresh mode
        mode = 'refresh';
    } else if (typeof config.success == 'string') { // redirect mode
        mode = 'redirect';
    } else if (typeof config.success === 'function') { // callback mode
        mode = 'callback';
    } else if (typeof config.success === 'object') { // load HTML mode
        mode = 'html';
    } else { // do nothing mode
        mode = '';
    }
    config.blockElement || (config.blockElement = true); // true = whole page, false = none, element = block only element

    const ajaxOpts = {
        url: $(form).attr('action'),
        data: $(form).serialize(),
        context: form,
        method: 'post',
        headers: {},
        dataType: 'json'
    };

    if($(form).attr('method') && $(form).attr('method') === 'get') {
        ajaxOpts.method = 'get';
    }

    if(typeof $.blockUI !== 'undefined') {
        if(config.blockElement === true){
            $.blockUI({baseZ: 2000}); // modals are 1005
        } else if (config.blockElement) {
            $(config.blockElement).block();
        }
    }

    try{
        $.ajax(ajaxOpts)
            .done(function(data, textStatus, jqXHR){
                if(data.error) {
                    $('.blockUI.blockOverlay').parent().unblock(); // take care of any blocked UI
                    alert(data.message);
                } else {
                    if (data._redirect) {
                        window.location = data._redirect;
                    }
                    if (mode === 'refresh') {
                        location.reload();
                    } else if (mode === 'redirect') {
                        window.location = config.success;
                    } else if (mode === 'callback') {
                        $('.blockUI.blockOverlay').parent().unblock(); // take care of any blocked UI
                        config.success(data, this); // pass form back
                    } else if (mode === 'html') { // load HTML mode
                        $('.blockUI.blockOverlay').parent().unblock(); // take care of any blocked UI
                        $(config.success).html(data);
                    } else { // do nothing mode
                        $('.blockUI.blockOverlay').parent().unblock(); // take care of any blocked UI
                    }
                }
            })
            .fail(function(jqXHR, textStatus, errorThrown) {
                $('.blockUI.blockOverlay').parent().unblock(); // take care of any blocked UI
                console.log(jqXHR);
                if (typeof jqXHR.responseJSON !== 'object' || typeof jqXHR.responseJSON.message !== 'string') {
                    alert(errorThrown);
                } else {
                    alert(errorThrown + ': ' + jqXHR.responseJSON.message);
                }
            }).always(function(data){
            console.log(data);
        });
        return false;
    }catch(err){
        alert('An error occurred');
        console.log(err);
        return false;
    }
};
```