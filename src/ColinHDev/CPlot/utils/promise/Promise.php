<?php

declare(strict_types=1);

namespace ColinHDev\CPlot\utils\promise;

use Closure;
use Throwable;
use function spl_object_id;

/**
 * This promise system was heavily inspired by PocketMine-MP's promise system: @see \pocketmine\promise\Promise
 * But due to the limitations of their classes and the inability to inherit them due to them being final, our own system
 * had to be created.
 * This promise system is similar to PocketMine-MP's one but more suited to the needs of this plugin.
 *
 * @phpstan-template TValue
 */
final class Promise {

    /**
     * @internal Do not call this directly; create a new {@see PromiseResolver} and call {@see PromiseResolver::getPromise()}
     * @phpstan-param PromiseSharedData<TValue> $shared
     */
    public function __construct(private PromiseSharedData $shared) {}

    /**
     * Provide callbacks to be called when the promise is resolved or rejected.
     * @phpstan-param (Closure(TValue): void)|(Closure(): void) $onSuccess
     * @phpstan-param (Closure(Throwable): void)|(Closure(): void) $onFailure
     */
    public function onCompletion(Closure $onSuccess, Closure $onFailure) : void {
        if ($this->shared->result !== null) {
            $onSuccess($this->shared->result);
        } else if ($this->shared->error !== null) {
            $onFailure($this->shared->error);
        } else {
            $this->shared->onSuccess[spl_object_id($onSuccess)] = $onSuccess;
            $this->shared->onError[spl_object_id($onFailure)] = $onFailure;
        }
    }

    /**
     * Returns true if the promise has been resolved or rejected.
     */
    public function isResolved() : bool {
        return $this->shared->result !== null || $this->shared->error !== null;
    }

    /**
     * Returns the result of the promise.
     * @phpstan-return TValue|null
     */
    public function getResult() : mixed {
        return $this->shared->result;
    }

    /**
     * Returns the exception that was thrown when the promise was rejected.
     */
    public function getError() : ?Throwable {
        return $this->shared->error;
    }

    /**
     * Utility method to create a promise that resolves once all the given promises have resolved.
     *
     * @param Promise ...$promises All the promises to wait for.
     * @phpstan-template UValue of mixed
     * @phpstan-param Promise<UValue> ...$promises
     *
     * Returns a {@see Promise} that resolves with an array of all the results of the given promises. The results in the
     * array are in the same order in which the promises were given to the method.
     * If any of the given promises is rejected, the returned promise is rejected with the same exception.
     * @return Promise
     * @phpstan-return Promise<non-empty-array<UValue>>
     */
    public static function all(Promise ...$promises) : Promise {
        /** @phpstan-var PromiseResolver<non-empty-array<UValue>> $resolver */
        $resolver = new PromiseResolver();
        /** @phpstan-var non-empty-array<UValue> $results */
        $results = [];
        foreach($promises as $key => $promise) {
            $promise->onCompletion(
                function(mixed $value) use($promises, $key, &$results, $resolver) : void {
                    $results[$key] = $value;
                    if (count($results) === count($promises)) {
                        $resolver->resolveSilent($results);
                    }
                },
                function(Throwable $error) use($resolver) : void {
                    $resolver->rejectSilent($error);
                }
            );
        }
        return $resolver->getPromise();
    }
}