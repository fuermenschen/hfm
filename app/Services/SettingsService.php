<?php

namespace App\Services;

use ReflectionClass;
use ReflectionNamedType;
use ReflectionProperty;
use Throwable;

class SettingsService
{
    public function __construct() {}

    /**
     * Return all settings grouped by class.
     *
     * Structure:
     * [
     *   FQCN => [
     *     'group' => string|null,
     *     'settings' => [
     *       settingName => [
     *         'value' => mixed|null,
     *         'type' => string|null,
     *         'description' => string|null,
     *         'encrypted' => bool|null,
     *       ],
     *       ...
     *     ],
     *   ],
     * ]
     */
    public function getAllSettings(): array
    {
        $classes = config('settings.settings');

        if (! \is_array($classes) || empty($classes)) {
            return [];
        }

        $result = [];

        foreach ($classes as $class) {
            if (! \is_string($class) || ! class_exists($class)) {
                continue;
            }

            // Resolve instance to fetch current values (gracefully handle failures)
            $instance = null;
            $instanceData = null;
            try {
                $instance = app($class);
                if ($instance !== null && \method_exists($instance, 'toArray')) {
                    /** @var array<string, mixed> $arr */
                    $arr = $instance->toArray();
                    $instanceData = $arr;
                }
            } catch (Throwable $e) {
                $instance = null;
                $instanceData = null;
            }

            // Static helpers (optional on classes)
            $group = null;
            try {
                if (\method_exists($class, 'group')) {
                    /** @var class-string $class */
                    $group = $class::group();
                }
            } catch (Throwable $e) {
                $group = null;
            }

            $encryptedMap = null;
            try {
                if (\method_exists($class, 'encrypted')) {
                    $enc = $class::encrypted();
                    $encryptedMap = \is_array($enc) ? array_fill_keys($enc, true) : null;
                }
            } catch (Throwable $e) {
                $encryptedMap = null;
            }

            $descriptionsMap = null;
            try {
                if (\method_exists($class, 'descriptions')) {
                    $descriptions = $class::descriptions();
                    $descriptionsMap = \is_array($descriptions) ? $descriptions : null;
                }
            } catch (Throwable $e) {
                $descriptionsMap = null;
            }

            $titlesMap = null;
            try {
                if (\method_exists($class, 'titles')) {
                    $titles = $class::titles();
                    $titlesMap = \is_array($titles) ? $titles : null;
                }
            } catch (Throwable $e) {
                $titlesMap = null;
            }

            $rulesMap = null;
            try {
                if (\method_exists($class, 'rules')) {
                    $rules = $class::rules();
                    $rulesMap = \is_array($rules) ? $rules : null;
                }
            } catch (Throwable $e) {
                $rulesMap = null;
            }

            $details = ['title' => null, 'description' => null];
            try {
                if (\method_exists($class, 'settingsDetails')) {
                    $d = $class::settingsDetails();
                    if (\is_array($d)) {
                        $details['title'] = $d['title'] ?? null;
                        $details['description'] = $d['description'] ?? null;
                    }
                }
            } catch (Throwable $e) {
                $details = ['title' => null, 'description' => null];
            }

            // Determine settings fields from public properties defined on the class
            $fields = [];
            try {
                $ref = new ReflectionClass($class);
                foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                    if ($prop->getDeclaringClass()->getName() !== $class) {
                        continue; // skip inherited public properties
                    }
                    $fields[] = $prop;
                }
            } catch (Throwable $e) {
                $fields = [];
            }

            $settings = [];
            foreach ($fields as $prop) {
                /** @var ReflectionProperty $prop */
                $name = $prop->getName();

                // value
                $value = null;
                if ($instanceData !== null && \array_key_exists($name, $instanceData)) {
                    $value = $instanceData[$name];
                } elseif ($instance !== null) {
                    try {
                        if ($prop->isInitialized($instance)) {
                            $value = $prop->getValue($instance);
                        } else {
                            // As a final fallback, attempt array access via toArray() result
                            $value = null;
                        }
                    } catch (Throwable $e) {
                        $value = null;
                    }
                }

                // type
                $type = null;
                try {
                    $t = $prop->getType();
                    if ($t instanceof ReflectionNamedType) {
                        $nameType = $t->getName();
                        $type = ($t->allowsNull() && $nameType !== 'mixed' && $nameType !== 'null')
                            ? '?'.$nameType
                            : $nameType;
                    } elseif ($t instanceof \ReflectionUnionType) {
                        $typeNames = [];
                        foreach ($t->getTypes() as $sub) {
                            $typeNames[] = $sub instanceof ReflectionNamedType ? $sub->getName() : (string) $sub;
                        }
                        $type = implode('|', $typeNames);
                    } elseif ($t instanceof \ReflectionIntersectionType) {
                        $typeNames = [];
                        foreach ($t->getTypes() as $sub) {
                            $typeNames[] = $sub instanceof ReflectionNamedType ? $sub->getName() : (string) $sub;
                        }
                        $type = implode('&', $typeNames);
                    } else {
                        $type = null;
                    }
                } catch (Throwable $e) {
                    $type = null;
                }

                // description
                $description = null;
                if ($descriptionsMap !== null && \array_key_exists($name, $descriptionsMap)) {
                    $description = \is_string($descriptionsMap[$name]) ? $descriptionsMap[$name] : null;
                }

                // encrypted
                $encrypted = null;
                if ($encryptedMap !== null) {
                    $encrypted = \array_key_exists($name, $encryptedMap);
                }

                $settings[$name] = [
                    'value' => $value,
                    'type' => $type,
                    'description' => $description,
                    'encrypted' => $encrypted,
                    'title' => $titlesMap[$name] ?? null,
                    'rules' => $rulesMap[$name] ?? null,
                ];
            }

            $result[$class] = [
                'group' => $group,
                'title' => $details['title'],
                'description' => $details['description'],
                'settings' => $settings,
            ];
        }

