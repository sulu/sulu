CHANGELOG for Sulu
==================

* dev-develop
    * ENHANCEMENT #2462 [All]                 Removed unnecessary NodeInterface definitions in tests
    * BUGFIX      #2455 [CoreBundle]          Fixed ServerStatusCommand for Symfony 2.8.7
    * BUGFIX      #2443 [WebsiteBundle]       Added portal check for portal-routes
    * FEATURE     #2424 [Content]             Add support for XInclude
    * BUGFIX      #2439 [ContentBundle]       Fixed tab visibility for create new page localization
    * ENHANCEMENT #2428 [Content]             Removed move and copy method from ContentMapper
    * BUGFIX      #2426 [RouteBundle]         Fixed route-provider when no resource-locator prefix isset
    * BUGFIX      #2418 [ContentBundle]       Removed ContentMapperRequest
    * ENHANCEMENT #2414 [ContentBundle]       Removed save method of ContentMapper
    * BUGFIX      #2367 [ContentBundle]       Fixed copy internal-link into new language
    * BUGFIX      #2396                       Fixed composer-events
    * ENHANCEMENT #2403 [ContentBundle]       Added lazy start of ckeditor for content form
    * BUGFIX      #2397 [PreviewBundle]       Added catching of "Unable to find template" exception
    * BUGFIX      #2396 [PreviewBundle]       Fixed leaking events of preview
    * BUGFIX      #2389 [MediaBundle]         Removed twice adding of navigation item
    * ENHANCEMENT #2386 [ContentBundle]       Use DocumentManager for NodeController postAction
    * BUGFIX      #2325 [WebsiteBundle]       Fixed a query issue on Postgresql
    * BUGFIX      #2379 [MediaBundle]         Inject CategoryRepository in MediaManager to avoid using removed constant
    * BUGFIX      #2370 [TestBundle]          Use Doctrine DBAL as default PHPCR-Backend
    * BUGFIX      #2369 [All]                 Install the symfony phpunit bridge again
    * ENHANCEMENT #2356 [PreviewBundle]       Added default error message 
    * BUGFIX      #2354 [ContentBundle]       Fixed javascript error preview is null for new page form 
    * FEATURE     #2336 [ContentBundle]       Enabled internal link in ckeditor
    * ENHANCEMENT #2338 [MarkupBundle]        Implemented markup bundle
    * FEATURE     #2333 [PreviewBundle]       Added preview render error templates 
    * ENHANCEMENT #2353 [WebsocketBundle]     Changed configuration to default disable websocket 
    * BUGFIX      #2351 [ContentBundle]       Removed strange condition for data-changed 
    * BUGFIX      #2352 [CoreBundle]          Fixed RequestAnalyzer for use with ESI
    * FEATURE     #2349 [RouteBundle]         Added route-bundle 
    * FEATURE     #2299 [PreviewBundle]       Implemented preview bundle 
    * ENHANCEMENT #2289 [ContentBundle]       Added display options support to date content type
    * ENHANCEMENT #2316 [Symfony]             Added collector compiler pass
    * ENHANCEMENT #2279 [Webspace]            Do not hide invalid webspace exceptions
    * ENHANCEMENT #2288 [WebsiteBundle]       Fixed overriding request attributes and set them on the request
    * BUGFIX      #2288 [AdminBundle]         Removed deleting of entire dom tree on tab change
    * ENHANCEMENT #2278 [TestBundle]          Cache result of Sulu intitializer rather than using a fixture
    * BUGFIX      #2305 [WebsiteBundle]       Fixed handling of non-default formats in error pages
    * ENHANCEMENT #2341 [MediaBundle]         Added category to medias
    * ENHANCEMENT #2323 [WebsiteBundle]       Added TWIG-Extension to check if parent nav-item is active
    * ENHANCEMENT #2377 [CoreBundle]          Made --router and --env optional when running the console commands server:run, server:start, server:stop and server:status

* 1.2.3 (2016-06-01)
    * HOTFIX      #2427 [Hash]                Fixed bug when using non generic visitor in HashSerializeEventSubscriber
    * HOTFIX      #2401 [MediaBundle]         Fixed slow media queries
    * HOTFIX      #2415 [ContactBundle]       Fixed account contacts api response
    * HOTFIX      #2401 [MediaBundle]         Fixed search in media bundle
    * HOTFIX      #2381 [ContentBundle]       Fixed auto-name subscriber to rename at the very end of persist
    * HOTFIX      #2388 [Rest]                Fixed bug when applying same sortfield multiple times
    * HOTFIX      #2378 [ContentBundle]       Fixed writing security to page documents
    * HOTFIX      #2376 [ContentBundle]       Added cleanup for structure reindex provider
    * HOTFIX      #2382 [ResourceBundle]      Added column definitions to resource-bundle
    * HOTFIX      #2384 [WebsiteBundle]       Added condition to custom-routes to match only full-matches

* 1.2.2 (2016-05-09)
    * HOTFIX      #2375 [SecurityBundle]      Fixed visibility of entries in language dropdown
    * ENHANCEMENT #2373 [MediaBundle]         Added batch indexing for medias
    * HOTFIX      #2371 [MediaBundle]         Fixed appveyor tests for collections
    * HOTFIX      #2365                       Fixed missing and wrong method mocks
    * ENHANCEMENT #2359 [MediaBundle]         Added ability to sort medias
    * HOTFIX      #2368 [ContentBundle]       Fixed copying shadow properties
    * HOTFIX      #2362 [Website]             Fixed hreflang-tag for homepage
    * BUGFIX      #2364 [CoreBundle]          DependencyInjection: Throw exception when locales/translations are misconfigured
    * BUGFIX      #2364 [ResourceBundle]      Moved fixtures from de_CH to de_ch
    * HOTFIX      #2363 [WebsiteBundle]       Fixed sulu-content-path for webspaces with different domains for locales
    * ENHANCEMENT #2346 [ResourceBundle]      Added fixtures for de_CH
    * ENHANCEMENT #2346 [AdminBundle]         Use always users locale for globalize culture
    * HOTFIX      #2347 [ContentBundle]       Fixed ghost children loading

* 1.2.1 (2016-04-27)
    * HOTFIX      #2340 [ContactBundle]       Fixed listing of contacts with Sulu user
    * HOTFIX      #2334 [ContactBundle]       Fixed account-contact search
    * HOTFIX      #2335 [ContentBundle]       Fixed textarea vertical resize
    * HOTFIX      #2331 [AdminBundle]         Fixed admin-controller to return correct system
    * HOTFIX      #2330 [WebsiteBundle]       Removed lazy analyzing of the request
    * HOTFIX      #2321 [WebsiteBundle]       Fixed request-analyze for not existing current request
    * HOTFIX      #2324 [SecurityBundle]      Removed circular reference from website security
    * HOTFIX      #2319 [ContactBundle]       Validate unknown vat-number as valid
    * HOTFIX      #2306 [WebsiteBundle]       Fixed partial rendering using query parameter
    * HOTFIX      #2312 [Rest]                Added security checks to DoctrineListBuilder
    * HOTFIX      #2303 [ContactBundle]       Fixed contact media search
    * HOTFIX      #2304 [ContactBundle]       Fixed styling of options dropdown and fixed url-input dropdown
    * HOTFIX      #2290 [WebsiteBundle]       Fixed redirect urls for webspace
    * HOTFIX      #2294 [WebsiteBundle]       Fixed detecting webspaces for URLs with same priority
    * HOTFIX      #2294 [WebsiteBundle]       Fixed analytics with all domains only in created webspace
    * HOTFIX      #2285 [SecurityBundle]      Made ResettingController translations more configurable
    * HOTFIX      #2291 [ContentBundle]       Fixed wrong spacing between more than two checkboxes
    * ENHANCEMENT #2288 [WebsiteBundle]       Fixed overriding request attributes and set them on the request

* 1.2.0 (2016-04-11)
    * BUGFIX      #2280 [ContentBundle]       Removed scrollbar from categories in overlay
    * BUGFIX      #2281 [ContentBundle]       Use correct link for content tab in page form
    * BUGFIX      #2273 [ContentBundle]       Fixed link generation for internal link type without webspace
    * BUGFIX      #2274 [WebsiteBundle]       Fixed sulu-content-path with different webspace
    * BUGFIX      #2269 [WebsiteBundle]       Added query string to redirect for internal links
    * ENHANCEMENT #2189 [Travis]              Cache jackrabbit download
    * BUGFIX      #2272 [ContentBundle]       Fixed ordering of pages for columns including ghosts
    * BUGFIX      #2269 [WebsiteBundle]       Fixed domain switching in sulu-content path
    * BUGFIX      #2271 [ContentBundle]       Fixed internal link webspace locale bug
    * BUGFIX      #2267 [CategoryBundle]      Fixed collaboration component
    * BUGFIX      #2255 [WebsocketBundle]     Introduced own websocket app to avoid connecting to port 8843
    * BUGFIX      #2258 [WebsiteBundle]       Added validation of analytic type
    * BUGFIX      #2251 [MediaBundle]         Fixed filter media by symstem-collection and type
    * BUGFIX      #2252 [ContentBundle]       Fixed webspace in permission check
    * BUGFIX      #2244 [AdminBundle]         Fixed login with enter for Safari and IE
    * BUGFIX      #2245 [CustomUrlBundle]     Removed double wildcard for custom-url
    * BUGFIX      #2242 [MediaBundle]         Fixed leaking events after uploading new media version
    * BUGFIX      #2235 [ContentBundle]       Fixed validation of resource-segments
    * BUGFIX      #2238 [ContentBundle]       Fixed URL in SEO tab
    * BUGFIX      #2237 [MediaBundle]         Added locale to request for adding new media version
    * BUGFIX      #2236 [ContentBundle]       Fixed preview js errors

