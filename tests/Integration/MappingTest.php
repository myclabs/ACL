<?php

namespace Tests\Mapping;

use Doctrine\ORM\Tools\SchemaValidator;
use Tests\MyCLabs\ACL\Integration\AbstractIntegrationTest;

class MappingValidationTest extends AbstractIntegrationTest
{
    /**
     * Doctrine schema validation
     */
    public function testValidateSchema()
    {
        $validator = new SchemaValidator($this->em);
        $errors = $validator->validateMapping();

        if (count($errors) > 0) {
            $message = PHP_EOL;
            foreach ($errors as $class => $classErrors) {
                $message .= "- " . $class . ":" . PHP_EOL . implode(PHP_EOL, $classErrors) . PHP_EOL . PHP_EOL;
            }
            $this->fail($message);
        }
    }
}
