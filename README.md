# CakePHP Json Tools Plugin

[![Framework](https://img.shields.io/badge/Framework-CakePHP%203.x-orange.svg)](http://cakephp.org)
[![license](https://img.shields.io/github/license/ali1/cakephp-json-tools.svg?maxAge=2592000)](https://github.com/LeWestopher/cakephp-monga/blob/master/LICENSE)
[![Github All Releases](https://img.shields.io/packagist/dt/ali1/cakephp-brute-force-protection.svg?maxAge=2592000)](https://packagist.org/packages/ali1/cakephp-brute-force-protection)
[![Travis](https://img.shields.io/travis/ali1/cakephp-brute-force-protection.svg?maxAge=2592000)](https://travis-ci.org/ali1/cakephp-brute-force-protection)
[![Coverage Status](https://coveralls.io/repos/github/ali1/cakephp-brute-force-protection/badge.svg)](https://coveralls.io/github/ali1/cakephp-brute-force-protection)

A CakePHP plugin to assist with creating Json responses from controllers. 

### Features
* A component that quickly lets you set up ajax methods.

### Requirements

* Composer
* CakePHP 3.7+
* PHP 7.1+

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

```php
// UsersController.php
    public $components = ['JsonTools']; // can also be in AppController.php
    
    ...
    
    public function getUser()
    {
        /*
        will throw exception if not Json and Post/put request and also
        will prepare the variables that can be handled by RequestHandler
                    'error' => false,
                    'field_errors' => [],
                    'message' => 'OK',
                    '_redirect' => false,
                    'content' => null,
        */
        $this->Json->requireJsonSubmit();
        // ...
    }
```


### Many more features