* 1.2.0-RC4 (2016-04-04)
    * BUGFIX      #2233 [ContentBundle]       Fixed resource locators for saving without locale
    * BUGFIX      #2232 [ContentBundle]       Updated condition to open ghost overlay
    * BUGFIX      #2229 [WebsiteBundle]       Fixed escaping of seo tags
    * BUGFIX      #2233 [CustomUrlBundle]     Fixed remove selected webspace-locale
    * ENHANCEMENT #2220 [ContentBundle]       Removed routable behavior and moved logic route-subscriber
    * BUGFIX      #2219 [ContentBundle]       Fixed changing template when disabling shadow page
    * ENHANCEMENT #2216 [ContentBundle]       Fixed hide add button if user has no add permission for webspace
    * ENHANCEMENT #2216 [All]                 Added KernelTestCase::assertHttpStatusCode method
    * BUGFIX      #2217 [MediaBundle]         Fixed ui-bugs in media-collections
    * ENHANCEMENT #2214 [WebsiteBundle]       Added website default locale providers
    * ENHANCEMENT #2208 [AdminBundle]         Added require-js url args to avoid wrong cache hits
    * ENHANCEMENT #2206 [WebsiteBundle]       Added security contexts to webspace settings
    * ENHANCEMENT #2206 [SnippetBundle]       Added security contexts to webspace settings
    * ENHANCEMENT #2206 [CustomUrlBundle]     Added security contexts to webspace settings
    * ENHANCEMENT #2209 [ContentBundle]       Added open-ghost overlay on change-locale in content-form
    * ENHANCEMENT #2211 [ContentBundle]       Improved translations and UI of seo tab
    * ENHANCEMENT #1980 [MediaBundle]         Sort assets by created date descending in lists
    * BUGFIX      #2193 [ContentBundle]       Ignore required properties on homepages during initialization.
    * BUGFIX      #2199 [SnippetBundle]       Fixed syntax mistake in snippet-controller
    * ENHANCEMENT #2204 [WebsiteBundle]       Enhanced custom-route creation
    * FEATURE     #2201 [All]                 Added collaboration message to all sulu core-bundles
    * ENHANCEMENT #2196 [AdminBundle]         Restructured admin-navigation
    * FEATURE     #2197 [MediaBundle]         Added media field credits
    * FEATURE     #2203 [WebsiteBundle]       Added host replacer to portal routes to support wildcard-urls
    * FEATURE     #2155 [MediaBundle]         Added media formats to masonry-view and edit-overlay
    * FEATURE     #2191 [All]                 Appveyor build for windows
    * FEATURE     #1288 [CustomUrlBundle]     Integrated custom-urls into analytics

* 1.2.0-RC3 (2016-03-29)
    * BUGFIX      #2190 [WebsiteBundle]       Fixed wrong translator locale by decorating translator
    * ENHANCEMENT #2192 [WebsiteBundle]       Added X-Generator HTTP header for Sulu website detection
    * ENHANCEMENT #2086 [ContentBundle]       Improved presentation of info-text

* 1.2.0-RC2 (2016-03-24)
    * BUGFIX      #2183 [ContentBundle]       Added missing locale for loading route document
    * BUGFIX      #2185 [MediaBundle]         Fixed throw exception if new version has a different media type
    * ENHANCEMENT #2182 [ContactBundle]       Added `sulu_resolve_contact` twig function
    * ENHANCEMENT #2182 [SecurityBundle]      Fixed `sulu_resolve_user` twig function to return a user instead of a contact
    * BUGFIX      #2178 [WebsiteBundle]       Added default IP anonymization for google analytics
    * ENHANCEMENT #2171 [CoreBundle]          Added validation of unused webspace locales
    * FEATURE     #2180 [MediaBundle]         Added fallback information for media assigments
    * BUGFIX      #2178 [WebsiteBundle]       Added default IP anonymization for google analytics
    * BUGFIX      #2171 [ContentBundle]       Fixed saving of homepage
    * BUGFIX      #2172 [CustomUrlBundle]     Added check for custom-url placeholder
    * BUGFIX      #2166 [WebsiteBundle]       Fixed analytics type change
    * ENHANCEMENT #2168 [WebsiteBundle]       Changed request to purge cache from GET to DELETE
    * BUGFIX      #2169 [CustomUrlBundle]     Fixed dropdown of custom-url target locales
    * BUGFIX      #2152 [ContentBundle]       Fixed not empty request body for delete history url
    * BUGFIX      #2141 [ContentBundle]       Fixed page gets immediately saved after generating URL
    * BUGFIX      #2156 [SecurityBundle]      Fixed behat context to create correct roles
    * BUGFIX      #2152 [ContentBundle]       Fixed not empty request body for delete history url
    * BUGFIX      #2157 [CustomUrlBundle]     Fixed route-validation in request processor
    * ENHANCEMENT #1288 [CoreBundle]          Introduced lazy initialization of request attributes
    * ENHANCEMENT #2132 [Test]                Removed external classes from and refactored functional test class hierarchy

* 1.2.0-RC1 (2016-03-18)
    * FEATURE     #1288 [All]                 Added deep-links for selection content-types
    * BUGFIX      #2131 [WebsiteBundle]       Fixed 'getTheme' error in ExceptionController
    * ENHANCEMENT #2131 [CoreBundle]          Added request attributes to extract data from request
    * ENHANCEMENT #2130 [MediaBundle]         Add support for newer symfony distributions with `bin/` directory
    * FEATURE     #2075 [All]                 Added CSV export for list responses
    * BUGFIX      #2128 [MediaBundle]         Fixed used language in media selection content type
    * BUGFIX      #2128 [All]                 Fix required version of PHP to support only ^5.5 and ^7.0
    * BUGFIX      #2126 [ContactBundle]       Excluded recursion in accounts REST API
    * BUGFIX      #2126 [All]                 Fixed firefox bug in label tick
    * FEATURE     #1927 [CustomUrlBundle]     Added custom-url feature
    * ENHANCEMENT #2122 [All]                 Disable xdebug on Travis to speed up composer and tests
    * ENHANCEMENT #2120 [All]                 Change bundle tests to use their own phpunit config and move `SYMFONY_DEPRECATIONS_HELPER` var into
    * BUGFIX      #2091 [MediaBundle]         Fixed routing when clicking of the data-navigation search-icon
    * ENHANCEMENT #2121 [All]                 Cache composer cache dir and prefer dist downloads on Travis
    * ENHANCEMENT #2114 [All]                 Update ffmpeg bundle and lib
    * ENHANCEMENT #2116 [All]                 Made restart of jackrabbit between tests configureable
    * ENHANCEMENT #2107 [WebsiteBundle]       Fixed portal redirect to local
    * FEATURE     #2099 [AdminBundle]         Implemented tab-conditions
    * BUGFIX      #2090 [MediaBundle]         Fixed fallback of media file-version meta
    * BUGFIX      #2092 [ContactBundle]       Fixed new contact when creating a new contact in the account
    * BUGFIX      #2103 [MediaBundle]         Fixed upload new version for media without thumbnail
    * BUGFIX      #2100 [ContactBundle]       Fixed switching tab in contact and account after save
    * ENHANCEMENT #2097 [TranslateBundle]     Fixed translation code length in database schema
    * BUGFIX      #2093 [ContactBundle]       Fixed auto-select new position and title
    * BUGFIX      #2094 [CategoryBundle]      Fixed maximum length of category-key
    * BUGFIX      #2082 [ContentBundle]       Fixed block type don't triggers save-button
    * ENHANCEMENT #2057 [ContentBundle]       Refactored ResourceLocator ContentType to use DocumentManager
    * ENHANCEMENT #2095 [WebsiteBundle]       Added security context for cache navigation entry
    * ENHANCEMENT #2082 [All]                 Get rid of the aliased evenement composer constraint
    * BUGFIX      #2088 [ContentBundle]       Fixed matrix for object permission tab
    * ENHANCEMENT #2035 [ContentBundle]       Add structure type to index
    * FEATURE     #2076 [All]                 Better content repository initialization, deprecated sulu:phpcr:init & sulu:webspaces:init
    * FEATURE     #2032 [CategoryBundle]      Added category keywords
    * BUGFIX      #2058 [ListBuilder]         Fixed cache for field-descriptor
    * ENHANCEMENT #2034 [ContentBundle]       Improved content-bundle testcases
    * ENHANCEMENT #2036 [SecurityBundle]      Introduced different permission types for different security contexts
    * ENHANCEMENT #2014 [Content]             Allow `-` in webspace name.
    * FEATURE     #1983 [ContentBundle]       Introduces hash check on save
    * FEATURE     #1983 [SnippetBundle]       Introduces hash check on save
    * ENHANCEMENT #1999 [SnippetBundle]       Snippet controller now uses DocumentManager and Serializer.
    * ENHANCEMENT #2008 [ContactBundle]       Fixed sorting in contact selection content type
    * ENHANCEMENT #1981 [ContentBundle]       Better search reindexing for structure content.
    * FEATURE     #2001 [MediaBundle]         Clear local image cache via cache clear service and command
    * BUGFIX      #1986 [All]                 Fixed naming of serializer properties
    * BUGFIX      #2006 [ContentBundle]       Show loading button after validating form
    * ENHANCEMENT #1987 [SecurityBundle]      Contact entity is required in User entity
    * BUGFIX      #1985 [CollaborationBundle] Removed leaking connections
    * ENHANCEMENT #1973 [All]                 Moved tests from /tests to component directories.
    * ENHANCEMENT #1970 [ContentBundle]       Changed get and put from NodeController to use DocumentManager
    * ENHANCEMENT #1956 [All]                 Removed Admin command registration
    * ENHANCEMENT #1956 [TranslateBundle]     Removed entry in admin navigation
    * BUGFIX      #1510 [Persistence]         Fetch user only if an entity with UserBlameInterface is detected
    * FEATURE     #1233 [CollaborationBundle] Showing current collaborators of pages
    * BUGFIX      #1944 [MediaBundle]         Removed wrong definition of indices
    * FEATURE     #1921 [ContentBundle]       Added unset single internal link
    * FEATURE     #1233 [ContentBundle]       Showing current collaborators of pages
    * ENHANCEMENT #1936 [Webspace]            Cleanup of WebsiteRequestAnalyzer
    * ENHANCEMENT #1937 [WebsiteBundle]       Removed unnecessary ob_clean in WebsiteController
    * BUGFIX      #1931 [ContentBundle]       Fixed form deprecation messages
    * BUGFIX      #1930 [ContentBundle]       Fixed updating values in combination
                                              with template change
    * FEATURE     #1912 [WebsiteBundle]       Added analytics to webspace settings
    * FEATURE     #1906 [All]                 Added PHP 7 support
    * FEATURE     #1922 [ContentBundle]       Added parameter to show toggler instead of checkbox
    * BUGFIX      #1926 [ContentBundle]       Fixed preview for non-standard page document
    * BUGFIX      #1874 [ContentBundle]       Fixed preview selector for blocks
    * FEATURE     #1777 [ContentBundle]       Added selection content types
    * BUGFIX      #1911 [SecurityBundle]      Fixed default locale user builder
    * BUGFIX      #1915 [All]                 Removed deprecations of initial admin request
    * FEATURE     #1851 [SnippetBundle]       Added default snippets webspace-settings
    * FEATURE     #1851 [ContentBundle]       Added webspace-settings
    * FEATURE     #1905 [All]                 Added french translation
    * BUGFIX      #1893 [ContentBundle]       Fixed resource locator deferred for edit
    * BUGFIX      #1871 [ContentBundle]       Fixed url-generation and save button
    * BUGFIX      #1873 [ContactBundle]       Fixed remove title and position
    * BUGFIX      #1873 [ContactBundle]       Fixed remove contact birthday

