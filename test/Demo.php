<?php
require_once("../vendor/autoload.php");

use Idiot\Service;
use Idiot\Type;
use com\xintiaotime\thrift\demo;

//use Thrift\;


$options = [
	'conn' => "139.199.23.76:2181",
	"path" => "com.xintiaotime.thrift.demo.UserApi",
	"version"=>"1.0.0",
	'protocol' => 'thrift',
	'host' => '139.199.23.76',
	'port'=>'40880'
];

$service = new Service($options);
$data = $service->invoke('getUserName', [111]);
