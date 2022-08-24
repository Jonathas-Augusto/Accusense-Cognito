<?php

namespace Accusense\Cognito\Services;

use Illuminate\Http\Request;
use Ellaisys\Cognito\AwsCognitoClaim;
use Ellaisys\Cognito\Auth\ChangePasswords;
use Ellaisys\Cognito\Auth\AuthenticatesUsers;
use Accusense\Cognito\Exceptions\CognitoException;
use Ellaisys\Cognito\Auth\SendsPasswordResetEmails;
use Accusense\Cognito\Repositories\CognitoRepository;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class CognitoService
{
    const LIMIT_RESET_PASSWORD = "LimitExceededException";

    use AuthenticatesUsers;
    use ChangePasswords;
    use SendsPasswordResetEmails;

    private $repository;

    public function __construct(CognitoRepository $repository)
    {
        $this->repository = $repository;
    }

    public function login(array $collection, string $paramUserName = 'email', string $paramPassword = 'password')
    {
        $claim = $this->attemptLogin(collect($collection), 'api', $paramUserName, $paramPassword, true);

        if (!$claim instanceof AwsCognitoClaim) {
            // primeiro login o usuário precisa resetar a senha
            if (is_array($claim) && isset($claim['session_token']) && isset($claim['status']) && $claim['status'] === 'NEW_PASSWORD_REQUIRED') {
                throw new CognitoException(['code' => 'force.change.password', 'session_token' => $claim['session_token']]);
            }

            if ($claim->original['error'] === 'cognito.validation.auth.failed') {
                throw new CognitoException(['error' => 'Usuário não cadastrado ou inativo', 'code' => 'invalid.user']);
            }
            if ($claim->original['error'] === 'cognito.validation.auth.user_unauthorized') {
                throw new CognitoException(['error' => 'Senha Inválida', 'code' => 'invalid.password']);
            }

            throw new CognitoException($claim->original);
        }

        return $claim->getData();
    }

    public function sendResetCode(Request $request,string $usernameKey = 'email', bool $resetTypeCode = true, bool $isJsonResponse = true)
    {
        $response = $this->sendResetLinkEmail($request, $usernameKey, $resetTypeCode, $isJsonResponse);

        if ($response === false) {
            throw new CognitoException([
                'error' => 'Usuário não existe ou Limite de tentativas excedido, tente novamente mais tarde ou contate um administrador',
                'code' => 'password.resets'
            ]);
        }
        return $response;
    }

    public function resetPassword($code, $email, $password)
    {
        try {
            $response = $this->repository->resetPassword($code, $email, $password);

            if ($response == 'passwords.token') {
                throw new CognitoException([
                    'error' => 'Código de verificação ou email inválido',
                    'code' => 'password.invalid.reset.code'
                ]);
            }

            return $response;
        } catch (CognitoIdentityProviderException $e) {
            if($e->getAwsErrorCode() == self::LIMIT_RESET_PASSWORD) {
                throw new CognitoException([
                    'error' => 'Limite de tentativas para resetar a senha excedido',
                    'code' => 'password.reset.limit'
                ]);
            }
            throw $e;
        }
    }
}