* 1.1.12 (2016-04-26)
    * HOTFIX      #2285 [SecurityBundle]    Made ResettingController translations more configurable

* 1.1.11 (2016-04-04)
    * HOTFIX      #2143 [ContactBundle]     Fixed account cget filtering of ids
    * HOTFIX      #2102 [ContactBundle]     Added filter for account tags

* 1.1.10 (2016-03-07)
    * HOTFIX      #2029 [WebsiteBundle]     Removed single alternate link in sitemap.xml
    * HOTFIX      #2029 [WebsiteBundle]     Fixed hreflang tag with one translation and different schemas
    * HOTFIX      #2046 [ContactBundle]     Added country-controller and use auto-complete for country
    * HOTFIX      #2074 [ListBuilder]       Added options for creating field-descriptors with meta-data
    * HOTFIX      #2053 [ContactBundle]     Added 'hasEmail' parameter to accounts api
    * HOTFIX      #2064 [ResourceBundle]    Fixed no option for invalid filter
    * HOTFIX      #2051 [ListBuilder]       Added metadata property to serialization process
    * HOTFIX      #2055 [ContactBundle]     Replaced span by input type hidden in address form
    * HOTFIX      #2058 [ListBuilder]       Fixed cache for field-descriptor
    * HOTFIX      #2024 [ContactBundle]     Fixed account add-contact-overlay enter bug and search for e-mail
    * HOTFIX      #2020 [ContactBundle]     Added account metadata
    * HOTFIX      #2000 [Filter]            Added filter metadata and new filter input types (tags, auto-complete)
    * ENHANCEMENT #2016 [AdminBundle]       Added loader to indicate loading suggestions
    * HOTFIX      #2002 [MediaBundle]       Fixed retina height for image scale command
    * HOTFIX      #2005 [WebsiteBundle]     Merge Twig globals and add output buffer handling for preview rendering
    * HOTFIX      #2003 [ContactBundle]     Fixed rendering of address with null title
    * HOTFIX      #1991 [Rest]              Added metadata for field-descriptors
    * BUGFIX      #1944 [MediaBundle]       Removed wrong definition of indices
    * HOTFIX      #2011 [AdminBundle]       Fixed double handling of login via enter
    * HOTFIX      #2023 [SecurityBundle]    Set the user language for requests in backend

* 1.1.9 (2016-02-05)
    * ENHANCEMENT #1978 [SecurityBundle]    Made url for resetting password configurable via static variable
    * HOTFIX      #1976 [MediaBundle]       Moved delete collection to drop-down to avoid misunderstandings
    * HOTFIX      #---  [AdminBundle]       Updated husky to fix rendering preselected bug

* 1.1.8 (2016-02-01)
    * HOTFIX      #1962 [ListBuilder]       Fixed search generation with case-field descriptors
    * HOTFIX      #1962 [ContactBundle]     Fixed contact birthday trigger save-button
    * HOTFIX      #1958 [SnippetBundle]     Fixed bug with snippet in snippet
    * HOTFIX      #1953 [SecurityBundle]    Added UI to enable user in sulu admin

* 1.1.7 (2016-01-26)
    * HOTFIX      #1952 [PersistenceBundle] Fixed mapped superclass inheritance
    * HOTFIX      #1950 [Rest]              Added possibility to disable the GROUP BY clause

* 1.1.6 (2016-01-26)
    * HOTFIX      #1948 [AdminBundle]     Updated husky for required validation fix
    * HOTFIX      #1938 [ContactBundle]   Added missing namespace declerations for fixtures
    * HOTFIX      #1938 [MediaBundle]     Added missing namespace declerations for fixtures
    * HOTFIX      #1938 [SecurityBundle]  Added missing namespace declerations for fixtures

* 1.1.5 (2016-01-15)
    * HOTFIX      #1933 [AdminBundle]     Fixed password reset twig template

* 1.1.4 (2016-01-08)
    * HOTFIX      #1917 [MediaBundle]     Changed version name to original filename

* 1.1.3 (2015-12-18)
    * HOTFIX      #1903 [ContentBundle]   Prohibited to follow empty or self internal link
    * HOTFIX      #1900 [ContentBundle]   Prohibited to link page to itself
    * HOTFIX      #1898 [MediaBundle]     Fixed dangling events of media-overlay
    * HOTFIX      #1872 [ContentBundle]   Fixed sql generation for user roles in ContentRepository
    * HOTFIX      #1888 [ContentBundle]   Used auto_name in phpcr migrations
    * HOTFIX      #1899 [ContentBundle]   Fixed directory separator for windows
    * HOTFIX      #1895 [ResourceBundle]  Fixed error handling of filters

* 1.1.2 (2015-12-11)
    * HOTFIX      #1831 [MediaBundle]     Fixed query for retrieving entities to index
    * HOTFIX      #1868 [ContentBundle]   Added date upgrade script for blocks
    * HOTFIX      #1869 [CategoryBundle]  Fixed opened category tree on startup
    * HOTFIX      #1799 [ContentBundle]   Added 'published' field to be indexed
    * HOTFIX      #1866 [WebsiteBundle]   Added scheme to sitemap url generation
    * HOTFIX      #1861 [ContentBundle]   Added upgrade script which removes non-translated properties

* 1.1.1 (2015-12-07)
    * HOTFIX      #1857 [ContentBundle]   Fixed open ghost overlay
    * HOTFIX      #1857 [WebsiteBundle]   Fixed sitemap xml generation to improve performance
    * HOTFIX      #1859 [MediaBundle]     Fixed media-query if no search isset
    * HOTFIX      #1855 [ContactBundle]   Fixed displaying correct position when adding new person to organisation
    * HOTFIX      #1856 [MediaBundle]     Fixed delete copyright from media
    * HOTFIX      #1856 [All]             Changed datagrid-pagination to input field to avoid performance leaks

