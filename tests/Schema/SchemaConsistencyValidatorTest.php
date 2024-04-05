<?php

declare(strict_types=1);

namespace League\OpenAPIValidation\Tests\Schema;

use League\OpenAPIValidation\Schema\Exception\InconsistentSchema;
use League\OpenAPIValidation\Schema\SchemaConsistencyValidator;

class SchemaConsistencyValidatorTest extends SchemaValidatorTest
{
    private const SIMPLE_SPEC = <<<SPEC
schema:
  type: object
  properties:
    id: 
      type: integer
    name: 
      type: string
SPEC;

    private const COMPLEX_SPEC = <<<SPEC
schema:
  type: object
  properties:
    id: 
      type: integer
    name: 
      type: string
    role:
      type: object
      properties:
        value:
          type: string
        guard:
          type: string
        level:
          type: integer
        nested:
          type: object
          properties:
            prop11:
                type: string
            prop12:
                type: string
            prop13:
                type: string
            prop14:
                type: object
                properties:
                  prop21:
                      type: string
                  prop22:
                      type: string
                  prop23:
                      type: string
        permissions:
          type: array
          items:
            type: string
SPEC;

    /** @var SchemaConsistencyValidator */
    private $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new SchemaConsistencyValidator();
    }

    public function testSimpleConsistentStructure(): void
    {
        $this->validator->validate(['id' => 1, 'name' => 'John Doe'], $this->loadRawSchema(self::SIMPLE_SPEC));
        $this->addToAssertionCount(1);
    }

    public function testComplexConsistentStructure(): void
    {
        $this->validator->validate([
            'id'   => 1,
            'name' => 'John Doe',
            'role' => [
                'value'       => 'admin',
                'guard'       => 'api',
                'level'       => 3,
                'nested'      => [
                    'prop11' => '11',
                    'prop12' => '12',
                    'prop13' => '13',
                    'prop14' => [
                        'prop21' => '21',
                        'prop22' => '22',
                        'prop23' => '23',
                    ],
                ],
                'permissions' => [
                    'create',
                    'read',
                    'update',
                    'delete',
                ],
            ],
        ], $this->loadRawSchema(self::COMPLEX_SPEC));
        $this->addToAssertionCount(1);
    }

    public function testSimpleInconsistentStructure(): void
    {
        $this->expectExceptionMessageMatches('/\[\$\.age\]/');
        $this->expectException(InconsistentSchema::class);
        $this->validator->validate(['id' => 1, 'name' => 'John Doe', 'age' => 21], $this->loadRawSchema(self::SIMPLE_SPEC));
    }

    public function testComplexInconsistentStructure(): void
    {
        $this->expectExceptionMessageMatches('/\[\$\.role->nested->unexpected\]/');
        $this->expectException(InconsistentSchema::class);
        $this->validator->validate([
            'id'   => 1,
            'name' => 'John Doe',
            'role' => [
                'value'       => 'admin',
                'guard'       => 'api',
                'level'       => 3,
                'nested'      => [
                    'prop11'     => '11',
                    'prop12'     => '12',
                    'prop13'     => '13',
                    'prop14'     => [
                        'prop21'   => '21',
                        'prop22'   => '22',
                        'prop23'   => '23',
                    ],
                    'unexpected' => ['foo' => 'bar'],
                ],
                'permissions' => [
                    'create',
                    'read',
                    'update',
                    'delete',
                ],
            ],
        ], $this->loadRawSchema(self::COMPLEX_SPEC));
    }
}