        return $result;
    }

    /**
     * Persist settings values.
     *
     * Expected payload shape: [ FQCN => [ settingName => scalar|null ] ]
     */
    public function save(array $payload): void
    {
        if (empty($payload)) {
            return;
        }

        foreach ($payload as $class => $values) {
            if (! \is_string($class) || ! class_exists($class) || ! \is_array($values)) {
                continue;
            }

            try {
                $instance = app($class);
            } catch (Throwable $e) {
                $instance = null;
            }

            if ($instance === null) {
                continue;
            }

            // Build type map via reflection to coerce incoming values
            $typeMap = [];
            try {
                $ref = new ReflectionClass($class);
                foreach ($ref->getProperties(ReflectionProperty::IS_PUBLIC) as $prop) {
                    if ($prop->getDeclaringClass()->getName() !== $class) {
                        continue;
                    }
                    $t = $prop->getType();
                    $type = null;
                    if ($t instanceof ReflectionNamedType) {
                        $nameType = $t->getName();
                        $type = ($t->allowsNull() && $nameType !== 'mixed' && $nameType !== 'null')
                            ? '?'.$nameType
                            : $nameType;
                    }
                    $typeMap[$prop->getName()] = $type;
                }
            } catch (Throwable $e) {
                $typeMap = [];
            }

            foreach ($values as $name => $value) {
                if (! \is_string($name)) {
                    continue;
                }
                $coerced = $this->coerceValue($typeMap[$name] ?? null, $value);
                try {
                    $instance->{$name} = $coerced;
                } catch (Throwable $e) {
                    // ignore invalid assignments
                }
            }

            try {
                if (\method_exists($instance, 'save')) {
                    $instance->save();
                }
            } catch (Throwable $e) {
                // ignore persistence problems per class
            }
        }
    }

    protected function coerceValue(?string $type, mixed $value): mixed
    {
        if ($type === null) {
            return $value;
        }

        $nullable = false;
        if (str_starts_with($type, '?')) {
            $nullable = true;
            $type = substr($type, 1);
        }

        if ($nullable && ($value === '' || $value === null)) {
            return null;
        }

        switch ($type) {
            case 'int':
                return is_numeric($value) ? (int) $value : 0;
            case 'float':
            case 'double':
                return is_numeric($value) ? (float) $value : 0.0;
            case 'bool':
                if (\is_bool($value)) {
                    return $value;
                }
                $truthy = ['1', 'true', 'on', 'yes', 'y'];

                return in_array(strtolower((string) $value), $truthy, true);
            case 'string':
                return \is_string($value) ? $value : (string) $value;
            case 'array':
                return \is_array($value) ? $value : [$value];
            default:
                return $value;
        }
    }
}
