<?php
// define('SHIELDFY_PACKAGE_ROOT',realpath(__DIR__.'/..'));
// define('SHIELDFY_DATA_DIR',SHIELDFY_PACKAGE_ROOT.'/Data');

// //ping shieldfy server
// $data = [
//     'host' => $this->request->server['HTTP_HOST'],
//     'https' => $this->request->isSecure(),
//     'lang' => 'php',
//     'sdk_version' => $this->config['version'],
//     'server' => isset($this->request->server['SERVER_SOFTWARE']) ? $this->request->server['SERVER_SOFTWARE'] : 'NA',
//     'php_version' => PHP_VERSION,
//     'sapi_type' => php_sapi_name(),
//     'os_info' => php_uname(),
//     'disabled_functions' => ini_get('disable_functions') ?: 'NA',
//     'loaded_extensions' => implode(',', get_loaded_extensions()),
//     'display_errors' => ini_get('display_errors')
// ];