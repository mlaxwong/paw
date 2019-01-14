<?php
// PHP version
if (PHP_VERSION_ID < 70000) exit('Requires PHP 7.0 or later.');

// mbstart
if (!extension_loaded('mbstring') || (extension_loaded('mbstring') && ini_get('mbstring.func_overload') != 0)) 
{
    exit('Requires the <a href="http://php.net/manual/en/book.mbstring.php" rel="noopener" target="_blank">PHP multibyte string</a> extension in order to run. Please talk to your host/IT department about enabling it on your server.');
}

// constant
foreach (['PATH_BASE', 'PATH_VENDOR'] as $requiredConstant) 
{
    if (!defined($requiredConstant)) exit(strtr("Must declare constant '{{constant}}'", ['{{constant}}' => $requiredConstant]));
    // free up memory
    $requiredConstant = null;
    unset($requiredConstant);
}