* 1.1.0 (2015-12-02)
    * BUGFIX      #1849 [MediaBundle]     Fixed media-edit-overlay language changer
    * BUGFIX      #1846 [CoreBundle]      Fixed name of type map config parameter
    * BUGFIX      #1847 [ContentBundle]   Removed disabler from account form
    * BUGFIX      #1844 [ContactBundle]   Fixed dimensions and position of contact avatar
    * ENHANCEMENT #1843 [ContactBundle]   Added new field-descriptors for accounts and contacts (zip, state, country,..)
    * ENHANCEMENT #1842 [ContentBundle]   Fixed title generation to ignore checkboxes
    * BUGFIX      #1839 [ResourceBundle]  Fixed filter-selection of non supported types
    * ENHANCEMENT #1841 [ContentBundle]   Extended path-replacers xml file
    * BUGFIX      #1837 [ResourceBundle]  Fixed filter-result bar when filtering has no results
    * BUGFIX      #1836 [ContentBundle]   Fixed preview to save before render and avoid over writing cached values
    * BUGFIX      #1795 [ContentBundle]   Fixed copying shadow pages with urls
    * BUGFIX      #1830 [ContentBundle]   Fixed load data in correct locale for excerpt
    * BUGFIX      #1826 [ContentBundle]   Fixed preselected select elements null
    * BUGFIX      #1829 [ContentBundle]   Refactored url and content type handling
    * BUGFIX      #1824 [CategoryBundle]  Fixed category-list scroll behaviour
    * BUGFIX      #1823 [TagBundle]       Added tag serialization groups to tag controller
    * ENHANCEMENT #1806 [All]             Added sticky toolbar to content lists
    * FEATURE     #1808 [ContentBundle]   Implemented content repository to query simple content fast
    * ENHANCEMENT #1822 [MediaBundle]     Preview image upload for video assets
    * BUGFIX      #1820 [ContentBundle]   Fixed migrate url script
    * BUGFIX      #1815 [SecurityBundle]  Fixed missing locale check for security
    * ENHANCEMENT #1816 [MediaBundle]     Added play button to video assets list
    * BUGFIX      #1811 [MediaBundle]     Normalize file names to avoid error in video preview image generation
    * BUGFIX      #1812 [ContactBundle]   Fixed translation bug in position dropdown
    * BUGFIX      #1814 [MediaBundle]     Fixed media selection bug
    * BUGFIX      #1807 [SecurityBundle]  Removed user locked field and used contact disabled field instead
    * ENHANCEMENT #1806 [ContentBundle]   Fixed serialization depth for column-navigation
    * BUGFIX      #1800 [ContentBundle]   Fixed preview update of date and color
    * BUGFIX      #1801 [ContentBundle]   Fixed reset values in smart content if values was selected before
    * ENHANCEMENT #1805 [ContentBundle]   Fixed validation of external node type and format of warning message
    * BUGFIX      #1796 [ContentBundle]   Fixed content type date to save as date data-type in phpcr
    * BUGFIX      #1802 [ContentBundle]   Fixed appearances of content tabs
    * BUGFIX      #1793 [TagBundle]       Fixed tag-list preview update
    * BUGFIX      #1793 [CategoryBundle]  Fixed category-list preview update
    * BUGFIX      #1790 [MediaBundle]     Fixed collection twice after edit collection
    * BUGFIX      #1792 [WebsiteBundle]   Fixed alternate links with custom x-default locale and remove links to
                                          homepage
    * FEATURE     #1712 [MediaBundle]     Added media data-provider
    * ENHANCEMENT #1779 [ContactBundle]   Added title to address list and fixed style of title
    * BUGFIX      #1794 [ContactBundle]   Fixed account-contact allocation with position
    * ENHANCEMENT #1786 [ContactBundle]   Added cascade options to account and contact
    * BUGFIX      #1789 [CoreBundle]      Added replacers for box brackets
    * BUGFIX      #1785 [ContentBundle]   Fixed data returned for internal link in settings tab
    * BUGFIX      #1784 [ContentBundle]   Removed webspace as required parameter for get action
    * BUGFIX      #1783 [MediaBundle]     Fixed not removed deleted original files
    * ENHANCEMENT #1782 [SnippetBundle]   Added ghost content parameter to getSnippetsByUuids in repository
    * BUGFIX      #1764 [MediaBundle]     Added no cache headers for 404 thumbnails
    * BUGFIX      #1778 [MediaBundle]     Fixed cache handling of system collections
    * ENHANCEMENT #1775 [SmartContent]    Add possibility to get ArrayAccessItem as json
    * ENHANCEMENT #1772 [SbippetBundle]   Added translated template to snippet list
    * BUGFIX      #1761 [MediaBundle]     Reduced the number of requests in the collection view
    * BUGFIX      #1736 [AdminBundle]     Fixed layout for content form
    * ENHANCEMENT #1760 [SecurityBundle]  Added missing joins on query for security
    * BUGFIX      #1759 [ContactBundle]   Fixed upload avatar when category is selected
    * BUGFIX      #1750 [SecurityBundle]  Added seraialization groups for user
    * BUGFIX      #1756 [ContentBundle]   Added excerpt values (title, description, tags and categories) to search
                                          indexing
    * ENHANCEMENT #1754 [MediaBundle]     Moved collection key functions to base collection
    * BUGFIX      #1751 [Persistence]     Fixed UserBlameSubscriber for new DoctrineBundle
    * ENHANCEMENT #1746 [Rest]            Use * as placeholder in ListBuilder search
    * FEATURE     #1749 [SnippetBundle]   Enabled search in snippet selection overlay
    * ENHANCEMENT #1743 [SmartContent]    Fixed param names for website operators
    * FEATURE     #1739 [MediaBundle]     Implemented system collections
    * BUGFIX      #1733 [ContentBundle]   Added empty locale condition to fix empty locale bug
    * ENHANCEMENT #1719 [SearchBundle]    Updated search to only return granted documents
    * BUGFIX      #1733 [ContactBundle]   Added delete warning and download icon for contact avatar and account logo
    * BUGFIX      #1733 [MediaBundle]     Fixed a few media-selection bugs
    * ENHANCEMENT #1605 [MediaBundle]     Adjust media-selection-overlay title
    * BUGFIX      #1716 [ContactBundle]   Fixed wrong contact entity identifier
    * ENHANCEMENT #1717 [Persistence]     Improved performance of MetadataSubscriber
    * FEATURE     #1611 [All]             Improved PHPCR content handling to allow custom PHPCR content
    * ENHANCEMENT #1706 [MediaBundle]     Changed download link in media section to real link
    * BUGFIX      #1714 [ContentBundle]   Fixed migration for url scheme
    * BUGFIX      #1713 [MediaBundle]     Fixed drag and drop behavior in collection view
    * ENHANCEMENT #1704 [AdminBundle]     Protected login from CSRF attacks
    * BUGFIX      #1702 [MediaBundle]     Fixed selected handling in media selection overlay
    * BUGFIX      #1685 [ContactBundle]   Fixed delete logo/avatar from collection and form
    * FEATURE     #1697 [MediaBundle]     Replaced StreamedResponse with BinaryFileResponse
    * BUGFIX      #1701 [ContentBundle]   Added website cache clear button in preview toolbar
    * ENHANCEMENT #1700 [CategoryBundle]  Added category translate fallbacks
    * BUGFIX      #1696 [MediaBundle]     Fixed dropzone for uploading new versions of media
    * BUGFIX      #1693 [All]             Fixed behat tests
    * BUGFIX      #1675 [ContactBundle]   Fixed null value for smart content
    * FEATURE     #1653 [MediaBundle]     Added generation of thumbnails for videos
    * BUGFIX      #1688 [ContentBundle]   Fixed doctrine cache size for preview with delete on navigate
    * BUGFIX      #1687 [MediaBundle]     Fixed media-selection overlay responsive and datagrid styles
    * BUGFIX      #1692 [MediaBundle]     Fixed maximum file size of dropzone
    * FEATURE     #1598 [MediaBundle]     Added infinite-scroll pagination for masonry-view
    * FEATURE     #1598 [ContactBundle]   Added infinite-scroll pagination for card-view
    * BUGFIX      #1670 [ContentBundle]   Fixed missing url-scheme in content type
    * FEATURE     #1683 [MediaBundle]     Added copyright to media metadata
    * BUGFIX      #1673 [ContentBundle]   Fixed settings tag for shadows on external links
    * BUGFIX      #1678 [ContactBundle]   Fixed contact-selection serialization
    * BUGFIX      #1667 [ContactBundle]   Removed the restriction of start dates from the datepicker
    * BUGFIX      #1671 [ContentBundle]   Fixed block sorting for blocks with only one type
    * BUGFIX      #1668 [ContentBundle]   Fixed smart content for usage with block property
    * BUGFIX      #1665 [ContentBundle]   Fixed creation of new url after template change
    * BUGFIX      #1666 [ContentBundle]   Fixed min occurs 0 for blocks
    * BUGFIX      #1664 [SearchBundle]    Fixed search-results deep links
    * BUGFIX      #1660 [ContentBundle]   Added separate error message for occupied resource locator
    * BUGFIX      #1655 [ContentBundle]   Fixed ghost pages and phpcr access control provider
    * FEATURE     #1606 [SmartContent]    Added filter by categories to SmartContent Component
    * FEATURE     #1606 [CategoryBundle]  Added TwigExtension to handle categories in twig templates
    * BUGFIX      #1654 [ContentBundle]   Added more path replacers
    * BUGFIX      #1656 [ContentBundle]   Fixed preview nested properties
    * BUGFIX      #1656 [ContentBundle]   Fixed preview property attributes
    * BUGFIX      #1649 [ContentBundle]   Fixed floating of block type select
    * BUGFIX      #1650 [LocationBundle]  Fixed configure overlay open multiple times
    * BUGFIX      #1650 [ContentBundle]   Fixed serialization of null values
    * BUGFIX      #1650 [ContentBundle]   Fixed show ghost and shadow toggler in content column view
    * BUGFIX      #1646 [ContactBundle]   Fixed upload of contact-avatar when a position is applied to the contact
    * BUGFIX      #1645 [CategoryBundle]  Removed automatic category translation
    * FEATURE     #1644 [MediaBundle]     Show collection UI elements based on security
    * BUGFIX      #1642 [AdminBundle]     Fixed cropping issue in table-view of datagrid
    * BUGFIX      #1641 [MediaBundle]     Fixed types in media-selection
    * ENHANCEMENT #1638 [ContentBundle]   Removed unnecessary variable in content form
    * FEATURE     #1643 [ContactBundle]   Added title of address
    * FEATURE     #1584 [ContentBundle]   Show UI elements in content management based on security
    * BUGFIX      #1633 [Rest]            Listbuilder: fix for concat joins in where clause;
                                          Fix in sort (order by id as default)
    * BUGFIX      #1625 [MediaBundle]     Fixed queries for PostgreSQL
    * ENHANCEMENT #1626 [AdminBundle]     Added require-css to load css dynamically with require
    * BUGFIX      #1619 [CategoryBundle]  Fixed category view cropping responsive
    * BUGFIX      #1619 [AdminBundle]     Fixed responsive interface with sidebar
    * BUGFIX      #1614 [ContentBundle]   Readded the reorder event to the OrderSubscriber
    * ENHANCEMENT #1607 [ContactBundle]   Now passing savedData when calling save and create new account
    * BUGFIX      #1609 [MediaBundle]     Added further null reference checks to MediaManager
    * ENHANCEMENT #1593 [ResourceBundle]  Added configurable avatar collection for contact and account form
    * ENHANCEMENT #1583 [ContactBundle]   Added no image icon to table view
    * BUGFIX      #1583 [ResourceBundle]  Fixed filter button in list-toolbar
    * ENHANCEMENT #1581 [CategoryBundle]  Added locale handling in list and list api
    * ENHANCEMENT #1581 [SnippetBundle]   Added locale chooser in list
    * FEATURE     #1347 [ContactBundle]   Content type for contacts and accounts
    * FEATURE     #1558 [AdminBundle]     Redesign of overlays
    * FEATURE     #1557 [MediaBundle]     Redesign of data-navigation
    * FEATURE     #1557 [AdminBundle]     Success-labels in navigation
    * FEATURE     #1544 [ContentBundle]   Highlighted section in content-edit
    * FEATURE     #1541 [ContentBundle]   Redesign of preview
    * FEATURE     #1540 [ContentBundle]   Redesign of content-blocks
    * FEATURE     #1530 [ContentBundle]   Redesign different content-types
    * FEATURE     #1543 [MediaBundle]     New view for collection-list
    * ENHANCEMENT #1543 [MediaBundle]     Changed front-end bundle structure with the use of services
    * FEATURE     #1481 [ContactBundle]   Titles in JS-views
    * FEATURE     #1481 [AdminBundle]     Extension-hook for loading data in JS-files
    * FEATURE     #1476 [ContactBundle]   Avatar upload via dropzone
    * FEATURE     #1478 [ContactBundle]   Rest-Api for media lists for contacts and accounts
    * FEATURE     #1467 [ContactBundle]   New view for contacts- and accounts-edit
    * FEATURE     #1431 [ContactBundle]   New view for contacts-list
    * ENHANCEMENT #1444 [ContactBundle]   changed front-end bundle structure with the use of services
    * ENHANCEMENT #1421 [AdminBundle]     New button-api for header and tabs
    * ENHANCEMENT #1417 [AdminBundle]     Style upgrade for header and tabs
    * FEATURE     #1472 [ContactBundle]   Rest-Api support for contact-avatars
    * FEAETURE    #1503 [All]             Updated to doctrine 2.5
    * ENHANCEMENT #1550 [ContactBundle]   Added flat response option to accountContact collection in js
    * ENHANCEMENT #1523 [ContactBundle]   Refactored config to make it reusable by other bundles
    * FEATURE     #1522 [SecurityBundle]  Created OrderByTrait for Repositories to sort by given array data
    * ENHANCEMENT #1522 [SecurityBundle]  Added sorting option for findUserByAccount in UserRepository
    * BUGFIX      #1508 [AdminBundle]     Fixed bug with serializing user settings
    * FEATURE     #1505 [All]             Added 'fullContact' serialization-groups in contact entity to all relations
    * BUGFIX      #1501 [ContentBundle]   Fixed caching when ttl is 0
    * FEATURE     #1529 [ContentBundle]   Added reset smart content button
    * FEATURE     #1517 [ContactBundle]   Added account DataProvider
    * FEATURE     #1513 [ContactBundle]   Added contact DataProvider
    * FEATURE     #1512 [SmartContent]    Extended SmartContent to be able to add tags from website URL
    * FEATURE     #1512 [TagBundle]       Added TwigExtension to handle tags in twig templates
    * FEATURE     #1369 [ContentBundle]   Show icons in column navigation based on the user's permission
    * FEATURE     #1415 [ContentBundle]   Refactored SmartContent to use DataProvider to load content
    * FEATURE     #1477 [MediaBundle]     Added object security in the media section
    * BUGFIX      #1462 [Rest]            Fixed type of returned value for the Doctrine list builder count method
    * BUGFIX      #1437 [SnippetBundle]   Fixed copy-locale overlay bug
    * FEATURE     #1424 [All]             Implemented and integrated expressions for the listbuilder
    * FEATURE     #1429 [ResourceBundle]  Updated husky and added preselect for filter dropdown
    * BUGFIX      #1411 [All]             Only take inner-joins into account that are referenced to selected entity +
                                          Added Tests
    * FEATURE     #1406 [ResourceBundle]  Integrated filters into user settings
    * FEATURE     #1406 [SecurityBundle]  Added api method to delete user settings
    * FEATURE     #1404 [AdminBundle]     Implemented new login design
    * BUGFIX      #1388 [ContactBundle]   Fixed issue with multiple instances of the contact-form component
    * BUGFIX      #1402 [AdminBundle]     Fixed sorting of datagrid
    * FEATURE     #1398 [SecurityBundle]  Integrated filters for roles
    * FEATURE     #1398 [TagBundle]       Integrated filters for tags
    * FEATURE     #1370 [SnippetBundle]   Added copy-locale for snippet UI
    * FEATURE     #1362 [MediaBundle]     Added resolve media twig extension
    * ENHANCEMENT #1373 [CoreBundle]      Performance improvement of ListBuilder: first select ids by filter conditions
                                          then select data
    * ENHANCEMENT #1367 [AdminBundle]     Added new tabs design
    * ENHANCEMENT #1358 [AdminBundle]     Added new grid-style and refactored list in all affected bundles
    * ENHANCEMENT #1368 [ResourceBundle]  Changed handling of conjunctions for filters
    * BUGFIX      #1334 [GeneratorBundle] Fixed twig error due to missing templates
    * ENHANCEMENT #1363 [ContactBundle]   Removed old config from filter config
    * ENHANCEMENT #1353 [ContactBundle]   Integrated custom filters for account-list
    * ENHANCEMENT #1310 [ContactBundle]   Integrated custom filters for contact-list
    * ENHANCEMENT #1356 [ContactBundle]   Fixed getContactsByUserSystem function
    * ENHANCEMENT #1345 [ContactBundle]   Added VAT validation for switzerland
    * ENHANCEMENT #1341 [SecurityBundle]  Excluded user-roles from role-api serialization
    * BUGFIX      #1191 [AdminBundle]     Fixed unique Navigation ID
    * ENHANCEMENT #1342 [SecurityBundle]  Added creator and changer of contact to 'fullContact' serialization group
    * BUGFIX      #1365 [ContactBundle]   Fixed bug caused by new instance name of datagrid
    * BUGFIX      #1136 [MediaBundle]     Fixed image scale forceRatio parameter for none squared image formats
    * BUGFIX      #1785 [ContentBundle]   Fixed data returned for internal link in settings tab

