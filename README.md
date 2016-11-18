Idiot
==============================

[![Total Downloads](https://poser.pugx.org/lornewang/idiot/downloads)](https://packagist.org/packages/lornewang/idiot)
[![Latest Stable Version](https://poser.pugx.org/lornewang/idiot/v/stable)](https://packagist.org/packages/lornewang/idiot)
[![Latest Unstable Version](https://poser.pugx.org/lornewang/idiot/v/unstable)](https://packagist.org/packages/lornewang/idiot)
[![License](https://poser.pugx.org/lornewang/idiot/license)](https://packagist.org/packages/lornewang/idiot)


Installation
------------
Install the latest version with

```bash
$ composer require lornewang/idiot
```

Usage
-----

```php
<?php
use Idiot\Service;

$options = [
    'conn' => '127.0.0.1:2181',
    'path' => 'com.alibaba.dubbo.service.user',
    'version' => '1.0.0'
];

$service = new Service($options);
$data = $service->invoke('getUserById', [748951]);

```

License
-------

Sapphire is licensed under the MIT License - see the `LICENSE` file for details