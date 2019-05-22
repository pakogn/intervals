<h1 align="center">Intervals Manager</h1>
<p align="center">A simple solution for managing intervals.</p>

[Live Demo](http://intervals.garcianoriega.com).

This is a proposal to manage intervals using a PHP application without frameworks and just taking advantage of vanilla PHP and the next composer packages:

* illuminate/database
* phpunit/phpunit
* vlucas/phpdotenv
* vlucas/valitron

This project is mainly focused on solve the intervals managing, so a robust architecture or a good routing or MVC may not be found in this solution. This result in a very simple interaction with HTTP verbs, redirects and simple PHP code.

## Requirements

- PHP >= 7.1.3
- PDO PHP Extension

## Installation

1. We need to clone the repo and enter to the repo directory.
```
git clone https://github.com/pakogn/intervals.git
cd intervals
```
2. We may install the composer packages:
```
composer install
```
3. Now, We need to copy .env.example to .env to store our environment configuration.
```
cp .env.example .env
```
4. We must describe here the database connection information.
```
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=intervals
DB_USERNAME=user
DB_PASSWORD=secret
```
5. We may install the schema with the included installation script or take advantage of the included [dump file](https://github.com/pakogn/intervals/blob/master/_docs/database/intervals.sql) that is in the _docs folder.
```
php install/index.php
```
6. Finally, We need to enter to the public folder and if We feel comfortable We may use the built-in PHP web server to publish the application.
```
cd public
php -S localhost:8000
```
Now We may visit [localhost:8000](http://localhost:8000) to interact with the application.

*Note. It's recommended to run the web server in a port We know is not in use. We propose 8000 because is not commonly used.*
