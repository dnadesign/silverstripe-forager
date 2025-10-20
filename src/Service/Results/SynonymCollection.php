<?php

namespace SilverStripe\Forager\Service\Results;

use JsonSerializable;
use SilverStripe\Model\ModelData;
use SilverStripe\Core\Injector\Injectable;

class SynonymCollection extends ModelData implements JsonSerializable
{

    use Injectable;

    public function __construct(private readonly string|int $id)
    {
        parent::__construct();
    }

    public function getId(): int|string
    {
        return $this->id;
    }

    public function jsonSerialize(): mixed
    {
        return [
            'id' => $this->id,
        ];
    }

}
