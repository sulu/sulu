# Upgrade

## dev-develop

### Admin

The `Sulu` prefix from all `ContentNavigationProviders` and `Admin` classes has
been removed. You have to change these names in all usages of this classes in
your own code.

### Media preview urls

The thumbnail url will only be generated for supported mime-types. Otherwise it returns a zero length array.

To be sure that it is possible to generate a preview image you should check if the thumbnail url isset:

```twig
{% if media.thumbnails['200x200'] is defined %}
<img src="{{ media.thumbnails['200x200'] }}"/>
{% endif %}
```

### Exception templates

Variables of exception template `ClientWebsiteBundle:error404.html.twig` has changed. It is basically the same as on
the startpage but contains this additional values.

* `status_code`: response code 
* `status_text`: response text
* `exception`: whole exception object
* `currentContent`: content which was rendered before exception was thrown

Especially for 404 exception the `path` variable has been removed.

Before:
```twig
<p>The path "<em>{{ path }}</em>" does not exist.</p>
```

After:
```twig
<p>The path "<em>{{ request.resourceLocator }}</em>" does not exist.</p>
```

## 0.17.0

### Media

Fill up the database column `me_collection_meta.locale` with the translated language like: `de` or `en`. If you
know you have only added collections in only one language you can use following sql statement:

```sql
UPDATE `me_collection_meta` SET `locale` = 'de';
```

Due to this it is possible that one collection has multiple metadata for one language. You have to remove this
duplicates by hand. For example one collection should have only one meta for the language `de`.

The collection and media has now a specific field to indicate which meta is default. For this run following commands.

```bash
app/console sulu:upgrade:0.17.0:collections
app/console sulu:upgrade:0.17.0:media
```

### Content navigation

The interfaces for the content navigation have been changed, so you have to
apply these changes if you have used a content navigation in your bundle.

Basically you can delete the `NavigationController` delivering the content
navigation items together with its routes. It's now common to suffix the
classes providing content navigation items with `ContentNavigationProvider`.

