Duo Universal PHP library

To install
```
composer install
```

To run interactive mode
```
php -a -d auto_prepend_file=vendor/autoload.php
```

To run tests
```
./vendor/bin/phpunit --process-isolation tests

```
To run linter
```
./vendor/bin/phpcs --standard=.duo_linting.xml -n src/* tests
```
