<?php

class Validator
{
    /**
     * @return array<string, string[]> empty array when valid
     */
    public static function make(array $data, array $rules, array $messages = []): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            if (in_array('sometimes', $fieldRules, true) && !array_key_exists($field, $data)) {
                continue;
            }

            $isRequired = in_array('required', $fieldRules, true);
            $isNullable = in_array('nullable', $fieldRules, true);
            $value = $data[$field] ?? null;
            $isEmpty = $value === null || $value === '';

            if ($isEmpty) {
                if ($isRequired) {
                    $errors[$field][] = self::message($messages, $field, 'required', 'El campo ' . $field . ' es obligatorio.');
                }

                continue;
            }

            if ($isNullable && $isEmpty) {
                continue;
            }

            foreach ($fieldRules as $rule) {
                if (in_array($rule, ['required', 'nullable', 'sometimes'], true)) {
                    continue;
                }

                [$name, $params] = self::parseRule($rule);
                $error = self::applyRule($name, $params, $field, $value);

                if ($error !== null) {
                    $errors[$field][] = self::message($messages, $field, $name, $error);
                }
            }
        }

        return $errors;
    }

    private static function parseRule(string $rule): array
    {
        if (!str_contains($rule, ':')) {
            return [$rule, []];
        }

        [$name, $paramString] = explode(':', $rule, 2);

        return [$name, explode(',', $paramString)];
    }

    private static function applyRule(string $name, array $params, string $field, $value): ?string
    {
        switch ($name) {
            case 'string':
                return is_string($value) ? null : "El campo $field debe ser una cadena de texto.";

            case 'integer':
                return filter_var($value, FILTER_VALIDATE_INT) !== false ? null : "El campo $field debe ser un número entero.";

            case 'email':
                return filter_var($value, FILTER_VALIDATE_EMAIL) !== false ? null : "El campo $field debe ser un correo válido.";

            case 'max':
                $max = (int) $params[0];

                return mb_strlen((string) $value) <= $max ? null : "El campo $field no debe ser mayor a $max caracteres.";

            case 'min':
                $min = (int) $params[0];

                return mb_strlen((string) $value) >= $min ? null : "El campo $field debe tener al menos $min caracteres.";

            case 'exists':
                $table = $params[0];
                $column = $params[1] ?? 'id';

                return self::rowExists($table, $column, $value) ? null : "El campo $field seleccionado no es válido.";

            case 'unique':
                $table = $params[0];
                $column = $params[1] ?? $field;
                $ignoreId = $params[2] ?? null;

                return self::isUnique($table, $column, $value, $ignoreId) ? null : "El valor del campo $field ya está en uso.";

            default:
                return null;
        }
    }

    private static function rowExists(string $table, string $column, $value): bool
    {
        $stmt = db()->prepare("SELECT 1 FROM `$table` WHERE `$column` = :value LIMIT 1");
        $stmt->execute(['value' => $value]);

        return $stmt->fetchColumn() !== false;
    }

    private static function isUnique(string $table, string $column, $value, ?string $ignoreId): bool
    {
        $sql = "SELECT 1 FROM `$table` WHERE `$column` = :value";
        $bindings = ['value' => $value];

        if ($ignoreId !== null && $ignoreId !== '') {
            $sql .= ' AND id != :ignoreId';
            $bindings['ignoreId'] = $ignoreId;
        }

        $sql .= ' LIMIT 1';

        $stmt = db()->prepare($sql);
        $stmt->execute($bindings);

        return $stmt->fetchColumn() === false;
    }

    private static function message(array $messages, string $field, string $rule, string $default): string
    {
        return $messages["$field.$rule"] ?? $default;
    }
}