* 1.0.15 (2016-01-08)
    * HOTFIX      #1919 [MediaBundle]    Fixed remove image description
    * ENHANCEMENT #1919 [LocationBundle] Added de and ch to google maps selection

* 1.0.14 (2015-11-13)
    * BUGFIX      #1191 [AdminBundle]    Fixed unique Navigation ID

* 1.0.13 (2015-11-12)
    * HOTFIX      #1771 [AdminBundle]    Fixed login translations if browser-locale is not translated

* 1.0.12 (2015-10-22)
    * HOTFIX      #1634 [SecurityBundle] Allow attribute overrides for user email field
    * HOTFIX      #1624 [ContentBundle]  Fixed nullable internal link and added server/clientside validation
    * BUGFIX      #1668 [ContentBundle]  Fixed smart content for usage with block property

* 1.0.11 (2015-09-23)
    * HOTFIX      #1596 [GeneratorBundle] Fixed sulu bundle generator path generation
    * HOTFIX      #1615 [Content]         Fixed resource segment subscriber for order internal link
    * HOTFIX      #1612 [Content]         Fixed non copy content of snippet

* 1.0.10 (2015-09-17)
    * HOTFIX      #1594 [Website] Fixed website request analyzer
    * HOTFIX      #1594 [Website] Fixed trailing slash for homepage

* 1.0.9 (2015-09-14)
    * HOTFIX      #1572 [ContentBundle]         Fixed select overlay for internal link node type
    * HOTFIX      #1567 [Document]              Fixed localized property for url property
    * HOTFIX      #1568 [ContentBundle]         Fixed copy extension data
    * HOTFIX      #1577 [DocumentManagerBundle] Made caching directory for DocumentManager configureable

