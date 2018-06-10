<?php

//require_once __DIR__ . '../../vendor/autoload.php';


class VerifyAccessTokenMiddleware{
    protected $api_configs;
    public function __construct( $api_configs ) {
        $this->api_configs =  $api_configs;
    }
    public function __invoke($request, $response, $next)
    {
        $cookies = new \Slim\Http\Cookies($request->getCookieParams());
        $access_token = $cookies->get('access_token');
        $refresh_token = $cookies->get('refresh_token');

        if($access_token && $refresh_token){
            $is_logged_in = true;
            $api_args = $this->get_api_args($access_token);
        } else {
            $is_logged_in = false;
            $api_args = null;
        }

        $request = $request->withAttributes(array(
            'api_args' => $api_args,
            'is_logged_in' => $is_logged_in,
            'api_configs' => $this->api_configs,
            'access_token' => $access_token,
            'refresh_token' => $refresh_token,
        ));

        try {
            $response = $next($request, $response);
        } catch (\faxplus\ApiException $e){
            var_dump($e);
            if($e->getCode() == 401){
                try {
                    $access_token = $this->renew_access_token($refresh_token);
                    $request = $request->withAttribute('api_args', $this->get_api_args($access_token));
                    $response = $next($request, $response);
                    $response = $response->withAddedHeader("Set-Cookie", "access_token={$access_token}; Path=/; HttpOnly");
                } catch (\faxplus\ApiException $e) {
                    $response = $response->write($e->getResponseBody())->withStatus(401);
                    $response = $response->withAddedHeader("Set-Cookie", "access_token=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Path=/; HttpOnly");
                    $response = $response->withAddedHeader("Set-Cookie", "refresh_token=; Expires=Thu, 01 Jan 1970 00:00:01 GMT; Path=/; HttpOnly");
                }
            } else {
                $response->write($e->getResponseBody());
            }
        } catch (Exception $e){
            throw $e;
        }

        return $response;
    }

    public function get_api_args($access_token){
        // Configure OAuth2 access token for authorization: fax_oauth
        $config = \faxplus\Configuration::getDefaultConfiguration()->setAccessToken($access_token);
        $config->setHost($this->api_configs['resource_server_url']);
        $headerSelector = new \faxplus\HeaderSelector(array('x-fax-clientid' => $this->api_configs['client_id']));
        $api_args = array(
            'http_client' => new GuzzleHttp\Client(array('allow_redirects' => true)),
            'config' => $config,
            'header_selector' => $headerSelector
        );
        return $api_args;
    }

    public function renew_access_token($refresh_token){
        $client = new GuzzleHttp\Client(array("base_uri" => $this->api_configs['authorization_server_url'], "allow_redirects"=>true));
        $url = "/token?grant_type=refresh_token&refresh_token={$refresh_token}";
        try{
            $res = $client->post($url, array(
                'headers' => array("Authorization" => "Basic {$this->api_configs['client_basic_auth']}")
            ));
            $res = json_decode($res->getBody());
            return $res->access_token;
        } catch (\GuzzleHttp\Exception\ClientException $e) {
            throw new \faxplus\ApiException(
                "[{$e->getCode()}] {$e->getMessage()}",
                $e->getCode(),
                $e->getResponse() ? $e->getResponse()->getHeaders() : null,
                $e->getResponse() ? $e->getResponse()->getBody()->getContents() : null
            );
        }
    }
}

$app->add(new VerifyAccessTokenMiddleware($api_configs));
