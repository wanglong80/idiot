# Idiot

[![Total Downloads](https://poser.pugx.org/lornewang/idiot/downloads)](https://packagist.org/packages/lornewang/idiot)
[![Latest Stable Version](https://poser.pugx.org/lornewang/idiot/v/stable)](https://packagist.org/packages/lornewang/idiot)
[![Latest Unstable Version](https://poser.pugx.org/lornewang/idiot/v/unstable)](https://packagist.org/packages/lornewang/idiot)
[![License](https://poser.pugx.org/lornewang/idiot/license)](https://packagist.org/packages/lornewang/idiot)

Dubbo is a distributed service framework empowers applications with service import/export capability with high performance RPC.

This is only dubbo php clinet implementation. It's only support Hessian now.

You must start dubbo and zookeeper, register prividers first.

-------------------------------------------------

## Installation

If you have not installed [zookeeper extension](http://pecl.php.net/package/zookeeper) for php, then

```bash
sudo apt-get install php-pear php5-dev make  
sudo pecl install zookeeper
```  

Maybe occuring an error with "zookeeper support requires libzookeeper" when you install the zookeeper extension, you should install 
the libzookeeper needed, And add ```zookeeper.so``` to your ```php.ini```

```bash
cd ${YOUR_ZOOKEEPER_HOME_DIR}/src/c/
./configure
make
sudo make install
```

Install the latest version with

```bash
$ composer require lornewang/idiot
```

## Usage

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

## License

Idiot is licensed under the MIT License - see the `LICENSE` file for details