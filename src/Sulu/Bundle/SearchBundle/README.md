SuluSearchBundle
================

[![](https://travis-ci.org/sulu-cmf/SuluSearchBundle.png)](https://travis-ci.org/sulu-cmf/SuluSearchBundle)
[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/sulu-cmf/SuluSearchBundle/badges/quality-score.png?s=ae0673b210ff6dd252a80fbb822e8ac789d24f73)](https://scrutinizer-ci.com/g/sulu-cmf/SuluSearchBundle/)

This bundle is part of the [Sulu Content Management
Framework](https://github.com/sulu-cmf/sulu-standard) and licensed under
the [MIT
License](https://github.com/sulu-cmf/SuluSearchBundle/blob/develop/LICENSE).

The SuluSearchBundle extends the
[MassiveSearchBundle](https://github.com/massiveart/MassiveSearchBundle) to
provide a metadata driver for Sulu Structure classes. This enables Sulu
content to be indexed.

## Usage

This bundle integrates the [MassiveSearchBundle](https://github.com/massiveart/MassiveSearchBundle) into
Sulu. For general usage informsation refer to the documentation for that bundle.

### Mapping structure documents

You can map search indexes on structure documents in the structure template:

````xml
<?xml version="1.0" ?>

<template xmlns="http://schemas.sulu.io/template/template"
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template-1.0.xsd">

    <!-- ... -->

    <properties>
        <property name="title" type="text_line" mandatory="true">
            <!-- ... -->

            <tag name="sulu.search.field" index="true" type="string" role="title" />
            
            <!-- ... -->
        </property>
    </properties>
</template>
```

The `tag` in the property is a hint for the search indexer. It will index the
field as type "string" and it will use this field as the "title" for the
search results.

### Roles

When you tag structure properties you can optionally assign roles. Roles tell
the search engine which fields should be available in the document via.
various standard getters:

- `title`: The title for the search result: `$document->getTitle()`
- `description`: The description / excerpt for the search result: `$document->getDescription()`
- `image`: Indicate a field which should be used to determine the image URL: `$document->getImageUrl()`

NOTE: If you are using the [SuluMediaBundle](https://github.com/sulu-cmf/SuluMediaBundle)  you can 
specify a media field as the image field.

### Indexing Structure documents

You index structure documents as you would any other object with the
[MassiveSearchBundle](https://github.com/massiveart/MassiveSearchBundle):

````php

// we get a structure from somewhere..
$yourStructure = $magicalStructureService->getStructure();

$searchManager = $container->get('massive_search.search_manager');
$searchManager->index($yourStructure);
````

### Searching 

Likewise, searching is exactly the same as with the massive search bundle:

````php

// we get a structure from somewhere..
$searchManager = $container->get('massive_search.search_manager');
$searchManager->createSearch('This is a search string')->locale('de')->index('content')->execute();
````

### Search from the command line

See the [MassiveSearchBundle](https://github.com/massiveart/MassiveSearchBundle) documentation.

### Rendering results

You can iterate over search results and retrieve the associated search
document. The URL will be the Structure URL (determined automatically).

````php
{% for hit in hits %}
    <section>
        <h3><a href="{{ hit.document.url }}">{{ hit.document.title }}</a></h3>
        <p><i>Class: {{ hit.document.class }}</i></p>
        <p>{{ hit.document.description }}</p>
        <p><img src="{{ hit.document.imageUrl }}" /></p>
    </section>
{% endfor %}
````

## Requirements

* Symfony: 2.3.*
* See the require section of [composer.json](https://github.com/sulu-cmf/SuluSearchBundle/blob/develop/composer.json)

