# Idiot

[![Total Downloads](https://poser.pugx.org/lornewang/idiot/downloads)](https://packagist.org/packages/lornewang/idiot)
[![Latest Stable Version](https://poser.pugx.org/lornewang/idiot/v/stable)](https://packagist.org/packages/lornewang/idiot)
[![Latest Unstable Version](https://poser.pugx.org/lornewang/idiot/v/unstable)](https://packagist.org/packages/lornewang/idiot)
[![License](https://poser.pugx.org/lornewang/idiot/license)](https://packagist.org/packages/lornewang/idiot)

Dubbo is a distributed service framework empowers applications with service import/export capability with high performance RPC.

This is only dubbo php clinet implementation. It's only support Hessian now.

You must start dubbo and zookeeper, register prividers first.

## Requirement

If you have not installed [zookeeper extension](http://pecl.php.net/package/zookeeper) for php, then

### MacOS

```bash
$ brew install php56-zookeeper
```

### Linux
```bash
$ sudo apt-get install php-pear php5-dev make  
$ sudo pecl install zookeeper
```  

Maybe occuring an error with "zookeeper support requires libzookeeper" when you install the zookeeper extension, you should install 
the libzookeeper needed, And add **zookeeper.so** to your **php.ini**

```bash
$ cd ${YOUR_ZOOKEEPER_HOME_DIR}/src/c/
$ ./configure && make && sudo make install
```

### Windows
```bash
Not currently supported, But you can through the source code compiled to a DLL
```


## Installation

Install the latest version with

```bash
$ composer require lornewang/idiot
```

## Usage

```php
<?php
use Idiot\Service;
use Idiot\Type;

$options = [
    "conn" => "127.0.0.1:2181",
    "path" => "com.alibaba.dubbo.service.user",
    "version" => "1.0.0"
];

$service = new Service($options);
$data = $service->invoke('getUserById', [951]);
```

Numerical parameters will get the suitable data type according to the numerical region, but this often is not accurate.Such as the remote service request is a **integer** type of data, if the passing **951** such argument, Client will automatically mapped to a **short** type, it for remote service have been a strong Type of service can lead to fatal exception, at this time we need client to use **Type Class** to explicitly pass the parameter value and data type (in fact this is the way we recommended, because automatic mapping is unreliable)

```php
$service->invoke("getUserById", [Type::int(951)]);
```

String, we can simply pass the string can be directly, also can use the Type Class of course explicitly pass

```php
$service->invoke("getUserByName", ["Lorne"]);

// you can also
$service->invoke("getUserByName", [Type::string("Lorne")]);
```

When we try to pass a Java object as a parameter, you need to define an object type

```php
Type::object("com.alibaba.dubbo.parameter.user", [
    "age": 20,
    "sex": "male"
]);
```

The following an example would be a complex remote invocation, we suggest that the numeric type (or even all of the data type)  at any time using Type Class wrapped the original data , it will be a good habit

```php
$service->invoke("complex", [
    Type::int(17263),
    Type::boolean(false),
    Type::string("male"),
    Type::double(16.25),
    Type::object("java.math.BigDecimal", 2367.299)
]);
```

Support the data wrapping type

```php
Type::short($value);
Type::int($value);
Type::integer($value);
Type::long($value);
Type::float($value);
Type::double($value);
Type::bool($value);
Type::boolean($value);
Type::string($value);
Type::object($class_name, $properties);
```

## License

Idiot is licensed under the MIT License - see the `LICENSE` file for details