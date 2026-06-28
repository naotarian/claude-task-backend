<?php

namespace App\UseCases;

use Closure;
use Illuminate\Support\Facades\DB;

/**
 * Base class for application use cases.
 *
 * A use case represents a single application operation and is the
 * transaction boundary: wrap all persistence within transaction().
 * Side effects with external systems (mail, Stripe, S3) should be
 * deferred until after commit via DB::afterCommit() or queued jobs.
 */
abstract class AbstractUseCase
{
    /**
     * Run the given work inside a single database transaction.
     *
     * @template TReturn
     *
     * @param  Closure(): TReturn  $callback
     * @return TReturn
     */
    protected function transaction(Closure $callback): mixed
    {
        return DB::transaction($callback);
    }
}
