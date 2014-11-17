CHANGELOG for Sulu
==================

* dev-develop

    * BUGFIX      #568 [SnippetBundle] Added template to view for Snippets
    * ENHANCEMENT #539 [AdminBundle]   Added validation for iban and vat numbers from the eu
    * HOTFIX      #559 [CoreBundle]    Workaround upstream reg. in DoctrinePHPCRBundle, which causes 
                                       eager validation of workspace existence.
    * ENHANCEMENT #523 [All]           Refactored and improved functional tests
    * FEATURE     #553 [SnippetBundle] Possiblity to show all snippet types by not providing any
                                       snippetType parameters in the ContentType

* 0.11.1 (2014-11-13)

    * HOTFIX      #543 [SearchBundle]  Fixed re-index command
    * HOTFIX      #551 [SearchBundle]  Switched to test adapter for tests
    * HOTFIX      #549 [ContentBundle] Fixed page URL fetching for internal links used in snippets
    * HOTFIX      #512 [MediaBundle]   Only show Media from specific selected Collection
    * HOTFIX      #550 [MediaBundle]   Deleted Media do not throw Exception when page is saved

* 0.11.0 (2014-11-12)

    * BUGFIX      #540                 Real url in requested language (navigation, ...) for shadows
    * ENHANCEMENT #523 [ContentBundle] Prefix ContentBundle template path
    * BUGFIX      #531 [ContentBundle] Fixed single internal link freeze
    * BUGFIX      #529 [MediaBundle]   Display sorted Collections in overlay
    * FEATURE     #536 [MediaBundle]   Added Configurable display options for media-selection
    * ENHANCEMENT #361 [WebsiteBundle] Read urls for pages in all languages
    * ENHANCEMENT #526 [WebsiteBundle] Added Template var to resolver (Twig-Template)
    * ENHANCEMENT #528 [WebsiteBundle] Added memoize service to cache data and use it in twig extension
    * ENHANCEMENT #524 [ContentBundle] Prefix ContentBundle template path
    * BUGFIX      #518 [ContentBundle] Ordering of page changed when node is renamed
    * FEATURE     #511 [SnippetBundle] Ask confirmation when deleting Snippets which are referenced by content

* 0.10.2 (2014-11-07)

    * HOTFIX #509 [ContentBundle] Fixed cached data bug in smart-content

* 0.10.1 (2014-11-04)

* 0.10.0 (2014-11-03)

* 0.9.0 (2014-10-29)

* 0.8.6 (2014-10-20)

* 0.8.5 (2014-10-15)

* 0.8.4 (2014-10-08)

* 0.8.3 (2014-10-08)

* 0.8.2 (2014-10-07)

* 0.8.1 (2014-10-07)

* 0.8.0 (2014-10-01)

* 0.7.1 (2014-09-23)

* 0.7.0 (2014-09-11)

* 0.6.8 (2014-09-04)

* 0.6.7 (2014-09-02)

* 0.6.6 (2014-09-02)

* 0.6.5 (2014-09-01)

* 0.6.4 (2014-08-27)

* 0.6.3 (2014-08-20)

* 0.6.2 (2014-08-20)

* 0.6.1 (2014-08-19)

* 0.6.0 (2014-08-14)

* 0.5.0 (2014-07-23)

* 0.4.0 (2014-06-30)

* 0.3.0 (2014-05-07)

* 0.2.0 (2014-04-14)

* 0.1.1 (2014-03-06)

* 0.1.0 (2014-03-04)
