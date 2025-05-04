<?php

namespace JoBins\Meilisearch\Traits;

trait HasQuery
{
    /**
     * @var callable
     */
    public $queryCallback;

    public function query(callable $callback): self
    {
        $this->queryCallback = $callback;

        return $this;
    }
}
