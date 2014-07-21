<?php

/**
 * This file is part of the authbucket/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

use Silex\Application;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Client;

// Demo, index.
$app->get('/demo', function (Request $request) use ($app) {
    return $app['twig']->render('demo/index.html.twig');
})->bind('demo_index');

// Demo, authorize, authorization code grant.
$app->get('/demo/authorize/code', function (Request $request, Application $app) {
    if (!$app['session']->isStarted()) {
        $app['session']->start();
    }

    $scopeManager = $app['authbucket_oauth2.model_manager.factory']->getModelManager('scope');
    $scope = $scopeManager->createScope()
        ->setScope(substr(md5(uniqid(null, true)), 0, 8));
    $scopeManager->updateScope($scope);

    $parameters = array(
        'response_type' => 'code',
        'client_id' => 'authorization_code_grant',
        'redirect_uri' => $request->getUriForPath('/demo/response_type/code'),
        'scope' => 'demoscope1 ' . $scope->getScope(),
        'state' => $app['session']->getId(),
    );

    $url = Request::create($request->getUriForPath('/oauth2/authorize'), 'GET', $parameters)->getUri();

    return $app->redirect($url);
})->bind('demo_authorize_code');

// Demo, authorize, implicit grant.
$app->get('/demo/authorize/token', function (Request $request, Application $app) {
    if (!$app['session']->isStarted()) {
        $app['session']->start();
    }

    $scopeManager = $app['authbucket_oauth2.model_manager.factory']->getModelManager('scope');
    $scope = $scopeManager->createScope()
        ->setScope(substr(md5(uniqid(null, true)), 0, 8));
    $scopeManager->updateScope($scope);

    $parameters = array(
        'response_type' => 'token',
        'client_id' => 'implicit_grant',
        'redirect_uri' => $request->getUriForPath('/demo/response_type/token'),
        'scope' => 'demoscope1 ' . $scope->getScope(),
        'state' => $app['session']->getId(),
    );

    $url = Request::create($request->getUriForPath('/oauth2/authorize'), 'GET', $parameters)->getUri();

    return $app->redirect($url);
})->bind('demo_authorize_token');

// Demo, authorization endpoint, authorization code grant.
$app->get('/demo/response_type/code', function (Request $request, Application $app) {
    $authorizationResponse = $request->query->all();

    $tokenPath = $app['url_generator']->generate('demo_grant_type_authorization_code', array(
        'code' => $authorizationResponse['code'],
        'state' => $authorizationResponse['state'],
    ));

    return $app['twig']->render('demo/response_type/code.html.twig', array(
        'authorization_response' => $authorizationResponse,
        'token_path' => $tokenPath,
    ));
})->bind('demo_response_type_code');

// Demo, authorization endpoint, implicit grant.
$app->get('/demo/response_type/token', function (Request $request, Application $app) {
    $accessTokenResponse = $request->query->all();

    $modelPath = $app['url_generator']->generate('demo_resource_type_model', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));
    $debugPath = $app['url_generator']->generate('demo_resource_type_debug_endpoint', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));

    return $app['twig']->render('demo/response_type/token.html.twig', array(
        'access_token_response' => $accessTokenResponse,
        'model_path' => $modelPath,
        'debug_path' => $debugPath,
    ));
})->bind('demo_response_type_token');

// Demo, token endpoint, authorization code grant.
$app->get('/demo/grant_type/authorization_code', function (Request $request, Application $app) {
    $parameters = array(
        'grant_type' => 'authorization_code',
        'code' => $request->query->get('code'),
        'redirect_uri' => $request->getUriForPath('/demo/response_type/code'),
        'client_id' => 'authorization_code_grant',
        'client_secret' => 'uoce8AeP',
        'state' => $request->query->get('state'),
    );
    $server = array();
    $client = new Client($app);
    $crawler = $client->request('POST', '/oauth2/token', $parameters, array(), $server);
    $accessTokenResponse = json_decode($client->getResponse()->getContent(), true);
    $accessTokenRequest = get_object_vars($client->getRequest());

    $modelPath = $app['url_generator']->generate('demo_resource_type_model', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));
    $debugPath = $app['url_generator']->generate('demo_resource_type_debug_endpoint', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));
    $refreshPath = $app['url_generator']->generate('demo_grant_type_refresh_token', array(
        'username' => 'authorization_code_grant',
        'password' => 'uoce8AeP',
        'refresh_token' => $accessTokenResponse['refresh_token'],
    ));

    return $app['twig']->render('demo/grant_type/authorization_code.html.twig', array(
        'access_token_response' => $accessTokenResponse,
        'access_token_request' => $accessTokenRequest,
        'model_path' => $modelPath,
        'debug_path' => $debugPath,
        'refresh_path' => $refreshPath,
    ));
})->bind('demo_grant_type_authorization_code');

// Demo, token endpoint, resource owner password credentials grant.
$app->get('/demo/grant_type/password', function (Request $request, Application $app) {
    if (!$app['session']->isStarted()) {
        $app['session']->start();
    }

    $parameters = array(
        'grant_type' => 'password',
        'username' => 'demousername1',
        'password' => 'demopassword1',
        'scope' => 'demoscope1',
        'state' => $app['session']->getId(),
    );
    $server = array(
        'PHP_AUTH_USER' => 'resource_owner_password_credentials_grant',
        'PHP_AUTH_PW' => 'Eevahph6',
    );
    $client = new Client($app);
    $crawler = $client->request('POST', '/oauth2/token', $parameters, array(), $server);
    $accessTokenResponse = json_decode($client->getResponse()->getContent(), true);
    $accessTokenRequest = get_object_vars($client->getRequest());

    $modelPath = $app['url_generator']->generate('demo_resource_type_model', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));
    $debugPath = $app['url_generator']->generate('demo_resource_type_debug_endpoint', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));
    $refreshPath = $app['url_generator']->generate('demo_grant_type_refresh_token', array(
        'username' => 'resource_owner_password_credentials_grant',
        'password' => 'Eevahph6',
        'refresh_token' => $accessTokenResponse['refresh_token'],
    ));

    return $app['twig']->render('demo/grant_type/password.html.twig', array(
        'access_token_response' => $accessTokenResponse,
        'access_token_request' => $accessTokenRequest,
        'model_path' => $modelPath,
        'debug_path' => $debugPath,
        'refresh_path' => $refreshPath,
    ));
})->bind('demo_grant_type_password');

// Demo, token endpoint, client credentials grant.
$app->get('/demo/grant_type/client_credentials', function (Request $request, Application $app) {
    $parameters = array(
        'grant_type' => 'client_credentials',
        'scope' => 'demoscope1',
    );
    $server = array(
        'PHP_AUTH_USER' => 'client_credentials_grant',
        'PHP_AUTH_PW' => 'yib6aiFe',
    );
    $client = new Client($app);
    $crawler = $client->request('POST', '/oauth2/token', $parameters, array(), $server);
    $accessTokenResponse = json_decode($client->getResponse()->getContent(), true);
    $accessTokenRequest = get_object_vars($client->getRequest());

    $modelPath = $app['url_generator']->generate('demo_resource_type_model', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));
    $debugPath = $app['url_generator']->generate('demo_resource_type_debug_endpoint', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));
    $refreshPath = $app['url_generator']->generate('demo_grant_type_refresh_token', array(
        'username' => 'client_credentials_grant',
        'password' => 'yib6aiFe',
        'refresh_token' => $accessTokenResponse['refresh_token'],
    ));

    return $app['twig']->render('demo/grant_type/client_credentials.html.twig', array(
        'access_token_response' => $accessTokenResponse,
        'access_token_request' => $accessTokenRequest,
        'model_path' => $modelPath,
        'debug_path' => $debugPath,
        'refresh_path' => $refreshPath,
    ));
})->bind('demo_grant_type_client_credentials');

// Demo, token endpoint, refresh token grant.
$app->get('/demo/grant_type/refresh_token', function (Request $request, Application $app) {
    $parameters = array(
        'grant_type' => 'refresh_token',
        'refresh_token' => $request->query->get('refresh_token'),
    );
    $server = array(
        'PHP_AUTH_USER' => $request->query->get('username'),
        'PHP_AUTH_PW' => $request->query->get('password'),
    );
    $client = new Client($app);
    $crawler = $client->request('POST', '/oauth2/token', $parameters, array(), $server);
    $accessTokenResponse = json_decode($client->getResponse()->getContent(), true);
    $accessTokenRequest = get_object_vars($client->getRequest());

    $modelPath = $app['url_generator']->generate('demo_resource_type_model', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));
    $debugPath = $app['url_generator']->generate('demo_resource_type_debug_endpoint', array(
        'access_token' => $accessTokenResponse['access_token'],
    ));
    $refreshPath = $app['url_generator']->generate('demo_grant_type_refresh_token', array(
        'username' => $request->query->get('username'),
        'password' => $request->query->get('password'),
        'refresh_token' => $accessTokenResponse['refresh_token'],
    ));

    return $app['twig']->render('demo/grant_type/refresh_token.html.twig', array(
        'access_token_response' => $accessTokenResponse,
        'access_token_request' => $accessTokenRequest,
        'model_path' => $modelPath,
        'debug_path' => $debugPath,
        'refresh_path' => $refreshPath,
    ));
})->bind('demo_grant_type_refresh_token');

// Demo, resource endpoint, model.
$app->get('/demo/resource_type/model', function (Request $request, Application $app) {
    $parameters = array(
        'debug_token' => $request->query->get('access_token'),
    );
    $server = array(
        'HTTP_Authorization' => implode(' ', array('Bearer', $request->query->get('access_token'))),
    );
    $client = new Client($app);
    $crawler = $client->request('GET', '/resource/resource_type/model', $parameters, array(), $server);
    $resourceResponse = json_decode($client->getResponse()->getContent(), true);
    $resourceRequest = get_object_vars($client->getRequest());

    return $app['twig']->render('demo/resource_type/model.html.twig', array(
        'resource_response' => $resourceResponse,
        'resource_request' => $resourceRequest,
    ));
})->bind('demo_resource_type_model');

// Demo, resource endpoint.
$app->get('/demo/resource_type/debug_endpoint', function (Request $request, Application $app) {
    $parameters = array(
        'debug_token' => $request->query->get('access_token'),
    );
    $server = array(
        'HTTP_Authorization' => implode(' ', array('Bearer', $request->query->get('access_token'))),
    );
    $client = new Client($app);
    $crawler = $client->request('GET', '/resource/resource_type/debug_endpoint', $parameters, array(), $server);
    $resourceResponse = json_decode($client->getResponse()->getContent(), true);
    $resourceRequest = get_object_vars($client->getRequest());

    return $app['twig']->render('demo/resource_type/debug_endpoint.html.twig', array(
        'resource_response' => $resourceResponse,
        'resource_request' => $resourceRequest,
    ));
})->bind('demo_resource_type_debug_endpoint');