* 1.0.8 (2015-08-31)
    * HOTFIX      #1539 [WebsiteBundle]  Fixed canonical tag for shadow pages
    * HOTFIX      #1537 [WebsiteBundle]  Fixed format of hreflang-tag locale
    * HOTFIX      #1532 [WebsiteBundle]  Fixed redirect to external pages
    * HOTFIX      #1511 [ContentBundle]  Fixed single-internal-link overlay URL
    * HOTFIX      #1521 [MediaBundle]    Fixed media-selection events for preview update

* 1.0.7 (2015-08-11)
    * HOTFIX      #1469 [ContentBundle]  fixed displayOptions in media selection
    * HOTFIX      #1468 [SnippetBundle]  Fixed default language for snippets in administration

* 1.0.6 (2015-08-05)
    * HOTFIX      #1448 [AdminBundle]    Fixed additional system languages

* 1.0.5 (2015-08-03)
    * HOTFIX      #--   [AdminBundle]    Fixed ckeditor overlay buttons for windows

* 1.0.4 (2015-07-28)
    * HOTFIX      #1427 [ContentBundle]  Added external link migration
    * HOTFIX      #1419 [ContentBundle]  Fixed tags, categories and navigation context for shadow pages

* 1.0.3 (2015-07-23)
    * HOTFIX      #1394 [MediaBundle]    Added regex replace for media download to avoid umlauts error
    * HOTFIX      #1391 [All]            Removed partial load hints
    * HOTFIX      #1393 [SnippetBundle]  Added try-catch to avoid exception for snippet load
    * HOTFIX      #1395 [ContentBundle]  Fixed cache-lifetime is required bug for lifetime "0"
    * HOTFIX      #1386 [ContentBundle]  Fixed hard-coded values in search metadata
    * HOTFIX      #1400 [SnippetBundle]  Fixed block sorting in snippet form
    * HOTFIX      #1378 [ContentBundle]  Fixed sorting of pages
    * HOTFIX      #1414 [ContentBundle]  Set published property in resolved smart content to date instead boolean

* 1.0.2 (2015-07-13)
    * HOTFIX      #1355 [CoreBundle]     Fixed creator id for website document
    * HOTFIX      #1346 [ContentBundle]  Reversed order of paths to enable overriding of templates again
    * HOTFIX      #1366 [CoreBundle]     Fixed build command for not existing database

* 1.0.1 (2015-07-06)
    * HOTFIX      #1338 [Content]        Fixed wrong check for block type meta title

* 1.0.0 (2015-07-01)
    * ENHANCEMENT #1319 [ContentBundle]  Fixed location of cached structures
    * BUGFIX      #1316 [ContentBundle]  Reenabled csrf protection and disabled it only for the content mapper
    * FEATURE     #1314 [SecurityBundle] Enable new persistence handling for User & Role.
    * FEATURE     #1314 [AdminBundle]    Added search component to the sulu dashboard
    * BUGFIX      #1313 [MediaBundle]    Fixed image conversion in environments where open_basedir is enabled
    * BUGFIX      #1308 [ContactBundle]  Fixed adding a new contact-account relation in contacts tab of account
    * ENHANCEMENT #1302 [MediaBundle]    Removed 'partial-load' hint from getMediaById repository function.
    * BUGFIX      #1172 [ContentBundle]  Fix hreflang meta tags, remove invalid title meta tag
    * BUGFIX      #1172 [ContentBundle]  Fixed ckeditor in blocks for IE11
    * ENHANCEMENT #1040 [ContentBundle]  Added validation for required tag name
    * FEATURE     #1278 [ContentBundle]  Implemented webspace structure provider
    * FEATURE     #1273 [SnippetBundle]  Removed snippet state from ui and set default published
    * FEATURE     #1281 [ContentBundle]  Added default-template config to webspace theme
    * FEATURE     #1315 [ContentBundle]  Fixed translator locale for preview
    * BUGFIX      #1023 [ContactBundle]  Fixed back to list in contact-list

* 1.0.0-RC3 (2015-06-24)
    * HOTFIX      #1306 [ContentBundle]  Fixed migration commands for jackrabbit
    * ENHANCEMENT #1260 [All]            Removed or renamed all old update commands
    * ENHANCEMENT #1090 [All]            Introduced DocumentManager
    * BUGFIX      #1295 [ContentBundle]  Fixed call of changed event from MassiveSearchBundle
    * ENHANCEMENT #1230 [ContactBundle]  Introduced the new `PersistenceBundle` which makes entities easy replaceable.
                                         Added this functionality for the contact entity.
    * BUGFIX      #1276 [ContentBundle]  Fixed smart-content datasource-select by change request url
    * FEATURE     #1264 [MediaBundle]    Added link to original image in media edit-overlay

* 1.0.0-RC2 (2015-06-17)
    * BUGFIX      #1264 [ContentBundle]  Fixed save of changed block type
    * BUGFIX      #1259 [ContentBundle]  Fixed internal link assignment delete
    * BUGFIX      #1244 [WebsiteBundle]  Updated LiipThemeBundle to get assetic bugfix
    * BUGFIX      #1254 [SnippetBundle]  Fixed snippet assigment delete
    * BUGFIX      #1250 [ContactBundle]  Fixed document assigment delete in contact area
    * ENHANCEMENT #1251 [SecurityBundle] Refactored PasswordResetting controller for better reusability
    * BUGFIX      #1253 [MediaBundle]    Improved speed for media list query
    * BUGFIX      #1245 [ContentBundle]  Ensure that concrete languages will be serialized as array not as object
    * FEATURE     #1248 [ContentBundle]  Added cleanup resource-locator history command
    * BUGFIX      #1243 [ContentBundle]  Added ignore of ghost pages when content copy locale
    * ENHANCEMENT #1234 [All]            Prefix twig extension functions with "sulu_"
    * ENHANCEMENT #1237 [AdminBundle]    Fixed typos in behat tests
    * BUGFIX      #1235 [ContentBundle]  Fixed delete page which has children with history url
    * BUGFIX      #1231 [ContentBundle]  Fixed wrong behaviour if you edit a shadow page
    * BUGFIX      #1216 [SecurityBundle] Moved settings action to non-secured ProfileController
    * BUGFIX      #1213 [ContentBundle]  Fixed redirect of external links
    * FEATURE     #1214 [MediaBundle]    Added language chooser in "all media" view and in edit-media overlay
    * BUGFIX      #1211 [WebsiteBundle]  Fixed merge of test-page childs into upper layer in website navigation
    * ENHANCMENT  #1206 [SecurityBundle] Corrected translation for roles entry in navigation
    * BUGFIX      #1203 [AdminBundle]    Fixed routes for tabs
    * BUGFIX      #1199 [ContentBundle]  URL of shadow pages are not delivered in the urls array
    * BUGFIX      #1207 [ContentBundle]  Added additional query before generate new url
    * BUGFIX      #1188 [MediaBundle]    Fixed new fileversion thumbnail update
    * BUGFIX      #1169 [AdminBundle]    Fixed sidebar issue (prepending div instead of appending)
    * ENHANCEMENT #1159 [SecurityBundle] Change role naming to keep symfony2 conventions.
    * BUGFIX      #1156 [MediaBundle]    Fix mimetype check for ghostscript
    * BUGFIX      #1163 [ContentBundle]  Set existing default for content language

* 1.0.0-RC1 (2015-05-29)
    * ENHANCEMENT #1148 [SecurityBundle] Moved user specific code from UserController to UserManager
    * BUGFIX      #1147 [MediaBundle]    Fixes fileVersion created date
    * ENHANCEMENT #1134 [MediaBundle]    Add parameter to view pdf in browser instead of downloading it immediately
    * ENHANCEMENT #1055 [MediaBundle]    Use tagged services instead of prefix for image converter commands
    * ENHANCEMENT #1144 [CacheBundle]    Changed dependencies from guzzle and HTTPCacheBundle
    * BUGFIX      #1141 [WebsiteBundle]  Added smaller version of logo and fixed twig syntax errors for profiler
    * BUGFIX      #1075 [WebsiteBundle]  Fixed sitemap add validation for requested domain
    * BUGFIX      #1124 [ContentBundle]  Fixed preview with multiple blocks
    * BUGFIX      #1123 [ContentBundle]  Fixed block behaviour on template change
    * ENHANCEMENT #1118 [SecurityBundle] Add possibility to enable SecurityChecker and SuluSecurityListener via configuration
    * ENHANCEMENT #1113 [ContactBundle]  Added sorting by last-name in accounts-contact tab
    * ENHANCEMENT #1100 [ContentBundle]  Replaced the checkboxes with radio buttons in overlay for creating node in new localization
    * ENHANCEMENT #1088 [ContactBundle]  Moved initialization of field-descriptors before init of list-builder in
                                         accounts cget action
    * ENHANCEMENT #1053 [Util]           Remove unused UuidUtils class
    * ENHANCEMENT #1038 [MediaBundle]    Added counter for selected images; Disabled drag event for links and
                                         images inside the overlay; Store media assignement display options in user settings
    * BUGFIX      #1051 [Website]        Throw NoValidWebspaceException if no valid webspaces are found
    * BUGFIX      #1089 [Media/Search]   Do not set image URL for non-images in the search results
    * BUGFIX      #996  [ContentBundle]  Fixed change language in add form
    * BUGFIX      #725  [Webspace]       Fixed trailing slash in defining url in webspace config

