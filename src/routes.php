<?php

use Slim\Http\Request;
use Slim\Http\Response;

// Routes

// INDEX
$app->get('/', function (Request $request, Response $response, array $args) {
    $api_configs = $request->getAttribute("api_configs");
    $is_logged_in = $request->getAttribute("is_logged_in");
    $vars = array(
        'is_logged_in' => $is_logged_in,
        'login_url' => "${api_configs['authorization_server_url']}/login?response_type=code&".
            "client_id=${api_configs['client_id']}&redirect_uri=${api_configs['redirect_uri']}&scope=all");

    // Render index view
    return $this->renderer->render($response, 'index.phtml', $vars);
});


// CALLBACK FROM LOGIN
$app->get('/cb/', function (Request $request, Response $response, array $args) {
    $api_configs = $request->getAttribute("api_configs");
    $url = "/token?grant_type=authorization_code&client_id=${api_configs['client_id']}";
    $token = $request->getQueryParam('code');
    $client = new GuzzleHttp\Client(array("base_uri" => $api_configs['authorization_server_url'], "allow_redirects"=>true));
    $res = $client->post($url, array(
        'headers' => array(
            "Authorization" => "Basic ${api_configs['client_basic_auth']}",
            "Cache-Control" => "no-cache",
            "Content-Type" => "application/x-www-form-urlencoded"
        ),
        "form_params" => array(
            'code' => $token,
            'client_id' => $api_configs['client_id'],
            'client_secret'=> $api_configs['client_secret'],
            'redirect_uri'=> $api_configs['redirect_uri'],
            'grant_type'=> 'authorization_code',
        )
    ));
    $res = json_decode($res->getBody());
    if($res->error) {
        $response = $response->withStatus(400);
        $response = $response->write(json_encode($res));
    } else {
        $response = $response->withAddedHeader("Set-Cookie", "access_token={$res->access_token}; Path=/; HttpOnly");
        $response = $response->withAddedHeader("Set-Cookie", "refresh_token={$res->refresh_token}; Path=/; HttpOnly");
        $response = $response->withRedirect('/');
    }
    return $response;
});


// ACCOUNTS REQUESTS
$app->get('/accounts', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\AccountsApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $uid = $request->getQueryParam('resource_id');
    if($uid == 'all'){
        $result = $client->getAccounts();
    } else {
        $result = $client->getUser($uid);
    }
    $response->write($result);
    return $response;
});
$app->put('/accounts', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\AccountsApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $uid = $request->getQueryParam('resource_id');
    $payload = new \faxplus\model\Account(array(
        'name' => $request->getParsedBodyParam("name"),
        'lastname' => $request->getParsedBodyParam("lastname")
    ));
    $client->updateUser($uid, $payload);
    $response->write(json_encode(array('result' => 'Account updated successfully')));
    return $response;
});


// MEMBER DETAILS REQUESTS
$app->get('/members', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\AccountsApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $uid = $request->getQueryParam('resource_id');
    $result = $client->getMemberDetails($uid);
    $response->write($result);
    return $response;
});
$app->put('/members', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\AccountsApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $uid = $request->getQueryParam('resource_id');
    $payload = new \faxplus\model\MemberDetail(array(
        'quota' => (integer)$request->getParsedBodyParam("quota"),
        'role' => $request->getParsedBodyParam("role"),
    ));
    $client->updateMemberDetails($uid, $payload);
    $response->write(json_encode(array('result' => 'Member detail updated successfully')));
    return $response;
});


// NUMBERS REQUESTS
$app->get('/numbers', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\NumbersApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $number = $request->getQueryParam('resource_id');
    if($number){
        $result = $client->getNumber($number);
    } else {
        $result = $client->listNumbers();
    }
    $response->write($result);
    return $response;
});
$app->put('/numbers', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\NumbersApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $number = $request->getQueryParam('resource_id');
    $payload = new \faxplus\model\PayloadNumberModification(array(
        'assigned_to' => $request->getParsedBodyParam("memberid"),
    ));
    $client->updateNumber($number, $payload);
    $response->write(json_encode(array('result' => 'Number assigned successfully')));
    return $response;
});
$app->delete('/numbers', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\NumbersApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $number = $request->getQueryParam('resource_id');
    $client->revokeNumber($number);
    $response->write(json_encode(array('result' => 'Number revoked successfully')));
    return $response;
});


