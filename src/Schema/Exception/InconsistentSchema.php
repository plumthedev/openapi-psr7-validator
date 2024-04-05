<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Schema\Exception;

use League\OpenAPIValidation\Schema\BreadCrumb;

use function implode;
use function sprintf;

class InconsistentSchema extends SchemaMismatch
{
    public static function create(BreadCrumb $breadCrumb): self
    {
        $exception = new self(sprintf(
            'Data on path: [%s] is inconsistent with provided schema.',
            '$.' . implode('->', $breadCrumb->buildChain())
        ));
        $exception->hydrateDataBreadCrumb($breadCrumb);

        return $exception;
    }
}
