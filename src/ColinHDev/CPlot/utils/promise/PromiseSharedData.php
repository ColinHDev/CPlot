<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\utils\promise;

use Closure;
use Throwable;

/**
 * @internal
 * @see PromiseResolver
 * @phpstan-template TValue
 */
final class PromiseSharedData {

    /**
     * An array of {@see Closure}s to call when the promise is resolved successfully.
     * @phpstan-var array<int, (Closure(TValue): void)|(Closure(): void)>
     */
    public array $onSuccess = [];
    /**
     * An array of {@see Closure}s to call when the promise is rejected.
     * @phpstan-var array<int, (Closure(Throwable): void)|(Closure(): void)>
     */
    public array $onError = [];

    /**
     * The result of the promise.
     * @phpstan-var TValue|null
     */
    public mixed $result = null;
    /**
     * The exception that was thrown when the promise was rejected.
     */
    public ?Throwable $error = null;
}