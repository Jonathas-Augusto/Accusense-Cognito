<?php

namespace Accusense\Cognito\Repositories;

use Aws\Result;
use Ellaisys\Cognito\AwsCognitoClient;
use Accusense\Cognito\Exceptions\CognitoException;
use Ellaisys\Cognito\Exceptions\InvalidTokenException;
use Aws\CognitoIdentityProvider\CognitoIdentityProviderClient;

class CognitoRepository
{
    private $client;

    public function __construct(AwsCognitoClient $client)
    {
        $this->client = $client;
    }

    public function confirmPassword($username, $password, $sessionToken)
    {
        return $this->client->confirmPassword($username, $password, $sessionToken);
    }

    public function resetPassword($code, $email, $password)
    {
        $response = $this->client->resetPassword($code, $email, $password);

        if ($response == 'passwords.token') {
            throw new CognitoException([
                'error' => 'Código de verificação ou email inválido',
                'code' => 'password.invalid.reset.code'
            ]);
        }

        return $response;
    }

    public function revokeToken($refreshToken)
    {
        $credentials = [
            'version'     => 'latest',
            'region'      => 'sa-east-1',
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ];
        $client = new CognitoIdentityProviderClient($credentials);

        $client->revokeToken([
            'ClientId' => env('AWS_COGNITO_CLIENT_ID'),
            'ClientSecret' => env('AWS_COGNITO_CLIENT_SECRET'),
            'Token' => $refreshToken
        ]);
    }

    public function refreshToken($refreshToken)
    {
        $credentials = [
            'version'     => 'latest',
            'region'      => 'sa-east-1',
            'credentials' => [
                'key'    => env('AWS_ACCESS_KEY_ID'),
                'secret' => env('AWS_SECRET_ACCESS_KEY'),
            ],
        ];
        $client = new CognitoIdentityProviderClient($credentials);
        $result = $client->AdminInitiateAuth([
            'AuthFlow' => 'REFRESH_TOKEN_AUTH',
            'AuthParameters' => [
                'REFRESH_TOKEN' => $refreshToken,
                'SECRET_HASH' => env('AWS_COGNITO_CLIENT_SECRET')
            ],
            'ClientId' => env('AWS_COGNITO_CLIENT_ID'),
            'UserPoolId' => env('AWS_COGNITO_USER_POOL_ID')
        ]);
        if (!$result instanceof Result) throw new InvalidTokenException();

        $result = $result->toArray()['AuthenticationResult'];
        $data = $client->getUser(['AccessToken' => $result['AccessToken']])->toArray();
        
        $claim = [
            'AccessToken' => $result['AccessToken'],
            'ExpiresIn' => $result['ExpiresIn'],
            'TokenType' => $result['TokenType'],
            'IdToken' => $result['IdToken'],
            'UserAttributes' => $data['UserAttributes']
        ];

        return $claim;
    }
}