These classes have to implement the `ContentNavigationProviderInterface` and be
registered as services as described in the
[documentation](http://docs.sulu.io/en/latest/cookbook/using-the-tab-navigation.html).

Consider that the URLs for the retrieval of the content navigation items have
changed to `/admin/content-navigations?alias=your-alias` and have to be updated
in your javascript components.

### Contact and Account Security

The security checks are now also applied to contacts and accounts, make sure
that the users you want to have access have the correct permissions.

### Content

Behaviour of internal links has changed. It returns the link title for navigation/smartcontent/internal-link.

### Media Types

The media types are now set by wildcard check and need to be updated,
by running the following command: `sulu:media:type:update`.

### Media API Object

The `versions` attribute of the media API object changed from [array to object list](https://github.com/sulu-io/docs/pull/14/files).

### Contact

CRM-Components moved to a new bundle. If you enable the new Bundle everything should work as before.

BC-Breaks are:

 * AccountCategory replaced with standard Categories here is a migration needed
 
For a database upgrade you have to do following steps:

* The Account has no `type` anymore. This column has to be removed from `co_accounts` table.
* The table `co_account_categories` has to be removed manually.
* The table `co_terms_of_delivery` has to be removed manually.
* The table `co_terms_of_payment` has to be removed manually.
* `app/console doctrine:schema:update --force`

### Security

The names of some classes have changed like shown in the following table:

Old name                                                          | New name
------------------------------------------------------------------|--------------------------------------------------------------
Sulu\Bundle\SecurityBundle\Entity\RoleInterface                   | Sulu\Component\Security\Authentication\RoleInterface
Sulu\Component\Security\UserInterface                             | Sulu\Component\Security\Authentication\UserInterface
Sulu\Bundle\SecurityBundle\Factory\UserRepositoryFactoryInterface | Sulu\Component\Security\Authentication\UserRepositoryFactoryInterface
Sulu\Component\Security\UserRepositoryInterface                   | Sulu\Component\Security\Authentication\UserRepositoryInterface
Sulu\Bundle\SecurityBundle\Permission\SecurityCheckerInterface    | Sulu\Component\Security\Authorization\SecurityCheckerInterface

If you have used any of these interfaces you have to update them.

## 0.16.0

### Content Types

Time content types returns now standardized values (hh:mm:ss) and can handle this as localized string in the input
field.

For content you can upgrade the pages with:

```bash
app/console sulu:upgrade:0.16.0:time
```

In the website you should change the output if time to your format.

If you use the field in another component you should upgrade your api that it returns time values in format (hh:mm:ss).

### Security

Database has changed: User has now a unique email address. Run following command:

```bash
app/console doctrine:schema:update --force
```

## 0.15.0

### Sulu Locales
The Sulu Locales are not hardcoded anymore, but configured in the `app/config/config.yml` file:

```yml
sulu_core:
    locales: ["de","en"]
```

You have to add the locales to your configuration, otherwise Sulu will stop working.

### Internal Links

The internal representation of the internal links have changed, you have to run the following command to convert them:

```bash
app/console sulu:upgrade:0.15.0:internal-links
```

### Content Types

PropertyParameter are now able to hold metadata. Therefore the Interface has changed. Please check all your
ContentTypes which uses params.

__Before:__

DefaultParams:

```php
array(
    'name' => 'value',
    ...
)
```
Access in Twig:

```twig
{{ params.name }}
```

__After:__

```php
array(
    'name' => new PropertyParameter('name', 'string', 'value', array()),
    ...
)
```
Access in Twig:

```twig
As String:
{{ params.name }}

As Array or boolean:
{{ params.name.value }}

Get translated title:
{{ params.name.getTitle('de') }}
```

__Optional:__

Metadata under properties in template:

```xml
<property name="smart_content" type="smart_content">
    <params>
        <param name="display_as" type="collection">
            <param name="two">
                <meta>
                    <title lang="de">Zwei Spalten</title>
                    <title lang="en">Two columns</title>

                    <info_text lang="de">Die Seiten werden in zwei Spalten dargestellt</info_text>
                    <info_text lang="en">The pages would be displayed in two columns</info_text>
                </meta>
            </param>
        </param>
    </params>
</property>
```

### Websocket Component

Websocket start command changed to `app/console sulu:websocket:run`. If you use xdebug on your server please start
websockets with `app/console sulu:websocket:run -e prod`.

Default behavior is that websocket turned of for preview, if you want to use it turn it on in the
`app/config/admin/config.yml` under:

```yml
 sulu_content:
     preview:
         mode: auto       # possibilities [auto, on_request, off]
         websocket: false # use websockets for preview, if true it tries to connect to websocket server,
                          # if that fails it uses ajax as a fallback
         delay: 300       # used for the delayed send of changes, lesser delay are more request but less latency
```

### HTTP Cache

The HTTP cache integration has been refactored. The following configuration
must be **removed**:

````yaml
sulu_core:
    # ...
    http_cache:
        type: symfonyHttpCache
````

The Symfony HTTP cache is enabled by default now, so there is no need to do
anything else. See the [HTTP cache
documentation](http://sulu.readthedocs.org/en/latest/reference/bundles/http_cache.html)
for more information.

### Renamed RequestAnalyzerInterface methods

The text "Current" has been removed from all of the request analyzer methods.
If you used the request analyzer service then you will probably need to update
your code, see: https://github.com/sulu-cmf/sulu/pull/749/files#diff-23

## 0.14.0

* Role name is now unique
  * check roles and give them unique names
* Apply all permissions correctly, otherwise users won't be able to work on snippets, categories or tags anymore

## 0.13.0

* Remove `/cmf/<webspace>/temp` from repository
  * run `app/console doctrine:phpcr:node:remove /cmf/<webspace>/temp` foreach webspace

## 0.12.0

* Permissions have to be correct now, because they are applied
  * otherwise add a permission value of 120 for `sulu.security.roles`,
    `sulu.security.groups` and `sulu.security.users` to one user to change
    the settings in the UI
  * also check for the correct value in the `locale`-column of the `se_user_roles`-table
    * value has to be a json-string (e.g. `["en", "de"]`)
* Snippet content type defaults to all snippet types available instead of the
  default one
  * Explicitly define a snippet type in the parameters if this is not desired
