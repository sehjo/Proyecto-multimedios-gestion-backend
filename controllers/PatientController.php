<?php

class PatientController
{
    public function index(Request $request): void
    {
        $params = Paginator::params($request, 20);
        $patients = PatientRepository::paginate($params['offset'], $params['perPage']);
        $total = PatientRepository::count();

        Response::json([
            'data' => PatientResource::collection($patients),
            'meta' => Paginator::meta($total, $params['page'], $params['perPage']),
        ]);
    }

    public function store(Request $request): void
    {
        $data = $request->all();

        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'nick' => ['required', 'string', 'max:255'],
            'suffering' => ['nullable', 'string', 'max:500'],
            'register_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        Response::json(PatientResource::toArray(PatientRepository::create($data)));
    }

    public function show(Request $request, $id): void
    {
        $patient = PatientRepository::findById((int) $id);

        if (!$patient) {
            Response::json(['message' => 'Paciente no encontrado.'], 404);

            return;
        }

        Response::json(PatientResource::toArray($patient));
    }

    public function update(Request $request, $id): void
    {
        $patient = PatientRepository::findById((int) $id);

        if (!$patient) {
            Response::json(['message' => 'Paciente no encontrado.'], 404);

            return;
        }

        $data = $request->all();

        $errors = Validator::make($data, [
            'name' => ['required', 'string', 'max:255'],
            'lastname' => ['required', 'string', 'max:255'],
            'nick' => ['required', 'string', 'max:255'],
            'suffering' => ['nullable', 'string', 'max:500'],
            'register_by' => ['nullable', 'integer', 'exists:users,id'],
        ]);

        if (!empty($errors)) {
            Response::validationError($errors);

            return;
        }

        Response::json(PatientResource::toArray(PatientRepository::update((int) $id, $data)));
    }

    public function destroy(Request $request, $id): void
    {
        $patient = PatientRepository::findById((int) $id);

        if (!$patient) {
            Response::json(['message' => 'Paciente no encontrado.'], 404);

            return;
        }

        PatientRepository::delete((int) $id);
        Response::noContent();
    }
}
