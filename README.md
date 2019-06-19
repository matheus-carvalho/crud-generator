# crud-generator for Laravel
Generate a CRUD with Model, Controller, Routes and Views based on given Migration.

## Installing via Composer

```bash
composer require matheuscarvalho/crudgenerator
```

## Add the provider to config/app.php providers array

```php
'providers' => [
...
/*
 * Package Service Providers...
 */
Matheuscarvalho\Crudgenerator\Src\CrudGeneratorServiceProvider::class,
...
],
```
