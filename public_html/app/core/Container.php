<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Minimal service container for dependency injection.
 *
 * Usage:
 *   $c = new Container();
 *   $c->bind(PDO::class, fn() => Database::connection());
 *   $pdo = $c->make(PDO::class);
 */
final class Container
{
    /** @var array<string, callable> */
    private array $bindings = [];

    /** @var array<string, object> */
    private array $instances = [];

    public function bind(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = $factory;
    }

    public function singleton(string $abstract, callable $factory): void
    {
        $this->bindings[$abstract] = function () use ($abstract, $factory) {
            if (!isset($this->instances[$abstract])) {
                $this->instances[$abstract] = $factory();
            }
            return $this->instances[$abstract];
        };
    }

    public function make(string $abstract): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }
        if (!isset($this->bindings[$abstract])) {
            // Auto-resolve: try to construct the class with reflection, recursively
            // resolving constructor dependencies.
            $obj = $this->autoResolve($abstract);
            $this->instances[$abstract] = $obj;
            return $obj;
        }
        $obj = ($this->bindings[$abstract])();
        $this->instances[$abstract] = $obj;
        return $obj;
    }

    /**
     * Construct a class resolving its dependencies, but supply a scalar
     * parameter (e.g. a role string for Authorize middleware) as the first
     * scalar constructor argument that has no type hint or a default.
     */
    public function makeWithParam(string $abstract, mixed $param): object
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        if (!class_exists($abstract)) {
            throw new \RuntimeException("No binding for $abstract and class does not exist.");
        }

        $reflection = new \ReflectionClass($abstract);
        $ctor = $reflection->getConstructor();

        if ($ctor === null) {
            return $reflection->newInstance();
        }

        $args = [];
        $paramUsed = false;
        foreach ($ctor->getParameters() as $p) {
            $type = $p->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $args[] = $this->make($type->getName());
                continue;
            }
            // First scalar param without a resolved class gets the supplied param.
            if (!$paramUsed) {
                $args[] = $param;
                $paramUsed = true;
                continue;
            }
            if ($p->isDefaultValueAvailable()) {
                $args[] = $p->getDefaultValue();
                continue;
            }
            throw new \RuntimeException("Cannot auto-resolve parameter \${$p->getName()} of $abstract");
        }

        return $reflection->newInstanceArgs($args);
    }

    /**
     * Auto-construct a class by inspecting its constructor and resolving
     * each parameter from the container. Falls back to null/default for
     * scalar parameters and known services for class type-hints.
     */
    private function autoResolve(string $class): object
    {
        if (!class_exists($class)) {
            throw new \RuntimeException("No binding for $class and class does not exist.");
        }

        $reflection = new \ReflectionClass($class);
        $ctor = $reflection->getConstructor();

        if ($ctor === null || $ctor->getNumberOfParameters() === 0) {
            return $reflection->newInstance();
        }

        $args = [];
        foreach ($ctor->getParameters() as $param) {
            $type = $param->getType();
            if ($type instanceof \ReflectionNamedType && !$type->isBuiltin()) {
                $typeName = $type->getName();
                $args[] = $this->make($typeName);
                continue;
            }
            if ($param->isDefaultValueAvailable()) {
                $args[] = $param->getDefaultValue();
                continue;
            }
            throw new \RuntimeException(
                "Cannot auto-resolve parameter \${$param->getName()} of $class"
            );
        }

        return $reflection->newInstanceArgs($args);
    }
}
