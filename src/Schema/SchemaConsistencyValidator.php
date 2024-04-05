<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Schema;

use cebe\openapi\spec\Schema;
use cebe\openapi\spec\Type as CebeType;
use League\OpenAPIValidation\Foundation\ArrayHelper;
use League\OpenAPIValidation\Schema\Exception\InconsistentSchema;
use RecursiveArrayIterator;

use function is_array;
use function iterator_apply;

final class SchemaConsistencyValidator implements Validator
{
    /** @inheritDoc */
    public function validate($data, Schema $schema, ?BreadCrumb $breadCrumb = null): void
    {
        if (! is_array($data)) {
            return;
        }

        if (! ArrayHelper::isAssoc($data)) {
            return;
        }

        $iterator   = new RecursiveArrayIterator($data);
        $breadCrumb = $breadCrumb ?? new BreadCrumb();
        iterator_apply($iterator, [$this, 'checkConsistencyRecursive'], [$iterator, $schema, $breadCrumb]);
    }

    /**
     * @param RecursiveArrayIterator<string|int, mixed> $iterator
     * @param array<string> $parents
     *
     * @throws InconsistentSchema
     */
    private function checkConsistencyRecursive(
        RecursiveArrayIterator $iterator,
        Schema $schema,
        BreadCrumb $breadCrumb,
        array $parents = []
    ): void {
        while ($iterator->valid()) {
            $key = $iterator->key();

            if ($iterator->hasChildren()) {
                $childrenParents   = $parents;
                $childrenParents[] = $key;
                $this->checkConsistencyRecursive(
                    $iterator->getChildren(),
                    $schema,
                    $breadCrumb->addCrumb($key),
                    $childrenParents
                );
            }

            $currentSchema = $schema;

            foreach ($parents as $parentKey) {
                $currentSchema = $currentSchema->properties[$parentKey] ?? null;

                if ($currentSchema === null) {
                    throw InconsistentSchema::create($breadCrumb);
                }
            }

            if ($currentSchema->type !== CebeType::OBJECT) {
                $iterator->next();
                continue;
            }

            $propertySchema = $currentSchema->properties[$key] ?? null;

            if ($propertySchema === null) {
                $breadCrumb = $breadCrumb->addCrumb($key);

                throw InconsistentSchema::create($breadCrumb);
            }

            $iterator->next();
        }
    }
}
