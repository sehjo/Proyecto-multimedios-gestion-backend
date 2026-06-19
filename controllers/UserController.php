<?php

class UserController
{
    public function index(Request $request): void
    {
        $params = Paginator::params($request, 15);
        $users = UserRepository::paginate($params['offset'], $params['perPage']);
        $total = UserRepository::count();

        Response::json([
            'data' => UserResource::collection($users),
            'meta' => Paginator::meta($total, $params['page'], $params['perPage']),
        ]);
    }

    public function store(Request $request): void
    {
        $data = $request->all();

        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'user_type_id' => ['required', 'integer', 'exists:users_types,id'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        Response::json(UserResource::toArray(UserRepository::create($data)));
    }

    public function show(Request $request, $id): void
    {
        $user = UserRepository::findById((int) $id);

        if (!$user) {
            Response::json(['message' => 'Usuario no encontrado.'], 404);

            return;
        }

        Response::json(UserResource::toArray($user));
    }

    public function update(Request $request, $id): void
    {
        $user = UserRepository::findById((int) $id);

        if (!$user) {
            Response::json(['message' => 'Usuario no encontrado.'], 404);

            return;
        }

        $data = $request->all();

        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email,' . $user->getId()],
            'password' => ['sometimes', 'string', 'min:8', 'max:255'],
            'user_type_id' => ['required', 'integer', 'exists:users_types,id'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        Response::json(UserResource::toArray(UserRepository::update((int) $id, $data)));
    }

    public function destroy(Request $request, $id): void
    {
        $user = UserRepository::findById((int) $id);

        if (!$user) {
            Response::json(['message' => 'Usuario no encontrado.'], 404);

            return;
        }

        UserRepository::delete((int) $id);
        Response::noContent();
    }
}