* 0.18.2 (2015-05-18)
    * HOTFIX      #1094 [MediaBundle]    Fixed media overlay version tab appearance

* 0.18.1 (2015-05-09)
    * HOTFIX      #1079 [SearchBundle]   Fix webspace-key index for content pages

* 0.18.0 (2015-05-08)
    * ENHANCEMENT #797  [SearchBundle]   Rebuild command removed, now hooks into massive:search:index:rebuild
    * ENHANCEMENT #797  [SearchBundle]   Unpublished pages are no longer deindexed - a "state" field has been added, see UPGRADE.md
    * ENHANCEMENT #797  [ContactBundle]  Contacts and Accounts have massive search mappings
    * ENHANCEMENT #1076 [AdminBundle]    Moved some translations from admin-bundle to their specific bundles
    * ENHANCEMENT #1057 [All]            Upgrade of jackalope 1.2
    * BUGFIX      #1072 [ContentBundle]  Cropping of long rlps in history overlay
    * BUGFIX      #1067 [SecurityBundle] Increase locale db field for big locale jsons
    * BUGFIX      #1065 [AdminBundle]    Second try: Fixed 1Password css bug on login screen
    * BUGFIX      #--   [AdminBundle]    Fixed login for IE see [commit](https://github.com/sulu-io/sulu/commit/a50e48aa83d360b93b5db0a63300c2799d3bc8ab)
    * BUGFIX      #1045 [MediaBundle]    Fixed upload new media version
    * FEATURE     #496  [ContentBundle]  SmartContent: change default tag filter to OR operation and user can decide to use OR or AND
    * ENHANCEMENT #1039 [ContactBundle]  Auto select new title or positions in contact form
    * ENHANCEMENT #--   [MediaBundle]    Added function to get base media types
    * ENHANCEMENT #1031 [MediaBundle]    Fixed success label for collection delete
    * FEATURE     #977  [MediaBundle]    Made Format Cache parameters configurable, prefix ghostscript path parameter with sulu_media.
    * BUGFIX      #1037 [ContentBundle]  Fixed preview renderer exception handling and removed global error handling
    * BUGFIX      #945  [WebsiteBundle]  Fix Redirect url with query string correctly and trailing slash
    * ENHANCEMENT #1029 [All]            Removed prefixes from content navigation providers and admins
    * FEATURE     #1014 [MediaBundle]    Added media preview in edit overlay
    * BUGFIX      #1026 [MediaBundle]    Fixed collection and category behat tests
    * BUGFIX      #1030 [WebsiteBundle]  Fixed exception-controller to resolve parameters like website-controller
    * FEATURE     #1030 [WebsiteBundle]  Added configuration for error templates to webspace-config
    * BUGFIX      #1044 [ContentBundle]  Update CKEditor parameters to snake_case and allow dynamic override of ckeditor config

* 0.17.0 (2015-04-20)
    * BUGFIX      #1020 [ContactBundle]  Fixed organization go back type bug
    * BUGFIX      #1021 [SecurityBundle] Only check security for not-null security contexts
    * BUGFIX      #1009 [MediaBundle]    Fix media download url
    * ENHANCEMENT #999  [ContentBundle]  Show permission tab only when user has correct permissions
    * ENHANCEMENT #1005 [ContactBundle]  Added security checks for contacts and accounts
    * BUGFIX      #1008 [AdminBundle]    Fixed 1Password css bug on login screen
    * BUGFIX      #1004 [MediaBundle]    Fix animated gifs
    * BUGFIX      #1002 [ContentBundle]  Changed internal link title for navigation, smartcontent and internal link
    * FEATURE     #935 [MediaBundle]     Added new media selection
    * BUGFIX      #952 [MediaBundle]     Fix coffee icon fallback in media thumbnail view
    * ENHANCEMENT #951 [MediaBundle]     Made path to image-formats.xml configurateable
    * BUGFIX      #968 [MediaBundle]     Add wildcard support for media type check
    * ENHANCEMENT #988 [ContentBundle]   Set locale on render request
    * BUGFIX      #994 [CategoryBundle]  Fixed category search
    * ENHANCEMENT #988 [MediaBundle]     Set working defaults for ghostscript and caching headers
    * BUGFIX      #976 [MediaBundle]     Fix media scale mode parameter
    * FEATURE     #975 [MediaBundle]     Make Storage path and segments configurateable
    * BUGFIX      #973 [All]             Added handling of anonymous user token
    * BUGFIX      #970 [SecurityBundle]  Fixed select all bug in permissions tab
    * FEATURE     #941 [SecurityBundle]  Adding permissions on an object basis
    * BUGFIX      #948 [MediaBundle]     Add ForceRation Parameter to Scale Command
    * FEATURE     #931 [MediaBundle]     Version History Tab
    * FEATURE     #923 [ContactBundle]   Extract CRM to own Bundles
    * BUGFIX      #922 [ContentBundle]   Fixed URL Generation after copying language of a child node
    * FEATURE     #732 [All]             Automatic mapping and assignation of changer, creator, changed and changer.
    * FEATURE     #891 [All]             Added (css) class property to field descriptors, updated husky and fixed an issue when merging settings with matchings
    * FEATURE     #884 [MediaBundle]     Loaders on media delete and media edit
    * BUGFIX      #884 [AdminBundle]     Fix for login displacement issues
    * BUGFIX      #884 [MediaBundle]     Fix for uploading bug on click on dropzone
    * ENHANCEMENT #877 [SecurityBundle]  Extracted some classes to component
    * BUGFIX      #863 [AdminBundle]     Fix for issue that navigation moved content on uncollapse
    * BUGFIX      #863 [MediaBundle]     Fix for not working image upload with click on the dropzone
    * BUGFIX      #863 [AdminBundle]     Workaround for chrome rendering-bug of overlay in the content-edit
    * ENHANCEMENT #942 [ContactBundle]   Changed max characters of street from 60 to 150
    * BUGFIX      #905 [ContactBundle]   Added Functionality for completing contact addresses
    * FEATURE     #940 [ContactBundle]   Added command for fixing nested tree of accounts sulu:contacts:accounts:recover
    * BUGFIX      #876 [ContactBundle]   Bugfix contact adresses and replacing husky select with native select
    * FEATURE     #873 [ContactBundle]   Command-line data-completion-script: automatically set state of all
                                         account-addresses in database. 'app/console sulu:contacts:data:complete -d state'
    * BUGFIX      #908 [CategoryBundle]  Added script for recovering categories nested tree (fixing left/right and depths)
    * FEATURE     #838 [SecurityBundle]  AJAX-Login and resetting of password
    * FEATURE     #886 [AdminBundle]     Moved SuluVersionPass to Sulu\Compontents\Util to make it useable from webspace bundles
    * FEATURE     #838 [AdminBundle]     Login UI
    * FEATURE     #812 [MediaBundle]     Added nested collection API and UI
    * FEATURE     #812 [MediaBundle]     Implemented move collections
    * FEATURE     #805 [MediaBundle]     Implementing media move
    * FEATURE     #909 [MediaBundle]     Added scroll down pagination for collection
    * ENHANCEMENT #907 [ContentBundle]   Added ability to define custom homepage template
    * BUGFIX      #955 [ContentBundle]   Added webspace and locale to page in smart-content to load snippet in correct language

* 0.16.2 (2015-04-14)
    * HOTFIX      #997 [HttpCacheBundle] Fixed bug for caching ESI requests

* 0.16.1 (2015-02-27)
    * HOTFIX      #880 [ContentBundle]   Fixed changelog if user and contact has not the same id
    * HOTFIX      #880 [AdminBundle]     Fixed user link if user and contact has not the same id
    * HOTFIX      #880 [ContentBundle]   Fixed content type time to allow empty time values
    * HOTFIX      #882 [ContentBundle]   Fixed deletion of referenced pages

* 0.16.0 (2015-02-24)
    * BUGFIX      #866 [ContactBundle]   Serialization group "select" for serializing system users
    * BUGFIX      #860 [AdminBundle]     Extended toolbar to accept more options
    * BUGFIX      #--- [ContentBundle]   Added validation for time field
    * BUGFIX      #865 [ContentBundle]   Added validation and localized formatted value for time field
    * BUGFIX      #848 [ContactBundle]   Refactored delete dialog function to make it reuseable
    * BUGFIX      #846 [MediaBundle]     Added missing dot to create event name method (\cc Daniel)
    * ENHANCEMENT #841 [SecurityBundle]  Unique email per user
    * BUGFIX      #698 [SecurityBundle]  Create user command - do not crash when no roles exist.
    * ENHANCEMENT #698 [SecurityBundle]  Create user/role commands - exit gracefully if user / role already exists
    * ENHANCEMENT #698 [SecurityBundle]  Create user command - validate locale when creating new user
    * BUGFIX      #837 [AdminBundle]     Javascript function for croping labels with a certain tag this.sandbox.sulu.cropAllLabels(className)
    * ENHANCEMENT #818 [ContentBundle]   Enhanced column-navigation ordering ui
    * BUGFIX      #857 [ContentBundle]   Added links without save could not be removed
    * FEATURE     #789 [ContentBundle]   Added present as to smart content config (see [here ...](https://github.com/sulu-cmf/docs/blob/master/developer-documentation/300-webspaces/smart-content.md))
    * BUGFIX      #856 [ContentBundle]   Added default values for smart content view vars

