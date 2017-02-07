<?php
/**
 * Created by PhpStorm.
 * User: yuyi
 * Date: 17/2/7
 * Time: 13:45
 */
include __DIR__."/../vendor/autoload.php";
$git = new \Wing\Git\Git( "/Users/yuyi/Web/xiaoan/wing/src/Git/tests" );
$git->addExcludePath([
    "vendor/*"
]);
$git->addExcludeFileName([
    "composer"
]);


var_dump( $git->analysis() );