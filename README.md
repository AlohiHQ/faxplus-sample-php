# FAX.PLUS PHP SDK Sample Application

This is a sample web application that uses [FAX.PLUS PHP SDK](https://github.com/alohi/faxplus-php). This app is intended to get you started using the PHP SDK.


## Requirements

PHP 7 (SDK itself works on php 5.5 and later but sample app is only tested on php 7)


## Installation

- Clone this repo and from command line go to root of repo
- Install [Composer](http://getcomposer.org/)
- Run `php composer.phar install` to install dependencies
- Run `cp src/sample.config.php src/config.php` and edit `src/config.php` and enter your client_id and client_secret
- go to your FAX.PLUS account and add `http://localhost:8080/cb/` to your redirect urls 
- Now run following commands to start the server 

```bash
cd public/
php -S localhost:8080 index.php
```

- Now you're able to see this demo app at [http://localhost:8080](http://localhost:8080)
