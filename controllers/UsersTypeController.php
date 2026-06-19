<?php

class UsersTypeController
{
    private const MESSAGES = [
        'name.max' => 'El nombre del tipo de usuario no puede exceder los 255 caracteres (Error: MAX_LENGTH_EXCEEDED).',
        'name.required' => 'El nombre del tipo de usuario es obligatorio.',
    ];

    public function index(Request $request): void
    {
        $params = Paginator::params($request, 20);
        $userTypes = UserTypeRepository::paginate($params['offset'], $params['perPage']);
        $total = UserTypeRepository::count();

        Response::json([
            'data' => UsersTypeResource::collection($userTypes),
            'meta' => Paginator::meta($total, $params['page'], $params['perPage']),
        ]);
    }

    public function store(Request $request): void
    {
        $data = $request->all();

        $errors = Validator::make($data, ['name' => ['required', 'string', 'max:255']], self::MESSAGES);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        Response::json(UsersTypeResource::toArray(UserTypeRepository::create($data)));
    }

    public function show(Request $request, $id): void
    {
        $userType = UserTypeRepository::findById((int) $id);

        if (!$userType) {
            Response::json(['message' => 'Tipo de usuario no encontrado.'], 404);

            return;
        }

        Response::json(UsersTypeResource::toArray($userType));
    }

    public function update(Request $request, $id): void
    {
        $userType = UserTypeRepository::findById((int) $id);

        if (!$userType) {
            Response::json(['message' => 'Tipo de usuario no encontrado.'], 404);

            return;
        }

        $data = $request->all();

        $errors = Validator::make($data, ['name' => ['required', 'string', 'max:255']], self::MESSAGES);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        Response::json(UsersTypeResource::toArray(UserTypeRepository::update((int) $id, $data)));
    }

    public function destroy(Request $request, $id): void
    {
        $userType = UserTypeRepository::findById((int) $id);

        if (!$userType) {
            Response::json(['message' => 'Tipo de usuario no encontrado.'], 404);

            return;
        }

        UserTypeRepository::delete((int) $id);
        Response::noContent();
    }
}
