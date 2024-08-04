<?php

require_once __DIR__ . '/../vendor/autoload.php';

use SimpleCms\Api\CmsApi;

$api = new CmsApi();
$api->handleRequest();