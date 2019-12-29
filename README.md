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

### Features
* A component that quickly lets you set up ajax methods.
* Works with CakePHP's ResponseHandler so you don't have to

### Requirements

* Composer
* CakePHP 4.0+ (see releases for working 3.7+ release)
* PHP 7.2+

### Installation

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

### Usage

#### Methods that require Json

##### Priming the method with boiler-plate Json output

```php
// All Json actions where you want to use this component should have one of the following lines:
$this->Json->prepareVars(); // The most basic. Will set the boiler-plate variables that can be processed by ResponseHandler if there is a json request
// The boiler-plate Json output is this:
[
    'error' => false,
    'field_errors' => [],
    'message' => 'OK',
    '_redirect' => false,
    'content' => null,
];
// The json output is {"error": false, "field_errors": {}, "message": "OK", "_redirect": false, "content": false }
// These keys could be overwritten later in your method using one of:
$this->Json->set('data', $data); // add a new key called data
$this->Json->set('field_errors', $errors); // replace a key
$this->Json->setMessage('Great'); // shortcut to replace message
$this->Json->setError('Bad input'); // sets error to true and message to 'Bad Input' (can be configured to set error to string 'Bad Input' rather than bool true
$this->Json->redirect(['action' => 'index']); // sets _redirect key to a URL (for javascript client to handle the redirect)
$this->Json->entityErrorVars($user); // change the Json output to error: true, and message: a list of validation errors as a string (e.g. Username: Too long, Email: Incorrect email address)


// Instead of $this->Json->prepareVars(), all of these will also prepareVars for you, but have further benefits
if($this->Json->isJsonSubmit()){} // will return true if is Json and is POST/PUT, otherwise false
$this->Json->forceJson(); // will force the output to be Json regardless of HTTP request headers
$this->Json->requireJsonSubmit(); // throw exception if request is not Json or not POST/PUT

```

```php
// UsersController.php
    public $components = ['JsonTools']; // can also be in AppController.php
    
    ...
    
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


### Many more features