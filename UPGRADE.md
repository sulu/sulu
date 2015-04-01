# Upgrade

## dev-develop

### Media

Fill up the database column `me_collection_meta.locale` with the translated language like: `de` or `en`. If you
know you have only added collections in only one language you can use following sql statement:

```sql
UPDATE `me_collection_meta` SET `locale` = 'de';
``

### Media API Object

The `versions` attribute of the media API object changed from [array to object list](https://github.com/sulu-io/docs/pull/14/files).

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
