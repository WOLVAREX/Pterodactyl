<?php

namespace Pterodactyl\Http\Controllers\Auth;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Validator;
use Pterodactyl\Rules\Username;
use Pterodactyl\Exceptions\DisplayException;
use Pterodactyl\Services\Users\UserCreationService;

class RegisterController extends AbstractLoginController
{
    public function __construct(private UserCreationService $creationService)
    {
        parent::__construct();
    }

    public function index(): View
    {
        return view('templates/auth.core');
    }

    /**
     * @throws \Pterodactyl\Exceptions\DisplayException
     * @throws \Illuminate\Validation\ValidationException
     */
    public function register(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email:strict|between:1,191|unique:users,email',
            'username' => ['required', 'between:1,191', 'unique:users,username', new Username()],
            'name_first' => 'required|string|between:1,191',
            'name_last' => 'required|string|between:1,191',
            'password' => 'required|string|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            throw new DisplayException($validator->errors()->first());
        }

        $data = $validator->validated();
        unset($data['password_confirmation']);

        // root_admin defaults to false on the User model, so every
        // self-registered account is a standard (non-admin) user.
        $user = $this->creationService->handle($data);

        return $this->sendLoginResponse($user, $request);
    }
}
