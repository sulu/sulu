CHANGELOG for Sulu
==================

* dev-develop
    * ENHANCEMENT #--   [MediaBundle]    Added function to get base media types
    * ENHANCEMENT #1031 [MediaBundle]    Fixed success label for collection delete
    * BUGFIX      #945  [WebsiteBundle]  Fix Redirect url with query string correctly and trailing slash
    * ENHANCEMENT #1029 [All]            Removed prefixes from content navigation providers and admins

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
