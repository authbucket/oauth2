<?php

/**
 * This file is part of the authbucket/oauth2 package.
 *
 * (c) Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace AuthBucket\OAuth2\ResourceType;

use AuthBucket\OAuth2\Exception\InvalidRequestException;
use AuthBucket\OAuth2\Exception\ServerErrorException;
use AuthBucket\OAuth2\Model\ModelManagerFactoryInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Client;
use Symfony\Component\HttpKernel\HttpKernelInterface;

/**
 * Token response type implementation.
 *
 * @author Wong Hoi Sing Edison <hswong3i@pantarei-design.com>
 */
class DebugEndpointResourceTypeHandler extends AbstractResourceTypeHandler
{
    public function handle(
        HttpKernelInterface $httpKernel,
        ModelManagerFactoryInterface $modelManagerFactory,
        $accessToken,
        array $options = array()
    )
    {
        $options = array_merge(array(
            'token_path' => '',
            'debug_path' => '',
            'client_id' => '',
            'client_secret' => '',
            'cache' => true,
        ), $options);

        // Both options are required.
        if (!$options['token_path']
            || !$options['debug_path']
            || !$options['client_id']
            || !$options['client_secret']
        ) {
            throw new ServerErrorException(array(
                'error_description' => 'The authorization server encountered an unexpected condition that prevented it from fulfilling the request.',
            ));
        }

        $accessTokenManager = $modelManagerFactory->getModelManager('access_token');

        // Get cached access_token and return if exists.
        if ($options['cache']) {
            $stored = $accessTokenManager->readModelOneBy(array(
                'accessToken' => $accessToken,
            ));
            if ($stored !== null && $stored->getExpires() > new \DateTime()) {
                return $stored;
            }
        }

        // Get client credentials grant-ed access token for resource server.
        $parameters = array(
            'grant_type' => 'client_credentials',
            'scope' => 'debug',
        );
        $server = array(
            'PHP_AUTH_USER' => $options['client_id'],
            'PHP_AUTH_PW' => $options['client_secret'],
        );
        $client = new Client($httpKernel);
        $crawler = $client->request('POST', $options['token_path'], $parameters, array(), $server);
        $tokenResponse = json_decode($client->getResponse()->getContent(), true);

        // Throw exception if error return.
        if (isset($tokenResponse['error'])) {
            throw new ServerErrorException(array(
                'error_description' => 'The authorization server encountered an unexpected condition that prevented it from fulfilling the request.',
            ));
        }

        // Fetch meta data of supplied access token by query debug endpoint.
        $parameters = array(
            'debug_token' => $accessToken,
        );
        $server = array(
            'HTTP_Authorization' => implode(' ', array('Bearer', $tokenResponse['access_token'])),
        );
        $client = new Client($httpKernel);
        $crawler = $client->request('GET', $options['debug_path'], $parameters, array(), $server);
        $debugResponse = json_decode($client->getResponse()->getContent(), true);

        // Throw exception if error return.
        if (isset($debugResponse['error'])) {
            throw new InvalidRequestException(array(
                'error_description' => 'The request includes an invalid parameter value.',
            ));
        }

        // Create a new access token with fetched meta data.
        $stored = $accessTokenManager->createModel(array(
            'accessToken' => $debugResponse['access_token'],
            'tokenType' => $debugResponse['token_type'],
            'clientId' => $debugResponse['client_id'],
            'username' => $debugResponse['username'],
            'expires' => new \DateTime('@' . $debugResponse['expires']),
            'scope' => $debugResponse['scope'],
        ));

        return $stored;
    }
}