* 0.15.2 (2015-02-19)
    * BUGFIX      #846 [MediaBundle]     Added missing dot to create event name method (\cc Daniel)

* 0.15.2 (2015-02-19)
    * HOTFIX      #850 [MediaBundle]     Fixed bug with deleted media in media selection

* 0.15.1 (2015-02-17)
    * HOTFIX      #842 [CoreBundle]      Fixed upgrade internal links command for installations without snippets

* 0.15.0 (2015-02-13)
    * BUGFIX      #833 [AdminBundle]     Added new husky version
    * BUGFIX      #829 [ContactBundle]   Account-Contacts: show full-name of contact
    * ENHANCEMENT #828 [ContactBundle]   Changed columns for contact list and made concatenated columns not sortable
    * BUGFIX      #825 [WebsiteBundle]   Fixed syntax error in ExceptionController
    * FEATURE     #806 [SnippetBundle]   added sorting feature to snippet content type
    * FEATURE     #806 [ContentBundle]   added sorting feature to internal links content type
    * ENHANCEMENT #798 [All]             Updated Symfony version to 2.6
    * BUGFIX      #826 [All]             Moved locales config from admin-bundle to core-bundle
    * BUGFIX      #736 [WebsiteBundle]   Redirect with port didn't work
    * ENHANCEMENT #735 [CategoryBundle]  Use parameters instead of FCQN of entities in service config
    * ENHANCEMENT #735 [MediaBundle]     Use parameters instead of FCQN of entities in service config
    * ENHANCEMENT #735 [TagBundle]       Use parameters instead of FCQN of entities in service config
    * FEATURE     #820 [ContactBundle]   Contact-Import: define multiple tags: 'account_tag1 ..n'
    * FEATURE     #810 [ContactBundle]   Added command line tool for detecting missing country codes in import csv files
                                         that uses google geo api for finding the correct country code
    * FEATURE     #792 [ContactBundle]   Added widget to show all companys of contact
    * BUGFIX      #801 [ALL]             Removed unused clean task which is deleting the public directory when executed
                                         due to the symfony 2.6 changes to symlinks
    * FEATURE     #793 [SecurityBundle]  Added field passwordForgetToken to BaseUser-Entity
    * FEATURE     #793 [ContactBundle]   Added Repository service for Contact
    * BUGFIX      #795 [ContentBundle]   Reversed structure paths to enable custom config
    * ENHANCEMENT #776 [CoreBundle]      Added set title to index page for init webspaces
    * BUGFIX      #774 [ContentBundle]   Enabled save shadow for index pages
    * BUGFIX      #778 [ContentBundle]   Fixed shadow page with internal link and smart-content
    * BUGFIX      #790 [WebsiteBundle]   Fixed twig variables for 404 page
    * FEATURE     #791 [ContentBundle]   Added Changelog to settings tab
    * FEATURE     #789 [ContentBundle]   Enabled property parameters to have metadata for localization
    * FEATURE     #684 [ContentBundle]   Refactored preview to use new websocket component and only one socket for form
                                         and preview
    * FEATURE     #684 [WebsocketBundle] Implemented Websocket Component to standardize Websocket implementations
    * BUGFIX      #753 [MediaBundle]     Fix 0 bytes file upload
    * FEATURE     #714 [ContentBundle]   Add Option to hide page in sidemap
    * ENHANCEMENT #740 [SecurityBundle]  Made role content navigation extendable
    * FEATURE     #569 [All]             Behat integration - behat features for bundles
    * ENHANCEMENT #692 [SecurityBundle]  Made user extendable
    * ENHANCEMENT #731 [TestBundle]      Removed test user
    * BUGFIX      #671 [MediaBundle]     Fixed fileversion update with meta data
    * FEATURE     #702 [AdminBundle]     Added sortings to user settings and changed default url for activities
    * BUGFIX      #697 [ContactBundle]   Set VAT number field optional
    * BUGFIX      #697 [CoreBundle]      Do not try and set the theme when the portal has not been found
    * FEATURE     #697 [HttpCacheBundle] Refactored HTTP cache, introduced Varnish support. See 38af8da73c929f9f57bb87a8973a1ee55dccee29
    * ENHANCEMENT #777 [ContentBundle]   Enable "copy language" on startpage
    * HOTFIX      #788 [ContentBundle]   Fixed bug with empty selection with single internal link

* 0.14.2 (2015-02-02)
    * HOTFIX      #781 [CoreBundle]     HTTP Cache event listener uses the wrong event name due to recent change

* 0.14.1 (2015-01-21)
    * HOTFIX      #741 [ContentBundle]  Fix Resourcelocater Content Type call move without editing
    * HOTFIX      #737 [MediaBundle]    Changed BaseCollection properties to be protected for inheritance

* 0.14.0 (2015-01-15)
    * ENHANCEMENT #695 [ContentBundle]  Hide textblock sort option when there is only 1 textblock available
    * FEATURE     #634 [AdminBundle]    Created new configuration component, added new configuration for autocomplete
    * BUGFIX      #681 [TagBundle]      Fixed filtering of tags in Tag list and Media edit Overlay
    * BUGFIX      #681 [MediaBundle]    Fixed imagick detection
    * FEATURE     #581 [SearchBundle]   Structures deindexed on delete
    * FEATURE     #581 [Content]        NODE_SAVE renamed to NODE_POST_SAVE
    * FEATURE     #581 [Content]        New events: NODE_PRE_DELETE, NODE_POST_DELETE
    * FEATURE     #634 [AdminBundle]    Created new configuration component, added new configuration for autocomplete
                                        and refactored usage of autocomplete
    * BUGFIX      #627 [ContentBundle]  Fixed damaged urls when moving/copy/rename
    * ENHANCEMENT #639 [AdminBundle]    Save page size for datagrid
    * FEATURE     #659 [MediaBundle]    Configurable image quality settings
    * ENHANCEMENT #644 [AdminBundle]    Displaying an error label everytime a request fails
    * ENHANCEMENT #665 [SecurityBundle] Added role creation command and question for role in user creation
    * FEATURE     #662 [SnippetBundle]  Applied security
    * FEATURE     #662 [CategoryBundle] Applied security
    * FEATURE     #662 [TagBundle]      Applied security
    * BUGFIX      #654 [ContentBundle]  Added dummy request to request stack for preview rendering.
                                        This is important when template uses ESI
    * BUGFIX      #661 [WebsiteBundle]  Added published date to resolver
    * BUGFIX      #655 [ContentBundle]  Fixed checkbox read for preview

* 0.13.2 (2014-12-12)
    * HOTFIX      #--- [AdminBundle]    Fixed globalize loading issue
    * HOTFIX      #--- [AdminBundle]    Fixed datagrid destroy method (remove window resize listener)

* 0.13.1 (2014-12-11)
    * HOTFIX      #--- [AdminBundle]    Added missing frontend (css/js) build

* 0.13.0 (2014-12-10)
    * HOTFIX      #619 [MediaBundle]    Made web folder for format cache configurable
    * FEATURE     #637 [WebsiteBundle]  Multisort method and Twig filter
    * FEATURE     #585 [ContentBundle]  Added analytics key to webspace configuration
    * BUGFIX      #612 [SnippetBundle]  Introduced snippet pagination
    * BUGFIX      #544 [ContentBundle]  Fixed PHPCR Format Value switches
    * ENHANCEMENT #599 [ContentBundle]  Moved cache for preview from phpcr to filesystem
    * BUGFIX      #632 [SecurityBundle] Fixed language changer for admin
    * BUGFIX      #633 [SnippetBundle]  Load snippets always in requested language (except there is no translation and
                                        the page is a shadow then use this language)

* 0.12.0 (2014-11-25)

    * ENHANCEMENT #586 [WebsiteBundle]  Added node path variable to template
    * BUGFIX      #614 [SecurityBundle] Fixed the security for command lines
    * HOTFIX      #594 [WebsiteBundle]  Fixed sitemap alternate link bugs
    * BUGFIX      #609 [SecurityBundle] Allows null value for security subject and fixed snippet internal links bug
    * ENHANCEMENT #577 [SecurityBundle] Applied security to navigation items and content tabs
    * ENHANCEMENT #604 [CoreBundle]     Only register services for the current context
    * ENHANCEMENT #--- [Tests]          Fixed output colors for Mac users
    * FEATURE     #563 [CoreBundle]     Introduced LocalizationProviders to offer the possibility to
    * BUGFIX      #563 [SecurityBundle] Showing correct localizations in UserRole-Assignment in
                                        Permission-Tab
    * FEATURE     #564 [ContentBundle]  Added UI to copy content languages
    * HOTFIX      #559 [CoreBundle]     Workaround upstream reg. in DoctrinePHPCRBundle, which causes
                                        eager validation of workspace existence.
    * ENHANCEMENT #523 [All]            Refactored and improved functional tests
    * BUGFIX      #597 [ContentBundle]  Reconnect to mysql if connection gone away in websocket
    * FEATURE     #368 [SnippetBundle]  Added `sulu:snippet:locale-copy`-command

* 0.11.2 (2014-11-17)

    * HOTFIX #559 [CoreBundle]    Workaround upstream reg. in DoctrinePHPCRBundle, which causes
                                  eager validation of workspace existence.

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
