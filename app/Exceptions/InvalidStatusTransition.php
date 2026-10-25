<?php

namespace App\Exceptions;

use App\Enums\ListingStatus;
use DomainException;
use Illuminate\Database\Eloquent\Model;

class InvalidStatusTransition extends DomainException
{
    public static function between(Model $model, ListingStatus $from, ListingStatus $to): self
    {
        return new self(sprintf('%s #%s cannot move from "%s" to "%s".', $model->getMorphClass(), $model->getKey(), $from->value, $to->value));
    }

    public static function expired(Model $model): self
    {
        return new self(sprintf('%s #%s has expired and must be renewed through moderation.', $model->getMorphClass(), $model->getKey()));
    }
}