// ARCHIVES REQUESTS
$app->get('/archives', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\ArchivesApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $category = $request->getQueryParam('category');
    $fax_id = $request->getQueryParam('resource_id');
    if($fax_id){
        $result = $client->getFax($fax_id);
    } else {
        $result = $client->listFaxes('self', $category);
    }
    $response->write($result);
    return $response;
});
$app->put('/archives', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\ArchivesApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $fax_id = $request->getQueryParam('resource_id');
    $payload = new \faxplus\model\PayloadFaxModification(array(
        'comment' => $request->getParsedBodyParam("comment"),
        'is_read' => $request->getParsedBodyParam("read") == 'true',
    ));
    $client->updateFax($fax_id, $payload);
    $response->write(json_encode(array('result' => 'Fax updated successfully')));
    return $response;
});
$app->delete('/archives', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\ArchivesApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $fax_id = $request->getQueryParam('resource_id');
    $client->deleteFax($fax_id);
    $response->write(json_encode(array('result' => 'Fax deleted successfully')));
    return $response;
});


// FILES REQUESTS
$app->get('/files', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\FilesApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $fax_id = $request->getQueryParam('resource_id');
    $mime_type = 'application/pdf';
    $file_type = explode('/', $mime_type)[1];

    $result = $client->getFile($fax_id);
    $file_path = $result->getRealPath();

    $response = $response->withHeader('Content-Type', 'Content-Description', 'File Transfer')
        ->withHeader('Content-Type', $mime_type)
        ->withHeader('Content-Description', 'File Transfer')
        ->withHeader('Content-Transfer-Encoding', 'binary')
        ->withHeader('Content-Disposition', 'attachment; filename="' . "{$fax_id}.{$file_type}" . '"')
        ->withHeader('Expires', '0')
        ->withHeader('Cache-Control', 'must-revalidate, post-check=0, pre-check=0')
        ->withHeader('Pragma', 'public');
    readfile($file_path);
    return $response;
});
$app->post('/files', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\FilesApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $uploaded_file = $request->getUploadedFiles()['fax_file'];
    $mime_type = $uploaded_file->getClientMediaType();
    $file_type = explode('/', $mime_type)[1];
    $new_path = "{$uploaded_file->file}.{$file_type}";
    $uploaded_file->moveTo($new_path);

    try{
        $result = $client->uploadFile($new_path, $file_type);
    } finally {
        unlink($new_path);
    }
    $response->write($result);
    return $response;
});


// OUTBOX REQUESTS
$app->get('/outbox', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\OutboxApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $category = $request->getQueryParam('category');
    $fax_id = $request->getQueryParam('resource_id');
    if($fax_id){
        $result = $client->getOutboxFax($fax_id);
    } else {
        $result = $client->listOutboxFaxes();
    }
    $response->write($result);
    return $response;
});
$app->post('/outbox', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\OutboxApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $payload = new \faxplus\model\PayloadOutbox(array(
            'to' => array($request->getParsedBodyParam("to")),
            'from' => $request->getParsedBodyParam("from"),
            'files' => array($request->getParsedBodyParam("fax-file")),
            'options' => array(
                "retry" => array(
                    "delay" => 0,
                    "count" => 0,
                ),
                "enhancement" => True
            )
        )
    );
    $client->sendFax($payload);
    $response->write(json_encode(array('result' => 'Outbox fax created successfully')));
    return $response;
});
$app->put('/outbox', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\OutboxApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $fax_id = $request->getQueryParam('resource_id');
    $payload = new \faxplus\model\PayloadOutboxModification(array(
        'comment' => $request->getParsedBodyParam("comment"),
    ));
    $client->updateOutboxFax($fax_id, $payload);
    $response->write(json_encode(array('result' => 'Outbox fax updated successfully')));
    return $response;
});
$app->delete('/outbox', function (Request $request, Response $response, array $args) {
    $api_args = $request->getAttribute('api_args');
    $client = new \faxplus\api\OutboxApi($api_args['http_client'], $api_args['config'], $api_args['header_selector']);
    $fax_id = $request->getQueryParam('resource_id');
    $client->deleteOutboxFax($fax_id);
    $response->write(json_encode(array('result' => 'Outbox fax deleted successfully')));
    return $response;
});
