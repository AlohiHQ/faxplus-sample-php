<?php

$api_configs = array (
    'client_id' => '',
    'client_secret' => '',
    'redirect_uri' => 'http://localhost:8080/cb/',
    'client_basic_auth' => '',
    'authorization_server_url' => 'https://accounts.fax.plus',
    'resource_server_url' => 'https://restapi.fax.plus/v1'
);
$api_configs['client_basic_auth'] = base64_encode($api_configs['client_id'] . ':' . $api_configs['client_secret']);

return $api_configs;
