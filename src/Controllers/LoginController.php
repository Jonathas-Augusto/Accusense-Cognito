<?php

namespace Accusense\Cognito\Controllers;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Accusense\Cognito\Actions\Action;
use Accusense\Cognito\Services\CognitoService;
use Illuminate\Validation\ValidationException;
use Accusense\Cognito\Exceptions\CognitoException;
use Accusense\Cognito\Actions\PushSectionGroupAction;
use Accusense\Cognito\Repositories\CognitoRepository;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Aws\CognitoIdentityProvider\Exception\CognitoIdentityProviderException;

class LoginController extends Controller
{
    private $cognitoService;
    private $action;
    private $cognitoRepository;

    public function __construct(CognitoService $cognitoService, Action $action, CognitoRepository $cognitoRepository)
    {
        $this->cognitoService = $cognitoService;
        $this->cognitoRepository = $cognitoRepository;
        $this->action = $action;
    }
    public function login(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $claim = $this->cognitoService->login($request->all());

            $this->action->dispatch(PushSectionGroupAction::class, $request->email, $claim['AccessToken']);

            return $claim;
        } catch (ModelNotFoundException $e) {
            return response()
                ->json(['error' => 'Usuário não encontrado ou inativo'], 401);
        } catch (ValidationException $validator) {
            return response()->json($validator->errors(), 422);
        } catch (CognitoException $exception) {
            return response()->json($exception->errors, $exception->status);
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    public function firstLogin(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
                'password' => 'required',
                'session_token' => 'required'
            ]);

            $this->cognitoRepository->confirmPassword($request->email, $request->password, $request->session_token);

            return $this->login($request);
        } catch (ValidationException $validator) {
            return response()->json($validator->errors(), 422);
        } catch (Exception $e) {
            return response()
                ->json(['error' => $e->getMessage()], 401);
        }
    }

    public function refresh(Request $request)
    {
        try {
            $request->validate(['refreshToken' => 'required']);
            
            $claim = $this->cognitoRepository->refreshToken($request->refreshToken);

            $email = current(array_filter($claim['UserAttributes'], function ($attribute) {
                return $attribute['Name'] === 'email';
            }))['Value'];

            $cacheData = [
                'token' => $claim['AccessToken'],
                'data' => $claim,
                'username' => $email
            ];

            $this->action->dispatch(SetSectionAction::class, $claim['AccessToken'], json_encode($cacheData), $claim['ExpiresIn']);
            $this->action->dispatch(PushSectionGroupAction::class, $email, $claim['AccessToken']);

            return response()
                ->json($cacheData);
            return response()->noContent();
        } catch (CognitoIdentityProviderException $e) {
            return response()
                ->json(['error' => $e->getAwsErrorMessage()], 400);
        }catch (ValidationException $validator) {
            return response()->json($validator->errors(), 422);
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    public function revoke(Request $request)
    {
        try {
            $request->validate(['refreshToken' => 'required']);
            
            $this->cognitoRepository->revokeToken($request->refreshToken);

            return response()->noContent();
        } catch (ValidationException $validator) {
            return response()->json($validator->errors(), 422);
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }

    }

    public function sendResetCode(Request $request)
    {
        try {
            $request->validate(['email' => 'required|email']);

            $this->cognitoService->sendResetCode($request);

            return response()
                ->json(['message' => 'Um código de redefinição de senha foi enviado por email']);
        } catch (CognitoException $exception) {
            return response()->json($exception->errors, $exception->status);
        } catch (ValidationException $validator) {
            return response()->json($validator->errors(), 422);
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }

    public function resetPassword(Request $request)
    {
        try {
            $request->validate([
                'code' => 'required',
                'email' => 'required|email',
                'password' => 'required'
            ]);

            $response = $this->cognitoRepository->resetPassword($request->code, $request->email, $request->password);

            if ($response === 'password.reset') {
                return $this->login($request);
            }

            return response()
                ->json(['error' => 'Não foi possível atualizar sua senha, contate o administrador do sistema'], 400);
        } catch (CognitoException $exception) {
            return response()->json($exception->errors, $exception->status);
        } catch (ValidationException $validator) {
            return response()->json($validator->errors(), 422);
        } catch (Exception $exception) {
            return response()->json(['error' => $exception->getMessage()], 400);
        }
    }
}
