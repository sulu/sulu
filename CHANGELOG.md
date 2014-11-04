CHANGELOG for Sulu
==================

* [UNRELEASED]

  * Read urls for pages in all languages
    * Added urls variable, which contains the urls for current page in all localization of the current webspace, to:
      * twig 
      * smart-content, internal-links and sitemap results
    * Added hreflang tag to the `sitemap.xml` file
    * Updated Example:
      * Default Theme uses the urls var to generate alternative url tag and Langauge-chooser
