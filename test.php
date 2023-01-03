<?php

/*
 * This file is part of Sulu.
 *
 * (c) Sulu GmbH
 *
 * This source file is subject to the MIT license that is bundled
 * with this source code in the file LICENSE.
 */

use Webmozart\Assert\Assert;

require_once 'vendor/autoload.php';

/**
 * Get a list of properties that should have a nullable type.
 */
function getOptionalProperties(DOMDocument $xml): array
{
    $optionalProperties = [];
    foreach ($xml->getElementsByTagName('field') as $xmlNode) {
        $nullableXML = $xmlNode->attributes->getNamedItem('nullable');
        if (null === $nullableXML) {
            continue;
        }

        $nullable = 'true' === $nullableXML->nodeValue;
        if (!$nullable) {
            continue;
        }

        /** @var DOMNode $xmlNode */
        $optionalProperties[] = $xmlNode->attributes->getNamedItem('name')->nodeValue;
    }

    return $optionalProperties;
}

function checkDocCommentType(string $className, ReflectionProperty $property): void
{
    $docComment = $property->getDocComment();

    // If the doc comment does not exist, then the function returns false
    if (false === $docComment) {
        return;
    }

    $matches = [];
    if (\preg_match('/@var\s+(\S+)/', $docComment, $matches)) {
        $types = \explode('|', $matches[1]);
        if (!\in_array('null', $types, true)) {
            reportDefectiveType($className, $property->getName(), $matches[1]);
        }
    } else {
        reportDefectiveType($className, $property->getName(), '<MISSING>');
    }
}

function reportDefectiveType(string $className, string $property, string $currentType): void
{
    echo "Kaputt @ $className $property $currentType\n";
}

// -------- MAIN PROGRAM ------------------
$output = [];
\exec('find src -iname "*.orm.xml"', $output);

foreach ($output as $ormFile) {
    //echo '== Checking '.$ormFile.' =='.PHP_EOL;
    $xml = new DOMDocument();
    $xml->load($ormFile);

    $optionalProperties = getOptionalProperties($xml);

    $classNode = $xml->getElementsByTagName('mapped-superclass')[0] ?? null;
    $classNode ??= $xml->getElementsByTagName('entity')[0] ?? null;
    Assert::notNull($classNode, 'Could not find class Name in file: ' . $ormFile);

    $className = $classNode->attributes->getNamedItem('name')->nodeValue;

    $reflection = new ReflectionClass($className);
    foreach ($optionalProperties as $optionalProperty) {
        $property = $reflection->getProperty($optionalProperty);

        // Check the return type
        $type = $property->getType();
        if (null !== $type) {
            if ($type->allowsNull()) {
                // If the type allows null we are fine check the next property
                continue;
            } else {
                reportDefectiveType($className, $optionalProperty, (string) $type);
            }
        }

        checkDocCommentType($className, $property);
    }
}
