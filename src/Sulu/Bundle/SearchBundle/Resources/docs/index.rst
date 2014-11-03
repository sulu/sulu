SuluSearchBundle
================

The SuluSearchBundle provides an abstraction for search engine libraries.

By default it is configured to use the Zend Lucene library, which must be
installed (see the ``suggests`` and ``require-dev`` sections in `composer.json`

Mapping
-------

The SuluSearchBundle requires that you define which objects should be indexed
through *mapping*. Currently only **XML mapping** supported:

.. code-block::

    <!-- /path/to/YourBundle/Resources/config/sulu-search/Product.xml -->
    <sulu-search-mapping xmlns="http://sulu.io/schema/dic/sulu-search-mapping">

        <mapping class="Sulu\Bundle\SearchBundle\Tests\Resources\TestBundle\Entity\Product">
            <indexName>product</indexName>
            <idField name="id"/>
            <fields>
                <field name="title" type="string" />
                <field name="body" type="string" />
            </fields>
        </mapping>

    </sulu-search-mapping>

This mapping will cause the fields ``title`` and ``body`` to be indexed into
an index named ``product`` using the ID obtained from the objects ``id``
field. (We use the Symfony `PropertyAccess
<http://symfony.com/doc/current/components/property_access/index.html>`_
component, so it works on properties and methods alike).

Note:

- This file MUST be located in ``YourBundle/Resources/config/sulu-search``
- It must be named after the name of your class (without the namespace) e.g.
  ``Product.xml``
- Your ``Product`` class MUST be located in one of the following folders:
  - ``YourBundle/Document``
  - ``YourBundle/Entity``
  - ``YourBundle/Model``

.. note::

    This is an early version of the bundle, it will support explict non-magic
    mapping in the future.

Indexing
--------

Once you have created your mapping files you can index your objects, for
example after saving it.

The bundle provides the ``sulu_search.search_manager`` object which is the
only service which you will need to access.

.. code-block:: php

    $product = new Product();

    // ... populate the product, persist it, whatever.

    $searchManager = $this->get('sulu_search.search_manager');
    $searchManager->index($product);

The SearchManager will know from the mapping how to index the product, and it
will be indexed using the configured search library adapter.

.. note:: The bundle automatically removes existing documents with the same
          ID. The ID mapping is mandatory.

Searching
---------

As with the indexing, searching for results is also done with the
SearchManager.

Currently only supported by query string is supported. The query string
is passed directly to the search library:

.. code-block:: php

    $hits = $searchManager->search('My Product');

    foreach ($hits as $hit) {
        echo $hit->getScore();

        // @var Sulu\Bundle\SearchBundle\Search\Document
        $document = $hit->getDocument();

        // retrieve the indexed documents "body" field
        $body = $document->getField('body');

        // retrieve the indexed ID of the document
        $body = $document->getId();
    }
