# Upgrade

## 3.0.7

### Smart content tag and category resolving is opt-in

`view.<smart_content_field>.tags` and `view.<smart_content_field>.categories` now expose the raw filter ids by default. To resolve the ids through a resource loader (for example to receive tag names or `Sulu\Bundle\CategoryBundle\Api\Category` API objects, as in Sulu 2.6), opt in via the new `tagResourceLoader` and `categoryResourceLoader` smart content `<param>` entries:

```xml
<property name="my_smart_content" type="smart_content">
    <params>
        <param name="provider" value="pages"/>
        <param name="tagResourceLoader" value="tag"/>
        <param name="categoryResourceLoader" value="category"/>
    </params>
</property>
```

Without these params, the new `raw` resource loader is used, which keeps the ids unchanged. The HTTP cache reference store is still populated for the tag and category ids regardless of the loader choice, so renaming or removing a referenced tag or category continues to invalidate the affected pages.

### Article admin now gates the navigation and views on per-group permissions

The `ArticleAdmin` previously gated its navigation item, per-group views, and toolbar actions (Add, Delete, Export) on the
umbrella `sulu.article.articles` EDIT permission, with per-group `sulu.article.articles_<group>` checks layered on top. The
umbrella check has been removed from those code paths, and the per-group permission is now the sole gate for the admin UI
(see #8830).

The umbrella `sulu.article.articles` permission is still required: `ArticleController` reports it as its security context, so
every article REST endpoint (list, get, create, update, delete, workflow) continues to check it. Roles need both the umbrella
permission for API access and the per-group permission for the matching navigation item and views to render.

After upgrading, re-run the reindex command for both kernels so the admin search index reflects the current security contexts:

```bash
bin/console cmsig:seal:reindex
```

#### Role updates

After upgrading, review every role with article permissions and adjust the grants accordingly:

- `sulu.article.articles`: always required, because the article REST controllers depend on it.
- `sulu.article.articles_<group>`: required for each group the role should manage.

### Selection view structure changed (snippet, page, article)

The `view` data emitted by `*_selection` and `single_*_selection` property resolvers now exposes the loaded entity's metadata and field-level view directly, restoring the Sulu 2.6 shape for multi-selections.

Multi-selection (`snippet_selection`, `page_selection`, `article_selection`) now returns a flat numeric list:

```php
// before
view.snippets => ['ids' => [...], 'types' => 'snippet-1', 0 => [...], 1 => [...]]

// after
view.snippets => [
    ['uuid' => 'uuid-a', 'template' => 'snippet-1', ...],
    ['uuid' => 'uuid-b', 'template' => 'snippet-1', ...],
]
```

Templates that iterated `view.<field>` already worked, templates that read `view.<field>.ids` must switch to `view.<field>` directly.

Single-selection (`single_snippet_selection`, `single_page_selection`, `single_article_selection`) exposes the resolved entity's metadata and per-field view alongside the existing keys:

```php
view.bannerSnippet => [
    'uuid' => 'uuid-a',
    'template' => 'snippet-1',
    'title' => [],
    'textboxButtons' => [...],
    // plus 'id' and any params the resolver was already exposing
]
```

## 3.0.6

### CKEditor upgrade to 47

For security reasons, the new version uses the `^47.0` CKEditor version.

Run:

```bash
bin/adminconsole sulu:admin:update-build
```

If you have any custom CKEditor plugins, you might need to adjust them to be compatible with CKEditor 47.

Keep in mind that CKEditor also requires at least `Node 20` to create a custom admin build.

### Additional Optional Parameter fieldDescriptorFactory for UserController

The `Sulu\Bundle\SecurityBundle\Controller\UserController` now takes an optional argument for the
`FieldDescriptorFactory`. However, omitting this argument is deprecated, so integrators should start
passing/injecting the `FieldDescriptorFactory` now to remain compatible with a future version where it
will become required.

### Introduce Doctrine Migrations Bundle

Sulu now ships core migrations via Doctrine Migrations Bundle. If your project does not use it yet, install it with:

```bash
composer require doctrine/doctrine-migrations-bundle
```

Sulu provides a migration to convert existing persisted tag-name values to tag IDs for SmartContent tag filters and
`tag_selection` fields. Before running the migration, create a database backup. Then execute:

```bash
bin/console doctrine:migrations:migrate
```

### Consistent smart content params across article, page and snippet providers

Several smart content `<param>` names for selecting templates were ambiguous between providers and have been deprecated:

- `types` is deprecated.
  - For article providers (`articles`, `articles_page_tree`), use `groups`.
  - For all other providers (`pages`, `snippets`), use `templateKeys`.
- `structureTypes` is deprecated. Use `templateKeys`.

**Migration:**

Update your template XML files. For example:

```xml
<!-- articles: before -->
<property name="my_articles" type="smart_content">
    <params>
        <param name="provider" value="articles"/>
        <param name="types" value="blog,news"/>
    </params>
</property>

<!-- articles: after -->
<property name="my_articles" type="smart_content">
    <params>
        <param name="provider" value="articles"/>
        <param name="groups" value="blog,news"/>
    </params>
</property>
```

```xml
<!-- pages/snippets: before -->
<property name="my_pages" type="smart_content">
    <params>
        <param name="provider" value="pages"/>
        <param name="structureTypes" value="default"/>
    </params>
</property>

<!-- pages/snippets: after -->
<property name="my_pages" type="smart_content">
    <params>
        <param name="provider" value="pages"/>
        <param name="templateKeys" value="default"/>
    </params>
</property>
```

If a deprecated param name is still used, the container build will fail with a message pointing at the affected template and property.


### SmartContent tag filter now uses tag IDs instead of tag names

The SmartContent tag filter previously stored and compared tags by **name**. It now stores and compares tags by **ID** to
be consistent with how categories are handled and to restore compatibility with content migrated from Sulu 2.6.

Any SmartContent filter configuration that was saved with tag names (as strings) will no longer filter correctly.

### `tag_selection` field type now uses tag IDs instead of tag names

The `tag_selection` field type now persists selected tags as integer IDs instead of name strings.

`TagSelectionPropertyResolver` and `TagResourceLoader` now interpret the 
stored data as integer IDs and look up tags via `TagRepositoryInterface::findBy(['id' => $ids])`. The `TagResourceLoader::load()`
result map is now keyed by tag ID instead of tag name.

### `TaxonomyDataMapper` constructor signature changed

The constructor argument of `Sulu\Content\Application\ContentDataMapper\DataMapper\TaxonomyDataMapper` was changed from 
`Sulu\Content\Domain\Factory\TagFactoryInterface` to `Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface`.
In addition, the input expected on `excerptTags` changed from `string[]` (tag names) to `int[]` (tag IDs).

### Tag creation in SmartContent now requires permission

Creating tags inline from the SmartContent filter overlay now correctly requires the `sulu.tags.tags` `add` permission. Previously, any user could create tags through the SmartContent interface regardless of their tag permissions.

Inline tag creation in the admin frontend now goes through the `TagController` `POST /admin/api/tags` endpoint instead of the implicit `TagManagerInterface::findOrCreateByName()` path, so the standard permission voter applies.

### Deprecations

The following APIs were deprecated in this release and will be removed in a future major version:

- `Sulu\Content\Domain\Factory\TagFactoryInterface` and its `create(string[] $tagNames): TagInterface[]` method. Use `Sulu\Bundle\TagBundle\Tag\TagRepositoryInterface::findBy(['id' => $ids])` instead.
- `Sulu\Bundle\TagBundle\Tag\TagManagerInterface::findOrCreateByName()` and the corresponding `Sulu\Bundle\TagBundle\Tag\TagManager::findOrCreateByName()`. Create tags via the `TagController` `POST /admin/api/tags` endpoint so permissions are enforced.

## 3.0.5

### Route URL no longer stored in templateData

The URL of `route` and `page_tree_route` template fields is no longer persisted into the `templateData` JSON column of 
dimension content tables. The `Route` entity is now the single source of truth for the URL value, and the URL is derived 
from it on read by `RoutableTemplateResolver` and `RoutableNormalizer`.

Existing rows do not need to be migrated — stale URL values in `templateData` are ignored on read and overwritten on the next save.

### Remove false cascade on author and route relations

The cascade delete on the author and route relations has been removed to prevent accidental data loss when deleting authors or routes.

```sql
ALTER TABLE pa_page_dimension_contents DROP FOREIGN KEY `FK_209A42C034ECB4E6`;
ALTER TABLE pa_page_dimension_contents DROP FOREIGN KEY `FK_209A42C0F675F31B`;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C034ECB4E6 FOREIGN KEY (route_id) REFERENCES ro_routes (id) ON DELETE SET NULL;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C0F675F31B FOREIGN KEY (author_id) REFERENCES co_contacts (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_contents DROP FOREIGN KEY `FK_5674F7BF34ECB4E6`;
ALTER TABLE ar_article_dimension_contents DROP FOREIGN KEY `FK_5674F7BFF675F31B`;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF34ECB4E6 FOREIGN KEY (route_id) REFERENCES ro_routes (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BFF675F31B FOREIGN KEY (author_id) REFERENCES co_contacts (id) ON DELETE SET NULL;
```

### Improved page, snippet and article performance

To improve the performance new indexes where added to the dimension tables:

```sql
CREATE INDEX idx_pa_page_dimension_contents_stage_version_locale ON pa_page_dimension_contents (stage, version, locale);
CREATE INDEX idx_pa_page_dimension_contents_resource_lookup ON pa_page_dimension_contents (pageUuid, stage, version, locale, ghostLocale);
CREATE INDEX idx_pa_page_dimension_contents_resource_template_lookup ON pa_page_dimension_contents (pageUuid, stage, version, locale, templateKey);
CREATE INDEX idx_sn_snippet_dimension_contents_stage_version_locale ON sn_snippet_dimension_contents (stage, version, locale);
CREATE INDEX idx_sn_snippet_dimension_contents_resource_lookup ON sn_snippet_dimension_contents (snippetUuid, stage, version, locale, ghostLocale);
CREATE INDEX idx_sn_snippet_dimension_contents_resource_template_lookup ON sn_snippet_dimension_contents (snippetUuid, stage, version, locale, templateKey);
CREATE INDEX idx_ar_article_dimension_contents_stage_version_locale ON ar_article_dimension_contents (stage, version, locale);
CREATE INDEX idx_ar_article_dimension_contents_resource_lookup ON ar_article_dimension_contents (articleUuid, stage, version, locale, ghostLocale);
CREATE INDEX idx_ar_article_dimension_contents_resource_template_lookup ON ar_article_dimension_contents (articleUuid, stage, version, locale, templateKey);
```

## 3.0.4

The type of the `apiKey` in the `se_users` table has been changed to `string`.

```sql
ALTER TABLE se_users CHANGE apiKey apiKey VARCHAR(128) DEFAULT NULL;
```

An index on the `idRoles` column has been added to the `se_access_controls` table to improve query performance for permission checks with multiple roles.

The access control query logic for doctrine entities with multiple roles has been fixed. Access is now granted if any assigned role grants the requested permission for an entity, instead of incorrectly denying access when another assigned role has no permission for the same entity.

```sql
CREATE INDEX IDX_C526DC5238C751C4 ON se_access_controls (idRoles);
```

## 3.0.3

### TeaserProvider Refactoring

The generic `ContentTeaserProvider` and related traits have been removed due to architectural issues.
Each resource now has its own standalone teaser provider implementation:

- `PageTeaserProvider` - Standalone implementation for pages
- `ArticleTeaserProvider` - Standalone implementation with multi-webspace support for articles

**Removed classes:**
- `Sulu\Bundle\ContentBundle\Infrastructure\Sulu\Teaser\ContentTeaserProvider`
- `Sulu\Bundle\ContentBundle\Infrastructure\Sulu\Traits\FindContentRichEntitiesTrait`
- `Sulu\Bundle\ContentBundle\Infrastructure\Sulu\Traits\ResolveContentDimensionUrlTrait`
- `Sulu\Bundle\ContentBundle\Infrastructure\Sulu\Traits\ResolveContentTrait`

If you extended these classes or traits in custom teaser providers, refactor your implementation to be standalone following the pattern in `PageTeaserProvider` or `ArticleTeaserProvider`.

## 3.0.2

### Snippet Area Security Context Migration

The security context for snippet areas changed from `sulu.snippet.snippet_areas` to `sulu.webspaces.{webspaceKey}.snippet-areas`
to align with Sulu's webspace-based permission architecture.

**Migration:** Configure snippet area permissions per webspace in **Settings** → **User Roles** → **Permissions** → **Webspaces**,
or run this SQL to automatically migrate all roles with the old permission:

```sql
-- Migrate permissions to all webspaces automatically
INSERT INTO se_permissions (context, permissions, idRoles)
SELECT
    CONCAT(SUBSTRING_INDEX(p2.context, '.', 3), '.snippet-areas') as context,
    p.permissions,
    p.idRoles
FROM se_permissions p
CROSS JOIN (
    SELECT DISTINCT SUBSTRING_INDEX(context, '.', 3) as context
    FROM se_permissions
    WHERE context LIKE 'sulu.webspaces.%'
    AND (
        LENGTH(context) - LENGTH(REPLACE(context, '.', '')) = 2
        OR LENGTH(context) - LENGTH(REPLACE(context, '.', '')) = 3
    )
) p2
WHERE p.context = 'sulu.snippet.snippet_areas'
AND NOT EXISTS (
    SELECT 1 FROM se_permissions p3
    WHERE p3.context = CONCAT(SUBSTRING_INDEX(p2.context, '.', 3), '.snippet-areas')
    AND p3.idRoles = p.idRoles
);

-- Remove old permission
DELETE FROM se_permissions WHERE context = 'sulu.snippet.snippet_areas';
```

## 3.0.1

### Support for doctrine/orm 3 dependencies added

Sulu now also supports the following Doctrine package versions: `doctrine/orm:^3.1`, `doctrine/dbal:^4.0`, and `doctrine/persistence:^4.0`.

It is recommended to perform this upgrade as a separate step.

To freeze your project's current Doctrine versions, run:

```bash
composer require doctrine/orm:"^2.17" doctrine/persistence:"^3.1" doctrine/dbal:"^3.9"
```

After successfully upgrading Sulu and verifying everything works as expected,
you can proceed with the Doctrine upgrade using:

```bash
composer require doctrine/orm:"^3.1" doctrine/persistence:"^4.0" doctrine/dbal:"^4.0"
```

The required changes can be found in the Doctrine repositories UPGRADE and CHANGELOG files.

## 3.0.0

The upgrade from Sulu 2.6 to Sulu 3.0 is a major upgrade and will require some migration steps.

### Pre Update step to 3.0 PHPCR Migration

Before upgrading to 3.0 make sure you have installed the latest Sulu 2.x versions of Sulu and the Sulu bundles.
Make sure that you have all PHPCR migration run via:

```shell
php bin/adminconsole phpcr:migrations:migrate
```

### Pre Update step to 3.0 strict webspace, template and navigation context key validation

In Sulu 3.0 the webspace and template keys are now strictly validated.
This means that the webspace, template and navigation context keys need to match
the pattern `[a-z0-9_-]+` and with a max length of 31 characters.

If you have webspace or template keys that do not match this pattern, you need to change them before
via [a PHPCR migration](https://docs.sulu.io/en/3.x/cookbook/migrate-content-data.html) or manually in the database.

### Pre Update step to 3.0 Sulu Article Bundle

Due to the fact that in Sulu 3.0 the SuluArticleBundle is merged into the core Sulu package, if you have the
sulu/article-bundle installed, you need to update the sulu/article-bundle dependency to the latest version before
updating the sulu/sulu dependency. If the sulu/article-bundle is not installed, this step is not necessary.

Ensure that the [Version202407111600](https://github.com/sulu/SuluArticleBundle/blob/2.6/Resources/phpcr-migrations/Version202407111600.php) migration is executed. This migration is required to migrate the old article
structure to the new one. After that you can remove the old article bundle from your code.

```shell
composer remove sulu/article-bundle --no-scripts
# composer remove elasticsearch/elasticsearch --no-scripts # can also be removed if not further required
```

Disable the old SuluArticleBundle `config/bundles.php`:

```diff
// config/bundles.php

return [
     // ...
-    Sulu\Bundle\ArticleBundle\SuluArticleBundle::class => ['all' => true],
-    ONGR\ElasticsearchBundle\ONGRElasticsearchBundle::class => ['all' => true],
```

It can happen that flex removes the `config/templates/articles/*` files. If you need the article bundle, ensure you 
don’t lose these files by checking them out again from version control:

```bash
git checkout config/templates/articles/
```

### Pre Update step to 3.0 Cleanup phpcr-repository

Before you can upgrade to Sulu 3.0 you need to cleanup the phpcr-repository.

Run the following command to remove all the unused properties from the phpcr-repository:

```bash
php bin/adminconsole sulu:document:phpcr-cleanup
```

### Upgrade to Sulu 3.0 and register new bundles

Now upgrade the dependencies to Sulu 3.0:

```shell
composer require sulu/sulu:"3.0.*" --no-scripts
```

After that you need to register the new Sulu bundles in your `config/bundles.php`:

```diff
// config/bundles.php

return [
     // ...
-    Sulu\Bundle\RouteBundle\SuluRouteBundle::class => ['all' => true],
-    Sulu\Bundle\SnippetBundle\SuluSnippetBundle::class => ['all' => true],
-    Sulu\Bundle\ArticleBundle\SuluArticleBundle::class => ['all' => true],
-    ONGR\ElasticsearchBundle\ONGRElasticsearchBundle::class => ['all' => true],
-    Sulu\Bundle\CustomUrlBundle\SuluCustomUrlBundle::class => ['all' => true],

+    Sulu\Content\Infrastructure\Symfony\HttpKernel\SuluContentBundle::class => ['all' => true],
+    Sulu\Route\Infrastructure\Symfony\HttpKernel\SuluRouteBundle::class => ['all' => true],
+    Sulu\Messenger\Infrastructure\Symfony\HttpKernel\SuluMessengerBundle::class => ['all' => true],
+    Sulu\Article\Infrastructure\Symfony\HttpKernel\SuluArticleBundle::class => ['all' => true],
+    Sulu\Snippet\Infrastructure\Symfony\HttpKernel\SuluSnippetBundle::class => ['all' => true],
+    Sulu\Page\Infrastructure\Symfony\HttpKernel\SuluPageBundle::class => ['all' => true],
```

### Register new bundle routes

Then you need to update the route configuration in your `config/routes/sulu_admin.yaml`:

```diff
-sulu_core:
-    type: rest
-    resource: "@SuluCoreBundle/Resources/config/routing_api.yml"
-    prefix: /admin/api

- sulu_custom_urls_api:
-     type: rest
-     resource: "@SuluCustomUrlBundle/Resources/config/routing_api.yml"
-     prefix: /admin/api

 sulu_snippet_api:
-    resource: "@SuluSnippetBundle/Resources/config/routing_api.yml"
-    type: rest
+    resource: "@SuluSnippetBundle/config/routing_admin_api.yaml"
     prefix: /admin/api

-sulu_route_api:
-    resource: "@SuluRouteBundle/Resources/config/routing_api.yaml"
-    prefix: /admin/api
-
+sulu_route_api:
+    resource: "@SuluRouteBundle/config/routing_admin_api.yaml"
+    prefix: /admin/api
+
+sulu_page_api:
+    resource: "@SuluPageBundle/config/routing_admin_api.yaml"
+    prefix: /admin/api
+
+sulu_article_api:
+    resource: "@SuluArticleBundle/config/routing_admin_api.yaml"
+    prefix: /admin/api
```

### Massive Search Bundle was removed

The Admin and Website search does no longer use the `MassiveSearchBundle` and so it can be removed from your project.
Remove the `massive/search-bundle` from your composer dependencies:

```bash
composer remove massive/search-bundle --no-scripts
composer remove handcraftedinthealps/zendsearch --no-scripts
```

Remove the bundle from your `config/bundles.php` file:

```diff
// config/bundles.php

return [
-    Massive\Bundle\SearchBundle\MassiveSearchBundle::class => ['all' => true],
```

Remove also the `config/packages/massive_search.yaml` if not tackled by composer already:

```bash
rm config/packages/massive_search.yaml
```

Update `config/routes/sulu_website.yaml`:

```diff
sulu_search:
    type: portal
-    resource: "@SuluSearchBundle/Resources/config/routing_website.yaml"
+    resource: "@SuluSearchBundle/config/routing_website.yaml"
```

The new `SearchBundle` is built on top of [SEAL](https://github.com/PHP-CMSIG/search) and supports
a wide range of search engines. If your project did use ZendSearch before the best way is to
update to the Loupe Adapter which only requires SQLite.

```yaml
# config/packages/cmsig_seal.yaml
cmsig_seal:
    schemas:
        default:
            dir: '%kernel.project_dir%/config/schemas'
            engine: default
    engines:
        default:
            adapter: '%env(resolve:SEAL_DSN)%'

when@test:
    cmsig_seal:
        # "TEST_TOKEN" is typically set by ParaTest
        index_name_prefix: 'test_%env(default::TEST_TOKEN)%'
```

And:

```dotenv
SEAL_DSN=loupe://%kernel.project_dir%/var/indexes
```

To install the adapter use:

```bash
composer require cmsig/seal-loupe-adapter --no-scripts
```

Make sure you have the `pdo_sqlite` extension installed and enabled.
In Linux package manager it is provided via e.g.: `php8.4-sqlite3` package:

```bash
apt-get update
apt-get install php8.4-sqlite3
```

If you are in docker the extension may already pre-installed else check your installer ([source](https://github.com/mlocati/docker-php-extension-installer?tab=readme-ov-file#supported-php-extensions)).

Have a look at the [SEAL Documentation](https://php-cmsig.github.io/search/) if you want to use other adapters,
like Elasticsearch, Meilisearch, Algolia, Redis and more.

### Template Controller changes

The `DefaultController` and it's parent class `WebsiteController` has been removed in favor
of the new `ContentController`. Update your controllers to extend the new `ContentController`
and update your templates definition to use the new controller:

```diff
-<controller>Sulu\Bundle\WebsiteBundle\Controller\DefaultController::indexAction</controller>
+<controller>Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction</controller>
```

### Content load Twig functions split and properties now required

The Sulu 2.6 `sulu_content_load` function has been split into separate `sulu_page_load` and `sulu_article_load` functions.
Additionally, the `properties` parameter is now mandatory, and a new optional `locale` parameter has been added.

**What Changed:**

**For Pages and Articles (Sulu 2.6 → Sulu 3.0):**
- `sulu_content_load(uuid, ?properties)` → `sulu_page_load(uuid, properties, ?locale)`
- `sulu_content_load(uuid, ?properties)` → `sulu_article_load(uuid, properties, ?locale)`

**For Snippets (Sulu 2.6 → Sulu 3.0):**
- `sulu_snippet_load_by_area(area, ?webspaceKey, ?locale)` → `sulu_snippet_load_by_area(areaKey, properties, ?webspaceKey, ?locale)`

**Key Changes:**
1. **Function split**: The single `sulu_content_load` function has been replaced with `sulu_page_load` and `sulu_article_load`
2. **Properties mandatory**: The `properties` parameter was optional in Sulu 2.6 but is now required in Sulu 3.0
3. **Locale parameter added**: A new optional `locale` parameter allows you to load content in a specific locale (previously always used the current request's locale)
4. **Snippet properties**: The `properties` parameter is completely new for snippets in Sulu 3.0

**Migration:**

Update all Twig templates to use the new function names and pass the properties array explicitly:

```twig
{# Old Sulu 2.6 #}
{% set page = sulu_content_load(page_uuid) %}
{% set page = sulu_content_load(page_uuid, ['title', 'description']) %}
{% set article = sulu_content_load(article_uuid) %}
{% set snippet = sulu_snippet_load_by_area('footer', 'sulu-io', 'en') %}

{# New Sulu 3.0 - use separate functions, properties mandatory #}
{% set page = sulu_page_load(page_uuid, {
    'title': 'title',
    'description': 'description',
}) %}

{# Optionally specify locale (new in 3.0) #}
{% set page = sulu_page_load(page_uuid, {
    'title': 'title',
    'url': 'url'
}, 'en') %}

{# Use sulu_article_load for articles #}
{% set article = sulu_article_load(article_uuid, {
    'title': 'title',
    'description': 'description'
}) %}

{# Snippets now require properties parameter #}
{% set snippet = sulu_snippet_load_by_area('footer', {
    'title': 'title',
    'image': 'image'
}, 'sulu-io', 'en') %}
```

**Note:** The property mapping uses the format `'output_key': 'content_resolver_path'`. For example:
- `'title': 'title'` - Maps the content's title field to the `title` key in the output
- `'url': 'url'` - Maps the content's URL to the `url` key
- `'content': 'content'` - Maps all template data fields to the `content` key
- `'excerpt.description': 'excerpt.description'` - Maps excerpt extension fields

### Navigation Twig functions renamed to `sulu_page_` prefix

All navigation-related Twig functions have been renamed to include the `sulu_page_` prefix instead of just `sulu_`
to better reflect that they come from the page package.

**Old function names:**
- `sulu_navigation_root_flat` → `sulu_page_navigation_root_flat`
- `sulu_navigation_root_tree` → `sulu_page_navigation_root_tree`
- `sulu_navigation_flat` → `sulu_page_navigation_flat`
- `sulu_navigation_tree` → `sulu_page_navigation_tree`
- `sulu_breadcrumb` → `sulu_page_breadcrumb`
- `sulu_navigation_is_active` → `sulu_page_navigation_is_active`

### Navigation Twig Extension property filtering

The navigation Twig functions and repository methods now support custom property filtering, and the default properties
have been simplified to improve performance.

**What Changed:**

1. The `$loadExcerpt` parameter has been removed from all navigation functions
2. The default properties have been reduced to only `title` and `url`
3. All navigation-related Twig extension methods accept an optional `$properties` parameter for custom fields

**Migration:**

If you need the old default properties, explicitly pass them to the navigation functions:

```twig
{# Old behavior (Sulu 2.6) #}
{% set navigation = sulu_navigation_root_flat('main', 2, false) %}

{# New behavior - use new function names and explicitly specify needed properties #}
{% set navigation = sulu_page_navigation_root_flat('main', 2, {
    'uuid': 'object.resource.id',
    'title': 'title',
    'url': 'url',
    'webspaceKey': 'object.resource.webspaceKey',
    'template': 'object.templateKey',
    'changed': 'object.changed',
    'changer': 'object.changer',
    'created': 'object.created',
    'creator': 'object.creator',
    'linkProvider': 'object.linkData[provider]'
}) %}

{# For excerpt fields, include them explicitly #}
{% set navigationWithExcerpt = sulu_page_navigation_root_flat('main', 2, {
    'title': 'title',
    'url': 'url',
    'excerpt.title': 'excerpt.title',
    'excerpt.description': 'excerpt.description',
    'excerpt.image': 'excerpt.image'
}) %}
```

Be aware, that the "nodeType" was replaced by "linkProvider", this field must be adjusted accordingly.  The property mapping
uses the format `'output_key': 'content_resolver_path'`, where the content resolver path can access nested object
properties using dot notation (e.g., `'object.resource.webspaceKey'`).

### Add new Content storage tables

The new content storage architecture requires a new database schema. You can execute the following sql statements
to update your database schema.

The following SQL statements are examples based on MySQL. You might generate them via doctrine for your preferred database.

#### RouteBundle

To be able to use the old routes in the migration we have to rename the `ro_routes` table to `ro_routes_old`.

```sql
ALTER TABLE ro_routes RENAME TO ro_routes_old;
```

And create a new `ro_routes` table with the following structure:

```sql
CREATE TABLE ro_routes (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, webspace VARCHAR(31) DEFAULT NULL, locale VARCHAR(15) NOT NULL, slug VARCHAR(144) NOT NULL, resource_key VARCHAR(32) NOT NULL, resource_id VARCHAR(70) NOT NULL, INDEX IDX_671DB7A4727ACA70 (parent_id), INDEX ro_routes_resource_idx (locale, resource_key, resource_id), UNIQUE INDEX ro_routes_unique (webspace, locale, slug), PRIMARY KEY(id));
ALTER TABLE ro_routes ADD CONSTRAINT FK_671DB7A4727ACA70 FOREIGN KEY (parent_id) REFERENCES ro_routes (id) ON DELETE SET NULL;
```

#### PageBundle

```sql
CREATE TABLE pa_page_dimension_contents (title VARCHAR(191) DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, stage VARCHAR(15) NOT NULL, locale VARCHAR(15) DEFAULT NULL, ghostLocale VARCHAR(15) DEFAULT NULL, availableLocales JSON DEFAULT NULL, version INT NOT NULL, shadowLocale VARCHAR(15) DEFAULT NULL, shadowLocales JSON DEFAULT NULL, templateKey VARCHAR(31) DEFAULT NULL, templateData JSON NOT NULL, seoData JSON NOT NULL, seoNoIndex TINYINT(1) NOT NULL, seoNoFollow TINYINT(1) NOT NULL, seoHideInSitemap TINYINT(1) NOT NULL, excerptData JSON NOT NULL, excerptSegment VARCHAR(255) DEFAULT NULL, authored DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', lastModified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', workflowPlace VARCHAR(31) DEFAULT NULL, workflowPublished DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', linkProvider VARCHAR(32) DEFAULT NULL, linkData JSON DEFAULT NULL, created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', route_id INT DEFAULT NULL, pageUuid VARCHAR(36) NOT NULL, author_id INT DEFAULT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_209A42C034ECB4E6 (route_id), INDEX IDX_209A42C0F099EEF3 (pageUuid), INDEX IDX_209A42C0F675F31B (author_id), INDEX IDX_209A42C0DBF11E1D (idUsersCreator), INDEX IDX_209A42C030D07CD5 (idUsersChanger), INDEX idx_pa_page_dimension_contents_dimension (stage, locale), INDEX idx_pa_page_dimension_contents_locale (locale), INDEX idx_pa_page_dimension_contents_stage (stage), INDEX idx_pa_page_dimension_contents_version (version), INDEX idx_pa_page_dimension_contents_template_key (templateKey), INDEX idx_pa_page_dimension_contents_workflow_place (workflowPlace), INDEX idx_pa_page_dimension_contents_workflow_published (workflowPublished), INDEX idx_pa_page_dimension_contents_link_provider (linkProvider), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE pa_page_dimension_content_excerpt_tags (page_dimension_content_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_66C81FDB67C2CFD5 (page_dimension_content_id), INDEX IDX_66C81FDBBAD26311 (tag_id), PRIMARY KEY(page_dimension_content_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE pa_page_dimension_content_excerpt_categories (page_dimension_content_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_BE45C16867C2CFD5 (page_dimension_content_id), INDEX IDX_BE45C16812469DE2 (category_id), PRIMARY KEY(page_dimension_content_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE pa_page_dimension_content_navigation_contexts (id INT AUTO_INCREMENT NOT NULL, page_dimension_content_id INT NOT NULL, name VARCHAR(31) NOT NULL, INDEX IDX_4C5FD8F767C2CFD5 (page_dimension_content_id), INDEX idx_page_navigation_context (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE pa_pages (uuid VARCHAR(36) NOT NULL, parent_id VARCHAR(36) DEFAULT NULL, webspaceKey VARCHAR(31) NOT NULL, lft INT NOT NULL, rgt INT NOT NULL, depth INT NOT NULL, created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_FF3DA1E2727ACA70 (parent_id), INDEX IDX_FF3DA1E2DBF11E1D (idUsersCreator), INDEX IDX_FF3DA1E230D07CD5 (idUsersChanger), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C034ECB4E6 FOREIGN KEY (route_id) REFERENCES ro_routes (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C0F099EEF3 FOREIGN KEY (pageUuid) REFERENCES pa_pages (uuid) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C0F675F31B FOREIGN KEY (author_id) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C0DBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C030D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE pa_page_dimension_content_excerpt_tags ADD CONSTRAINT FK_66C81FDB67C2CFD5 FOREIGN KEY (page_dimension_content_id) REFERENCES pa_page_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_content_excerpt_tags ADD CONSTRAINT FK_66C81FDBBAD26311 FOREIGN KEY (tag_id) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_content_excerpt_categories ADD CONSTRAINT FK_BE45C16867C2CFD5 FOREIGN KEY (page_dimension_content_id) REFERENCES pa_page_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_content_excerpt_categories ADD CONSTRAINT FK_BE45C16812469DE2 FOREIGN KEY (category_id) REFERENCES ca_categories (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_content_navigation_contexts ADD CONSTRAINT FK_4C5FD8F767C2CFD5 FOREIGN KEY (page_dimension_content_id) REFERENCES pa_page_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE pa_pages ADD CONSTRAINT FK_FF3DA1E2727ACA70 FOREIGN KEY (parent_id) REFERENCES pa_pages (uuid) ON DELETE CASCADE;
ALTER TABLE pa_pages ADD CONSTRAINT FK_FF3DA1E2DBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE pa_pages ADD CONSTRAINT FK_FF3DA1E230D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
```

#### SnippetBundle

```sql
CREATE TABLE sn_snippet_dimension_contents (title VARCHAR(191) DEFAULT NULL, id INT AUTO_INCREMENT NOT NULL, stage VARCHAR(15) NOT NULL, locale VARCHAR(15) DEFAULT NULL, ghostLocale VARCHAR(15) DEFAULT NULL, availableLocales JSON DEFAULT NULL, version INT NOT NULL, templateKey VARCHAR(31) DEFAULT NULL, templateData JSON NOT NULL, excerptSegment VARCHAR(255) DEFAULT NULL, workflowPlace VARCHAR(31) DEFAULT NULL, workflowPublished DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', snippetUuid VARCHAR(36) NOT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_46D6814477F33FFB (snippetUuid), INDEX IDX_46D68144DBF11E1D (idUsersCreator), INDEX IDX_46D6814430D07CD5 (idUsersChanger), INDEX idx_sn_snippet_dimension_contents_dimension (stage, locale), INDEX idx_sn_snippet_dimension_contents_locale (locale), INDEX idx_sn_snippet_dimension_contents_stage (stage), INDEX idx_sn_snippet_dimension_contents_version (version), INDEX idx_sn_snippet_dimension_contents_template_key (templateKey), INDEX idx_sn_snippet_dimension_contents_workflow_place (workflowPlace), INDEX idx_sn_snippet_dimension_contents_workflow_published (workflowPublished), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE sn_snippet_dimension_content_excerpt_tags (snippet_dimension_content_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_96BD1E357891499D (snippet_dimension_content_id), INDEX IDX_96BD1E35BAD26311 (tag_id), PRIMARY KEY(snippet_dimension_content_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE sn_snippet_dimension_content_excerpt_categories (snippet_dimension_content_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_464EB1547891499D (snippet_dimension_content_id), INDEX IDX_464EB15412469DE2 (category_id), PRIMARY KEY(snippet_dimension_content_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE sn_snippet_area (uuid VARCHAR(255) NOT NULL, webspace_key VARCHAR(255) NOT NULL, area_key VARCHAR(255) NOT NULL, idSnippet VARCHAR(36) DEFAULT NULL, INDEX IDX_8C978EE186A5E727 (idSnippet), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE sn_snippets (uuid VARCHAR(36) NOT NULL, created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_E68115CFDBF11E1D (idUsersCreator), INDEX IDX_E68115CF30D07CD5 (idUsersChanger), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D6814477F33FFB FOREIGN KEY (snippetUuid) REFERENCES sn_snippets (uuid) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D68144DBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D6814430D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE sn_snippet_dimension_content_excerpt_tags ADD CONSTRAINT FK_96BD1E357891499D FOREIGN KEY (snippet_dimension_content_id) REFERENCES sn_snippet_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_content_excerpt_tags ADD CONSTRAINT FK_96BD1E35BAD26311 FOREIGN KEY (tag_id) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_content_excerpt_categories ADD CONSTRAINT FK_464EB1547891499D FOREIGN KEY (snippet_dimension_content_id) REFERENCES sn_snippet_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_content_excerpt_categories ADD CONSTRAINT FK_464EB15412469DE2 FOREIGN KEY (category_id) REFERENCES ca_categories (id) ON DELETE CASCADE;
ALTER TABLE sn_snippets ADD CONSTRAINT FK_E68115CFDBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE sn_snippet_area ADD CONSTRAINT FK_8C978EE186A5E727 FOREIGN KEY (idSnippet) REFERENCES sn_snippets (uuid) ON DELETE CASCADE;
ALTER TABLE sn_snippets ADD CONSTRAINT FK_E68115CF30D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
```

#### ArticleBundle

```sql
CREATE TABLE ar_article_dimension_contents (title VARCHAR(191) DEFAULT NULL, customizeWebspaceSettings TINYINT(1) NOT NULL, id INT AUTO_INCREMENT NOT NULL, stage VARCHAR(15) NOT NULL, locale VARCHAR(15) DEFAULT NULL, ghostLocale VARCHAR(15) DEFAULT NULL, availableLocales JSON DEFAULT NULL, version INT NOT NULL, shadowLocale VARCHAR(15) DEFAULT NULL, shadowLocales JSON DEFAULT NULL, templateKey VARCHAR(31) DEFAULT NULL, templateData JSON NOT NULL, seoData JSON NOT NULL, seoNoIndex TINYINT(1) NOT NULL, seoNoFollow TINYINT(1) NOT NULL, seoHideInSitemap TINYINT(1) NOT NULL, excerptData JSON NOT NULL, excerptSegment VARCHAR(255) DEFAULT NULL, mainWebspace VARCHAR(255) DEFAULT NULL, authored DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', lastModified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', workflowPlace VARCHAR(31) DEFAULT NULL, workflowPublished DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', route_id INT DEFAULT NULL, articleUuid VARCHAR(36) NOT NULL, author_id INT DEFAULT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_5674F7BF34ECB4E6 (route_id), INDEX IDX_5674F7BFAE39C518 (articleUuid), INDEX IDX_5674F7BFF675F31B (author_id), INDEX IDX_5674F7BFDBF11E1D (idUsersCreator), INDEX IDX_5674F7BF30D07CD5 (idUsersChanger), INDEX idx_ar_article_dimension_contents_dimension (stage, locale), INDEX idx_ar_article_dimension_contents_locale (locale), INDEX idx_ar_article_dimension_contents_stage (stage), INDEX idx_ar_article_dimension_contents_version (version), INDEX idx_ar_article_dimension_contents_template_key (templateKey), INDEX idx_ar_article_dimension_contents_workflow_place (workflowPlace), INDEX idx_ar_article_dimension_contents_workflow_published (workflowPublished), PRIMARY KEY(id));
CREATE TABLE ar_article_dimension_content_excerpt_tags (article_dimension_content_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_B45854027C1747D1 (article_dimension_content_id), INDEX IDX_B4585402BAD26311 (tag_id), PRIMARY KEY(article_dimension_content_id, tag_id));
CREATE TABLE ar_article_dimension_content_excerpt_categories (article_dimension_content_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_971AE52D7C1747D1 (article_dimension_content_id), INDEX IDX_971AE52D12469DE2 (category_id), PRIMARY KEY(article_dimension_content_id, category_id));
CREATE TABLE ar_article_dimension_content_additional_webspaces (name VARCHAR(31) NOT NULL, id INT AUTO_INCREMENT NOT NULL, article_dimension_content_id INT NOT NULL, INDEX IDX_3F9F33F37C1747D1 (article_dimension_content_id), INDEX idx_article_additional_webspace (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE ar_articles (uuid VARCHAR(36) NOT NULL, created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_7F75CD17DBF11E1D (idUsersCreator), INDEX IDX_7F75CD1730D07CD5 (idUsersChanger), PRIMARY KEY(uuid));
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF34ECB4E6 FOREIGN KEY (route_id) REFERENCES ro_routes (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BFAE39C518 FOREIGN KEY (articleUuid) REFERENCES ar_articles (uuid) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BFF675F31B FOREIGN KEY (author_id) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BFDBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF30D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_content_excerpt_tags ADD CONSTRAINT FK_B45854027C1747D1 FOREIGN KEY (article_dimension_content_id) REFERENCES ar_article_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_excerpt_tags ADD CONSTRAINT FK_B4585402BAD26311 FOREIGN KEY (tag_id) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_excerpt_categories ADD CONSTRAINT FK_971AE52D7C1747D1 FOREIGN KEY (article_dimension_content_id) REFERENCES ar_article_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_excerpt_categories ADD CONSTRAINT FK_971AE52D12469DE2 FOREIGN KEY (category_id) REFERENCES ca_categories (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_additional_webspaces ADD CONSTRAINT FK_3F9F33F37C1747D1 FOREIGN KEY (article_dimension_content_id) REFERENCES ar_article_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE ar_articles ADD CONSTRAINT FK_7F75CD17DBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE ar_articles ADD CONSTRAINT FK_7F75CD1730D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
```

#### CustomUrlBundle

Custom URLs are no longer stored in PHPCR and have been migrated to Doctrine ORM tables.

```sql
CREATE TABLE cu_custom_url (uuid VARCHAR(36) NOT NULL, title VARCHAR(255) NOT NULL, published TINYINT(1) NOT NULL, base_domain VARCHAR(255) NOT NULL, webspace VARCHAR(255) NOT NULL, domain_parts JSON NOT NULL, target_document VARCHAR(255) DEFAULT NULL, target_locale VARCHAR(255) NOT NULL, canonical TINYINT(1) NOT NULL, redirect TINYINT(1) NOT NULL, no_follow TINYINT(1) NOT NULL, no_index TINYINT(1) NOT NULL, created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, UNIQUE INDEX UNIQ_51A7F98D2B36786B (title), INDEX IDX_51A7F98DDBF11E1D (idUsersCreator), INDEX IDX_51A7F98D30D07CD5 (idUsersChanger), INDEX IDX_51A7F98DB055BCD4 (webspace), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE cu_custom_url_route (uuid VARCHAR(36) NOT NULL, customUrl VARCHAR(36) NOT NULL, target_route_uuid VARCHAR(36) DEFAULT NULL, path VARCHAR(255) NOT NULL, history TINYINT(1) DEFAULT 0 NOT NULL, created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', INDEX IDX_D2349CF4CB30A644 (customUrl), INDEX IDX_D2349CF44ED689B2 (target_route_uuid), INDEX custom_url_route_history_idx (history), UNIQUE INDEX cu_custom_url_route_unique (path), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE cu_custom_url ADD CONSTRAINT FK_51A7F98DDBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE cu_custom_url ADD CONSTRAINT FK_51A7F98D30D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE cu_custom_url_route ADD CONSTRAINT FK_D2349CF4CB30A644 FOREIGN KEY (customUrl) REFERENCES cu_custom_url (uuid) ON DELETE CASCADE;
ALTER TABLE cu_custom_url_route ADD CONSTRAINT FK_D2349CF44ED689B2 FOREIGN KEY (target_route_uuid) REFERENCES cu_custom_url_route (uuid) ON DELETE CASCADE;
```

**Custom URL Routing Features:**
- **History Routes**: When a custom URL path changes (baseDomain or domainParts), the old route is automatically marked as history and redirects to the new URL with a 301 redirect
- **Redirect Support**: Custom URLs with `redirect=true` will redirect to their target page URL
- **SEO Properties**: `canonical`, `noFollow`, and `noIndex` properties are available and applied to route defaults
- **Self-Referencing Routes**: The `target_route_uuid` field enables history route chains (old URL → newer URL → newest URL)

#### MediaBundle

The media bundle removed some unused tables:

```sql
ALTER TABLE me_file_version_content_languages DROP FOREIGN KEY FK_F3FD652C911ADE33;
ALTER TABLE me_file_version_publish_languages DROP FOREIGN KEY FK_195DAB3C911ADE33;
DROP TABLE me_file_version_content_languages;
DROP TABLE me_file_version_publish_languages;
```

Also the `MediaType` entity has been removed the value has been moved to the `me_media` table itself as a string column:

```sql
ALTER TABLE me_media ADD COLUMN type VARCHAR(10) DEFAULT NULL;
UPDATE me_media m
    JOIN me_media_types mt ON mt.id = m.idMediaTypes
    SET m.type = mt.name;

ALTER TABLE me_media DROP FOREIGN KEY FK_A694E57284671716;
DROP TABLE me_media_types;
DROP INDEX IDX_A694E57284671716 ON me_media;

ALTER TABLE me_media MODIFY type VARCHAR(10) NOT NULL;
ALTER TABLE me_media DROP idMediaTypes;

CREATE INDEX IDX_A694E5728CDE5729 ON me_media (type);
```

#### CategoryBundle

The `CategoryMeta` entity was removed. Drop the table and its foreign key:

```sql
ALTER TABLE ca_category_meta DROP FOREIGN KEY FK_2575BBB0B8075882;
DROP TABLE ca_category_meta;
```

### Removed `SecurityType`

Removed the `Sulu\Bundle\SecurityBundle\Entity\SecurityType` class and its fixtures. This also includes database migrations:

```sql
ALTER TABLE se_roles DROP FOREIGN KEY FK_13B749A0D02106C0;
DROP TABLE se_security_types;
DROP INDEX IDX_13B749A0D02106C0 ON se_roles;
ALTER TABLE se_roles DROP idSecurityTypes;
```

### Groups and User Groups have been removed

This includes the following services:

- `sulu_security.group_repository`
- `sulu_security.group_controller`

These unused parameters have been removed:
- `sulu_security.group_repository.class`
- `sulu_security.entity_group.class`
- `sulu_security.entity.group`

The resource routes has been removed:

```diff
- sulu_security.groups:
-    type: rest
-    name_prefix: sulu_security.
-    resource: sulu_security.group_controller
```

The `se_user_groups` and `se_groups` table were removed from the database:

```sql
ALTER TABLE se_group_roles DROP FOREIGN KEY FK_9713F725937C91EA;
ALTER TABLE se_group_roles DROP FOREIGN KEY FK_9713F725A1FA6DDA;
ALTER TABLE se_groups DROP FOREIGN KEY FK_231E44EC30D07CD5;
ALTER TABLE se_groups DROP FOREIGN KEY FK_231E44ECBF274AB0;
ALTER TABLE se_groups DROP FOREIGN KEY FK_231E44ECDBF11E1D;
ALTER TABLE se_user_groups DROP FOREIGN KEY FK_E43ED0C8347E6F4;
ALTER TABLE se_user_groups DROP FOREIGN KEY FK_E43ED0C8937C91EA;
DROP TABLE se_group_roles;
DROP TABLE se_groups;
DROP TABLE se_user_groups;
```

### Migrate User entity columns

The `idContacts` join column is now nullable to be consistent with the entity property.

```sql
ALTER TABLE se_users CHANGE idContacts idContacts INT DEFAULT NULL;
```

### Migrate on TimestampableInterface depend entities to DateTimeImmutable

The following columns now contain immutable datetime objects:

All `created` and `changed` columns generated by the `TimestampableInterface`.

Run this SQL to migrate the database:

```sql
ALTER TABLE re_references CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE tr_trash_items CHANGE storeTimestamp storeTimestamp DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE ac_activities CHANGE timestamp timestamp DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE pr_preview_links CHANGE lastVisit lastVisit DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE co_accounts CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE co_contacts CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE me_files CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE me_collections CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE me_media CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE me_file_versions CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE se_users CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE se_roles CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE ca_category_translations CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE ca_categories CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE ca_keywords CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
ALTER TABLE ta_tags CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
```

> [!IMPORTANT]
> You need to generate a migration for your own project, since this also effects custom entities in your project.

### Migrate Permission settings

> [!IMPORTANT]
> Doctrine migration cannot detect the following migrations, you need to execute it manually,
> or create a custom migration for it. The following SQL commands are for MySQL-based projects for other used 
> databases you might need to change it.

```sql
UPDATE `se_permissions` SET `context` = 'sulu.article.articles' WHERE `context` = 'sulu.modules.articles';
UPDATE `se_permissions` SET `context` = 'sulu.snippet.snippets' WHERE `context` = 'sulu.global.snippets';
```

### Remove legacy user settings

> [!IMPORTANT]
> Doctrine migration cannot detect the following migrations, you need to execute it manually,
> or create a custom migration for it. The following SQL commands are for MySQL-based projects for other used
> databases you might need to change it.

This step is optional but highly recommended, removing legacy user settings from the database helps ensure compatibility
with the new content storage architecture. Some columns from the old settings may no longer exist in the updated schema.
Retaining outdated data can lead to issues or exceptions in the admin interface, especially if user settings reference
fields that are no longer available.

To safely remove these obsolete settings, execute the following SQL commands:

```sql
DELETE FROM se_user_settings WHERE settingsKey LIKE 'sulu_admin.list_store.articles%';
DELETE FROM se_user_settings WHERE settingsKey LIKE 'sulu_admin.list_store.snippets%';
DELETE FROM se_user_settings WHERE settingsKey LIKE 'sulu_admin.list_store.pages%';
```

### Updating to flysystem 3

The Sulu media storage uses now the Flysystem Bundle which need to be configured:

```yaml
# config/packages/flysystem.yaml
flysystem:
    storages:
        default.storage:
            adapter: 'local'
            options:
                directory: '%kernel.project_dir%/var/uploads/media'
```

Here are some examples on how to migrate individual providers:

<details>
  <summary>Sulu Media S3 to Flysystem S3</summary>

Before:

```yaml
sulu_media:
    storage: s3
    storages:
        s3:
            key: 'your aws s3 key'
            secret: 'your aws s3 secret'
            bucket_name: 'your aws s3 bucket name'
            path_prefix: 'optional/path/prefix'
            public_url: 'http://some_cdn.com'
            region: 'eu-west-1'
```

New:

```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'aws'
            public_url: 'http://some_cdn.com/optional/path/prefix' # Public url now also has to contain the prefix
            options:
                client: 'aws_client_service' # see service below
                key: ''
                secret: ''
                bucket: 'bucket_name'
                prefix: 'optional/path/prefix'
                streamReads: true
services:
    aws_client_service:
        class: Aws\\S3\\S3Client
        arguments:
            - [] # Put the value of the parameter "sulu_media.media.storage.s3.arguments" here (empty array by default)
```

</details>

<details>
  <summary>Sulu Google Cloud Config to Flysystem S3</summary>

> [!NOTE]  
> If you were using `superbalist/flysystem-google-storage` replace it with the official package version `league/flysystem-google-cloud-storage`.

Before:

```yaml
sulu_media:
    storage: google_cloud
    storages:
        google_cloud:
            key_file_path: '/path/to/key.json'
            bucket_name: 'sulu-bucket'
            path_prefix: 'optional path prefix'
```

New:

```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'gcloud'
            options:
                client: 'gcloud_client_service' # The service ID of the Google\\Cloud\\Storage\\StorageClient instance
                bucket: 'bucket_name'
                prefix: 'optional/path/prefix'
```

</details>

<details>
  <summary>Sulu Azure Config to Flysystem Config</summary>

Before:

```yaml
sulu_media:
    storage: azure_blob
    storages:
        azure_blob:
            connection_string: 'your azure connection string'
            container_name: 'your azure container name'
            path_prefix: 'optional path prefix'
```

New:

```yaml
flysystem:
    storages:
        default.storage:
            adapter: 'azure'
            options:
                client: 'azure_client_service' # The service ID of the MicrosoftAzure\Storage\Blob\BlobRestProxy instance
                container: 'container_name'
                prefix: 'optional/path/prefix'
```

</details>

If you want use a [different storage](https://github.com/thephpleague/flysystem-bundle/blob/3.x/docs/2-cloud-storage-providers.md) for Sulu you can configure it here:

```yaml
# config/packages/sulu_media.yaml
sulu_media:
    storage:
        flysystem_service: 'default.storage' # this is default and not required to be configured.
```

This will only create the service `sulu_media.storage` as the alias to `sulu_media.storage.*` services has been removed.

### PHPCR, Jackalope and Document Manager related service got removed

A completely new content storage was written for Sulu 3.0. Instead of `PHPCR` and `Jackalope`,
the new content storage uses `Doctrine ORM` entities, which most Symfony developers should be more familiar with.

So, all queries you previously wrote with `PHPCR` and `Jackalope` now need to be rewritten using the `Doctrine ORM Query Builder`.
Take a look at examples in the repositories of the new entities.

Also, the DocumentManager and its functions were removed.
The new bundles use a hexagonal architecture and a command bus to dispatch commands for creating, editing, publishing, and unpublishing content.

Here are some quick examples of the most commonly used functions:

 - `DocumentManager::find`
    - `PageRepositoryInterface::getOneBy(['uuid' => $uuid])`
    - `ArticleRepository::getOneBy(['uuid' => $uuid])`
    - `SnippetRepository::getOneBy(['uuid' => $uuid])`
    - To modify something you no longer load it, instead use the `Modify...` messages listed below.
 - `DocumentManager::create`
    - `MessageBusInterface::dispatch(new Envelope(new CreatePageMessage($webspaceKey, $parentId, $data), [new EnableFlushStamp()]));`
    - `MessageBusInterface::dispatch(new Envelope(new CreateArticleMessage($data), [new EnableFlushStamp()]));`
    - `MessageBusInterface::dispatch(new Envelope(new CreateSnippetMessage($data), [new EnableFlushStamp()]));`
 - `DocumentManager::find` and `edit`:
    - `MessageBusInterface::dispatch(new Envelope(new ModifyPageMessage(['uuid' => $uuid], $data), [new EnableFlushStamp()]));`
    - `MessageBusInterface::dispatch(new Envelope(new ModifyArticleMessage(['uuid' => $uuid], $data), [new EnableFlushStamp()]));`
    - `MessageBusInterface::dispatch(new Envelope(new ModifySnippetMessage(['uuid' => $uuid], $data), [new EnableFlushStamp()]));`
 - `DocumentManager::publish`:
    - `MessageBusInterface::dispatch(new Envelope(new ApplyWorkflowTransitionPageMessage(['uuid' => $uuid], $locale, WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH), [new EnableFlushStamp()]));`
    - `MessageBusInterface::dispatch(new Envelope(new ApplyWorkflowTransitionArticleMessage(['uuid' => $uuid], $locale, WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH), [new EnableFlushStamp()]));`
    - `MessageBusInterface::dispatch(new Envelope(new ApplyWorkflowTransitionSnippetMessage(['uuid' => $uuid], $locale, WorkflowInterface::WORKFLOW_TRANSITION_PUBLISH), [new EnableFlushStamp()]));`
 - `DocumentManager::unpublish`:
    - `MessageBusInterface::dispatch(new Envelope(new ApplyWorkflowTransitionPageMessage(['uuid' => $uuid], $locale, WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH), [new EnableFlushStamp()]));`
    - `MessageBusInterface::dispatch(new Envelope(new ApplyWorkflowTransitionArticleMessage(['uuid' => $uuid], $locale, WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH), [new EnableFlushStamp()]));`
    - `MessageBusInterface::dispatch(new Envelope(new ApplyWorkflowTransitionSnippetMessage(['uuid' => $uuid], $locale, WorkflowInterface::WORKFLOW_TRANSITION_UNPUBLISH), [new EnableFlushStamp()]));`

Instead of DocumentManager events use Doctrine ORM event listeners or listen to the domain events provided by 
the new bundles. You will find them in the `src/Domain/Event` directory of the different bundles under `/packages/*`.

If you want deeper understand the new storage works under the hood checkout following presentation from SymfonyCon 2024:
[From Translations to Multi Dimension Entities](https://speakerdeck.com/alexanderschranz/from-translations-to-multi-dimension-entities).

### Preview Services changed

Most of the services in the PreviewBundle are now internal. The new `PreviewDefaultsProviderInterface` is
now the primary means of interacting with the bundle.

### Metadata now locale independent

The Sulu Metadata classes `ItemMetadata`, `SectionMetadata`, `FieldMetadata`, `OptionMetadata` and `FormMetadata` are
now independent of the locale and contain all metadata for all locales this means setter and getter changed.

```diff
// getter
-$metadata->getLabel();
+$metadata->getLabel($locale);
-$metadata->getTitle();
+$metadata->getTitle($locale);
-$metadata->getDescription();
+$metadata->getDescription($locale);
-$metadata->getPlaceholder();
+$metadata->getPlaceholder($locale);
// setter
-$metadata->setLabel($label);
+$metadata->setLabel($label, $locale);
-$metadata->setTitle($title);
+$metadata->setTitle($title, $locale);
-$metadata->setDescription($description);
+$metadata->setDescription($description, $locale);
-$metadata->setPlaceholder($placeholder);
+$metadata->setPlaceholder($placeholder, $locale);
```

### Template path configuration changed

**Old**:

```yaml
sulu_core:
    content:
        structure:
            default_type:
                snippet: 'my_snippet_key'
            paths:
                snippet_project_a:
                    path: '%kernel.project_dir%/config/templates/snippets/projectA'
                    type: snippet
```

**New**:

```yaml
sulu_admin:
    templates:
        snippet:
            default_type: 'my_snippet_key'
            directories:
                snippet_project_a: '%kernel.project_dir%/config/templates/snippets/projectA'
```

### ResourceLocator endpoint changed

The ResourceLocator endpoint was moved from PageBundle to the new RouteBundle.  
It changed from `/admin/api/resourcelocators?action=generate` to `/admin/api/resource-locators`.
Also, the endpoint no longer expects `entityClass` or `entityId` instead the `resourceKey` and `resourceId` are expected.

Also the `generateUrl` from the `Config` variable was removed if you want to receive it in your JS you need to
register a hook via `initializer.addUpdateConfigHook('sulu_route', (config, initialized) => {});`.

### FOSRestRouting Bundle removed

As announced in Sulu [2.6.10](https://github.com/sulu/sulu/blob/2.6/UPGRADE-2.x.md)
the `type: rest` / [FOSRestRouting](https://github.com/handcraftedinthealps/RestRoutingBundle) was removed.

The `HandcraftedInTheAlps\RestRoutingBundle\RestRoutingBundle` class should be removed from your `config/bundles.php`.

Use the `.yaml` extension instead of the old `.yml` for routing files in your `config/routes/` files without `type: rest`:

Example:

```diff
sulu_tag_api:
-    resource: "@SuluTagBundle/Resources/config/routing_api.yml"
+    resource: "@SuluTagBundle/Resources/config/routing_api.yaml"
-    type: rest
     prefix:   /admin/api
```

### Upgrade resourceLocator and route property type

The new content structure used in Sulu 3.0 requires that all the `resource_locator` or `route` properties must be
renamed to `url` in your templates and use the `route` always.

If previously your route for articles or custom entities were generated on the backend it is now done on the frontend,
at least one property, mostly the `title`, requires the tag `sulu.rlp.part` to be added to trigger the route generation
during unfocus of that tagged property.

Also the route property is recommended to be `mandatory` to prevent saving without any generated URL:

```diff
        <property name="title" type="text_line" mandatory="true">
             <meta>
                 <title lang="en">Title</title>
                 <title lang="de">Titel</title>
             </meta>
             <params>
                 <param name="headline" value="true"/>
             </params>

+            <tag name="sulu.rlp.part"/>
         </property>

-        <property name="routePath" type="route">
+        <property name="url" type="route" mandatory="true">
            <meta>
                <title lang="en">Resourcelocator</title>
                <title lang="de">Adresse</title>
            </meta>

-            <tag name="sulu_article.article_route"/>
+            <tag name="sulu.rlp"/>
        </property>
```

To make things easier between pages and articles the `resource_locator` field type is now replaced by the `route` 
field type, so all routable entities use the same field type:

```diff
-        <property name="url" type="resource_locator">
+        <property name="url" type="route" mandatory="true">
            <meta>
                <title lang="en">Resourcelocator</title>
                <title lang="de">Adresse</title>
            </meta>
            
            <tag name="sulu.rlp"/>
        </property>
```

The `ResourceLocator` JS React Component now uses `tree_leaf_edit` and `tree_full_edit` instead of `leaf` and `full`,
to be consistent with the PHP naming.

### Route mapping configuration moved to templates

Previously for articles or custom entities you could configure the route generation via a config.
This was removed, and you now define the schema via the `route_schema` param in your articles template.
Also, the `entity_class` param from the previous route mapping configuration is no longer needed in this setup:

```diff
# config/packages/sulu_route.yaml
-sulu_route:
-    mappings:
-        Sulu\Bundle\ArticleBundle\Document\ArticleDocument:
-            generator: schema
-            options:
-                route_schema: '/blog/{implode("-", object)}'
```

```diff
        <!-- config/templates/articles/your_template.xml -->

        <property name="title" type="text_line" mandatory="true">
            <meta>
                <title lang="en">Title</title>
                <title lang="de">Titel</title>
            </meta>
            <params>
                <param name="headline" value="true"/>
            </params>

+           <tag name="sulu.rlp.part"/>
        </property>

-       <property name="url" type="route">
+       <property name="url" type="route" mandatory="true">
            <meta>
                <title lang="en">Resourcelocator</title>
                <title lang="de">Adresse</title>
            </meta>
            
+           <params>
-               <param name="entity_class" value="Sulu\Bundle\ArticleBundle\Document\ArticleDocument"/>
+               <param name="route_schema" value="/blog/{implode('-', object)}"/>
+           </params>

+           <tag name="sulu.rlp"/>
        </property>
```

The `sulu.rlp.part` tag on the title property is required — it tells the admin frontend which fields
to use as inputs when generating the URL suggestion. Without it, `route_schema` has no effect because
URL generation is never triggered. The `sulu.rlp` tag marks the field that stores the generated URL.

It is also supported by the `page_tree_route` which still uses the selected page as prefixed URL.

### Upgrade the Controller references

The controller in the page/article templates have to be adjusted use the new controller
from the SuluContentBundle. Be aware that also your custom controllers have to be modified to extend from the new one.

```diff
-        <controller class="Sulu\Bundle\ArticleBundle\Controller\ArticleController"/>
-        <controller>Sulu\Bundle\WebsiteBundle\Controller\DefaultController::indexAction</controller>
+        <controller>Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction</controller>
```

### Page, CustomUrl, Snippet, Route, Search JS includes path changed

Some bundles have undergone path changes, requiring corresponding updates to their JS package
paths. This step is normally automatically handled by the `bin/console sulu:admin:update-build` command,
kept here for completeness:

```diff
-        "sulu-custom-url-bundle": "file:../../vendor/sulu/sulu/src/Sulu/Bundle/CustomUrlBundle/Resources/js",
+        "sulu-custom-url-bundle": "file:../../vendor/sulu/sulu/packages/custom-url/assets/js",
-        "sulu-page-bundle": "file:../../vendor/sulu/sulu/src/Sulu/Bundle/PageBundle/Resources/js",
+        "sulu-page-bundle": "file:../../vendor/sulu/sulu/packages/page/assets/js",
-        "sulu-search-bundle": "file:../../vendor/sulu/sulu/src/Sulu/Bundle/SearchBundle/Resources/js",
+        "sulu-search-bundle": "file:../../vendor/sulu/sulu/packages/search/assets/js",
-        "sulu-route-bundle": "file:../../vendor/sulu/sulu/src/Sulu/Bundle/RouteBundle/Resources/js",
+        "sulu-route-bundle": "file:../../vendor/sulu/sulu/packages/route/assets/js",
-        "sulu-snippet-bundle": "file:../../vendor/sulu/sulu/src/Sulu/Bundle/SnippetBundle/Resources/js",
+        "sulu-snippet-bundle": "file:../../vendor/sulu/sulu/packages/snippet/assets/js",
```

### SnippetArea Admin requires defined areas

The `SnippetAreaAdmin` service is now automatically unregistered when no snippet areas are defined in your
snippet templates. If you want the snippet areas tab to appear in the admin interface, you need to define
at least one area in your snippet template XML files.

Snippet template example with area definition:

```xml
<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template-1.0.xsd">
    <key>default</key>

    <areas>
        <area key="footer">
            <title lang="en">Footer</title>
            <title lang="de">Fußzeile</title>
        </area>
    </areas>

    <!-- ... rest of your template ... -->
</template>
```

If you previously relied on the snippet areas functionality without defining areas, you must now explicitly
define them in your snippet templates located in `config/templates/snippets/`.

### Removing deprecated twig functions

- `sulu_meta_alternate` (use the SEO template instead `@SuluWebsite/Extension/seo.html.twig`)
- `sulu_meta_seo` (use the SEO template instead `@SuluWebsite/Extension/seo.html.twig`)
- `sulu_seo` (use the SEO template instead `@SuluWebsite/Extension/seo.html.twig`)

This also includes the container services:

- `sulu_website.twig.meta`
- `sulu_website.twig.seo`
  and the parameters
- `%sulu_website.twig.meta.class%`
- `%sulu_website.twig.seo.class%`

### Remove deprecated sulu_website twig attributes configuration

The configuration for the Twig attribute in `config/packages/sulu_website.yaml` needs to be removed.

```diff
sulu_website:
-    twig:
-        attributes:
-            urls: false
-            path: false
```

If you have nothing else configured, the whole file can be removed.

### Remove `use_deprecated_object_data_format`

For example in the account selection:
```diff
 <property name="parent" type="single_account_selection" colspan="6">
     <meta>
         <title>sulu_contact.parent_company</title>
     </meta>

-    <params>
-        <param name="use_deprecated_object_data_format" value="true" />
-    </params>
 </property>
```

### Removing "modules" from Permissions

The unused column on the permissions table has been removed. This also requires some migration on the table, to recreate
an index:

```sql
DROP INDEX UNIQ_5CEC3EEAE25D857EC242628A1FA6DDA ON se_permissions;
ALTER TABLE se_permissions DROP module;
CREATE UNIQUE INDEX UNIQ_5CEC3EEAE25D857EA1FA6DDA ON se_permissions (context, idRoles);
```

### The StructureMetadataFactory service removed

The StructureMetadataFactory service was removed and replaced by the MetadataProviderRegistry.
So a few constructor of specific classes has been changed:

- `Sulu\Component\Webspace\Manager\WebspaceManager` 

### ReferenceStore Refactoring

The ReferenceStore classes have been refactored and moved from the `WebsiteBundle` to the `HttpCacheBundle` to improve cache tag handling architecture.

**Moved classes**

- `Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore` → `Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore`
- `Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface` → `Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface`

**Removed classes**

- `Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreNotExistsException`
- `Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePool`
- `Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStorePoolInterface`
- `Sulu\Bundle\WebsiteBundle\ReferenceStore\WebspaceReferenceStore`

**Interface changes**

The `ReferenceStoreInterface::add()` method signature has changed:

```diff
- public function add($id);
+ public function add(string $id, string $resourceKey);
```

**Architecture changes**

Previously, each bundle registered its own ReferenceStore with a specific alias (e.g., "page", "article", "snippet"). This approach has been consolidated to use a single ReferenceStore throughout the application. All bundle-specific ReferenceStore registrations have been removed.

**Service definition migration example**

Before (bundle-specific ReferenceStore):

```yaml
# config/services.yaml or bundle's services.xml
services:
    app.my_service:
        arguments:
            - '@sulu_page.reference_store.content'  # or any other bundle-specific alias
```

After (global ReferenceStore):

```yaml
# config/services.yaml
services:
    app.my_service:
        arguments:
            - '@sulu_http_cache.reference_store'  # Use the single global reference store
```

If you were injecting the ReferenceStore in PHP:
```diff
- use Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface;
+ use Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface;

class MyService
{
    public function __construct(ReferenceStoreInterface $referenceStore)
    {
        // ...
    }
}
```

**Impact:** If you have custom code that directly uses the old ReferenceStore classes from the WebsiteBundle, you need to update your imports to use the new classes from the HttpCacheBundle. Update any calls to `add($id)` method to include the `$resourceKey` parameter: `add($id, $resourceKey)`. Remove any custom ReferenceStore registrations that were specific to individual bundles, as the application now uses a single centralized ReferenceStore. The removed classes are no longer available and their functionality has been consolidated into the refactored architecture.

### Removed deprecations for 3.0

Removed classes / services / interfaces / traits:

- `Sulu\Bundle\MarkupBundle\Listener\SwiftMailerListener`
- `Sulu\Bundle\DocumentManagerBundle\Slugifier\Urlizer`
- `Sulu\Bundle\CategoryBundle\DependencyInjection\DeprecationCompilerPass`
- `Sulu\Bundle\CategoryBundle\Entity\CategoryMeta` (unused in admin UI)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryMetaInterface` (unused in admin UI)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepository` (unused in admin UI)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryMetaRepositoryInterface` (unused in admin UI)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryInterface::getMeta()` (unused in admin UI)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryInterface::addMeta()` (unused in admin UI)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryInterface::removeMeta()` (unused in admin UI)
- `Sulu\Bundle\SecurityBundle\DataFixtures\ORM\LoadSecurityTypes`
- `Sulu\Bundle\SecurityBundle\Controller\ContextsController`
- `Sulu\Bundle\SecurityBundle\Security\Exception\TokenAlreadyRequestedException`
- `Sulu\Component\Rest\Listing\ListQueryBuilder`
- `Sulu\Component\Rest\Listing\ListRepository`
- `Sulu\Component\Rest\Listing\ListRestHelper`
- `Sulu\Component\SmartContent\Orm\DataProviderRepositoryInterface`
- `Sulu\Component\SmartContent\Orm\DataProviderRepositoryTrait`
- `Sulu\Bundle\CoreBundle\Controller\LocalizationController`
- `Sulu\Bundle\CoreBundle\Cache\StructureWarmer`
- `Sulu\Bundle\MediaBundle\Media\Storage\AzureBlobStorage`
- `Sulu\Bundle\MediaBundle\Media\Storage\GoogleCloudStorage`
- `Sulu\Bundle\MediaBundle\Media\Storage\LocalStorage`
- `Sulu\Bundle\MediaBundle\Media\Storage\S3Storage`
- `Sulu\Bundle\MediaBundle\DependencyInjection\S3ClientCompilerPass` (internal)
- `Sulu\Bundle\MediaBundle\Content\MediaSelectionContainer`
- `Sulu\Bundle\MediaBundle\Content\Types\CollectionSelection`
- `Sulu\Bundle\MediaBundle\Content\Types\MediaSelectionContentType`
- `Sulu\Bundle\MediaBundle\Content\Types\ImageMapContentType`
- `Sulu\Bundle\MediaBundle\Content\Types\SingleCollectionSelection`
- `Sulu\Bundle\MediaBundle\Content\Types\SingleMediaSelection`
- `Sulu\Bundle\MediaBundle\Content\Entity\MediaDataProviderRepository`
- `Sulu\Bundle\AdminBundle\Command\DownloadBuildCommand`
- `Sulu\Component\Rest\ListBuilder\ListRepresentation`
- `Sulu\Bundle\AdminBundle\Metadata\FormMetadata\LocalizedFormMetadataCollection`
- `Sulu\Component\Content\Metadata\XmlParserTrait` (internal)
- `Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser` (moved and internal)
- `Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\TextPropertyMetadataMapper` (moved and internal)
- `Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SelectionPropertyMetadataMapper` (moved and internal)
- `Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SingleSelectionPropertyMetadataMapper` (moved and internal)
- `ContentTypes` implementing `PropertyMetadataMapperInterface` (moved and internal)
- `Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\AbstractLoader` (moved and internal)
- `Sulu\Component\Content\Document\Subscriber\Compat\ContentMapperSubscriber`
- `Sulu\Component\Content\Mapper\ContentEvents`
- `Sulu\Component\Content\Mapper\Event\ContentNodeDeleteEvent`
- `Sulu\Component\Content\Mapper\Event\ContentNodeEvent`
- `Sulu\Component\Content\Mapper\Event\ContentNodeOrderEvent`
- `Sulu\Bundle\WebsiteBundle\Controller\DefaultController` (use new `ContentController` instead)
- `Sulu\Bundle\WebsiteBundle\Controller\WebsiteController` (use new `ContentController` instead)
- `Sulu\Bundle\ContactBundle\Content\Types\AccountSelection`
- `Sulu\Bundle\ContactBundle\Content\Types\ContactAccountSelection`
- `Sulu\Bundle\ContactBundle\Content\Types\ContactSelection`
- `Sulu\Bundle\ContactBundle\Content\Types\SingleContactSelection`
- `Sulu\Bundle\ContactBundle\Content\Types\SingleAccountSelection`
- `Sulu\Bundle\CoreBundle\DataFixtures\ReplacerXmlLoader`
- `Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\ReplacersCompilerPass`
- `Sulu\Bundle\PageBundle\Routing\DecoratedContentRouteProvider`
- `Sulu\Bundle\TagBundle\Content\Types\TagSelection`
- `Sulu\Bundle\WebsiteBundle\DependencyInjection\Compiler\RouteProviderCompilerPass`
- `Sulu\Bundle\WebsiteBundle\Twig\Navigation\MemoizedNavigationTwigExtension`
- `Sulu\Bundle\WebsiteBundle\Twig\Navigation\NavigationTwigExtension`
- `Sulu\Bundle\WebsiteBundle\Twig\Navigation\NavigationTwigExtensionInterface`
- `Sulu\Component\Persistence\EventSubscriber\ORM\MetadataSubscriber` (internal)
- `Sulu\Bundle\WebsiteBundle\Navigation\NavigationQueryBuilder`
- `Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapper`
- `Sulu\Bundle\WebsiteBundle\Navigation\NavigationMapperInterface`
- `Sulu\Bundle\WebsiteBundle\Navigation\NavigationItem`
- `Sulu\Bundle\WebsiteBundle\Sitemap\SitemapContentQueryBuilder`
- `Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGenerator`
- `Sulu\Bundle\WebsiteBundle\Sitemap\SitemapGeneratorInterface`
- `Sulu\Bundle\WebsiteBundle\Twig\Sitemap\MemoizedSitemapTwigExtension`
- `Sulu\Bundle\WebsiteBundle\Twig\Sitemap\SitemapTwigExtension`
- `Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathInterface`
- `Sulu\Bundle\WebsiteBundle\Twig\Content\ContentPathTwigExtension` (moved and internal)
- `Sulu\Bundle\WebsiteBundle\Twig\Content\ContentTwigExtension`
- `Sulu\Bundle\WebsiteBundle\Twig\Content\ContentTwigExtensionInterface`
- `Sulu\Bundle\WebsiteBundle\Twig\Content\MemoizedContentTwigExtensionInterface`
- `Sulu\Bundle\WebsiteBundle\Twig\Content\ParentNotFoundException`
- `Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolver`
- `Sulu\Bundle\WebsiteBundle\Resolver\ParameterResolverInterface`
- `Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SingleSelectionPropertyMetadataMapper` (moved and internal)
- `Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderInterface` (use new `PreviewDefaultsProviderInterface` instead)
- `Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistryInterface` (internal)
- `Sulu\Bundle\PreviewBundle\Preview\Object\PreviewObjectProviderRegistry` (internal)
- `Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewKernel` (internal)
- `Sulu\Bundle\PreviewBundle\Preview\Renderer\KernelFactoryInterface` (internal)
- `Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRendererInterface` (internal)
- `Sulu\Bundle\PreviewBundle\Preview\Renderer\PreviewRenderer` (internal)
- `Sulu\Bundle\PreviewBundle\Preview\Renderer\WebsiteKernelFactory` (internal)
- `Sulu\Bundle\PreviewBundle\Preview\Preview` (internal)
- `Sulu\Bundle\PreviewBundle\Preview\PreviewCache` (internal)
- `Sulu\Bundle\PreviewBundle\Preview\PreviewCacheItem` (internal)
- `Sulu\Bundle\PreviewBundle\UserInterface\Controller\PreviewController` (internal)
- `Sulu\Bundle\PreviewBundle\UserInterface\Controller\PreviewLinkController` (internal)
- `Sulu\Bundle\PreviewBundle\UserInterface\Controller\PublicPreviewController` (internal)
- `Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\SuluVersionPass` (moved and internal)
- `Sulu\Bundle\PageBundle\Controller\WebspaceController`
- `Sulu\Bundle\SecurityBundle\Security\LogoutSuccessHandler` (replaced by `LogoutEventSubscriber`)
- `Sulu\Bundle\SecurityBundle\EventListener\AuthenticationFailureListener` (moved and internal)
- `Sulu\Bundle\SecurityBundle\DependencyInjection\Compiler\AccessControlProviderPass` (use tagged_iterator)
- `Sulu\Bundle\MarkupBundle\DependencyInjection\Compiler\ParserCompilerPass` (use tagged_iterator)
- `Sulu\Bundle\MediaBundle\DependencyInjection\FormatCacheClearerCompilerPass` (use tagged_iterator)
- `Sulu\Component\Security\Authorization\AccessControl\SecuredEntityRepositoryTrait`
- `Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddAdminPass` (replaced by a `tagged_iterator`)
- `Sulu\Bundle\AdminBundle\DependencyInjection\Compiler\AddMetadataProviderPass` (replaced by a `tagged_locator`)
- `Sulu\Bundle\AudienceTargetingBundle\DependencyInjection\Compiler\AddRulesPass` -> (`sulu.audience_target_rule`)
- `Sulu\Bundle\CoreBundle\DependencyInjection\Compiler\RegisterLocalizationProvidersPass` (`sulu.localization_provider`)
- `Sulu\Component\Symfony\CompilerPass\TaggedServiceCollectorCompilerPass` (replaced by symfony's tagged_iterator)
- `Sulu\Bundle\MediaBundle\DependencyInjection\ImageTransformationCompilerPass` (replaced by `tagged_locator`)
- `Sulu\Bundle\PreviewBundle\Preview\PreviewCache` -> (using Symfony cache now)
- `sulu_contact.twig.cache`, `sulu_core.cache.memoize.cache`, `sulu_security.twig_extension.user.cache` -> ArrayAdapter of Symfony caching

Removed deprecated functions and properties:

- `Sulu\Component\Security\Event\PermissionUpdateEvent::getSecurityIdentity` (use `getPermissions()` instead)
- `Sulu\Component\Webspace\Portal::getXDefaultLocalization` (use `getDefaultLocalizations()` instead)
- `Sulu\Component\Webspace\Portal::setXDefaultLocalization` (use `setDefaultLocalization()` instead)
- `Sulu\Component\Localization\Localization::isXDefault` (use `isDefault()` instead)
- `Sulu\Bundle\ContactBundle\Controller\AccountController::$contactEntityKey` (use `Contact::class` instead)
- `Sulu\Bundle\ContactBundle\Controller\AccountController::$entityKey` (use `Account::class` instead)
- `Sulu\Bundle\WebsiteBundle\Controller\AnalyticsController::$entityKey` (use `Analytics::class` instead)
- `Sulu\Bundle\CategoryBundle\Controller\CategoryController::$entityKey` (use `Category::class` instead)
- `Sulu\Bundle\MediaBundle\Controller\CollectionController::$entityKey` (use `Collection::class` instead)
- `Sulu\Bundle\MediaBundle\Controller\MediaController::$entityKey` (use `Media::class` instead)
- `Sulu\Bundle\PageBundle\Controller\PageController::$entityKey` (use `Page::class` instead)
- `Sulu\Bundle\TagBundle\Controller\TagController::$entityKey` (use `Tag::class` instead)
- `Sulu\Bundle\SecurityBundle\Controller\UserController::$entityKey` (use `User::class` instead)
- `Sulu\Bundle\ContactBundle\Controller\ContactTitleController::$entityKey` (use `ContactTitle::class` instead)
- `Sulu\Bundle\ContactBundle\Controller\ContactController::$entityKey` (use `Contact::class` instead)
- `Sulu\Bundle\ContactBundle\Controller\PositionController::$entityKey` (use `Position::class` instead)
- `Sulu\Bundle\WebsiteBundle\Controller\RedirectController::redirectWebspaceAction` (use Symfony RedirectController instead)
- `Sulu\Component\Cache\Memoize::memoize()`
- `Sulu\Component\Cache\MemoizeInterface::memoize()`
- `Sulu\Component\Webspace\PortalInformation::getSegment()`
- `Sulu\Component\Webspace\PortalInformation::setSegment()`
- `Sulu\Bundle\WebsiteBundle\Twig\Core\UtilTwigExtension::extract()`
- `Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata::setName()` (use `setKey()` instead)
- `Sulu\Bundle\AdminBundle\Metadata\FormMetadata\FormMetadata::getName()` (use `getKey()` instead)
- `Sulu\Bundle\ContactBundle\Contact\AccountManager::findAll` (use Repository instead)
- `Sulu\Bundle\ContactBundle\Entity\AccountRepository::findByFilter` (use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\ContactBundle\Entity\AccountRepositoryInterface::findByFilter`(use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\ContactBundle\Entity\ContactRepository::findByFilter` (use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\ContactBundle\Entity\ContactRepository::findByExcludedAccountId` (use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\CategoryBundle\Category\CategoryManager::find` (use Repository instead)
- `Sulu\Bundle\CategoryBundle\Category\CategoryManager::findChildren` (use Repository instead)
- `Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface::find` (use Repository instead)
- `Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface::findChildren` (use Repository instead)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryRepository::findByCategoryIds` (use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryRepository::findCategories` (use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryRepository::findChildren` (use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface::findByCategoryIds` (use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface::findCategories` (use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\CategoryBundle\Entity\CategoryRepositoryInterface::findChildren` (use custom Doctrine queryBuilder instead)
- `Sulu\Bundle\CategoryBundle\Entity\Category::addChildren` (use `addChild()` instead)
- `Sulu\Bundle\CategoryBundle\Entity\Category::removeChildren` (use `removeChild()` instead)
- `Sulu\Bundle\AdminBundle\Admin\View\FormOverlayListViewBuilder::setRequestParameters` (use `addRequestParameters()` instead)
- `Sulu\Component\Rest\ListBuilder\ListBuilderInterface::setFields` (use `setSelectFields()` instead)
- `Sulu\Component\Rest\ListBuilder\ListBuilderInterface::addField` (use `addSelectField()` instead)
- `Sulu\Component\Rest\ListBuilder\ListBuilderInterface::hasField` (use `hasSelectField()` instead)
- `Sulu\Component\Rest\ListBuilder\ListBuilderInterface::whereNot` (use `where()` instead)
- `Sulu\Component\Rest\ListBuilder\AbstractListBuilder::setFields` (use `setSelectFields()` instead)
- `Sulu\Component\Rest\ListBuilder\AbstractListBuilder::addField` (use `addSelectField()` instead)
- `Sulu\Component\Rest\ListBuilder\AbstractListBuilder::hasField` (use `hasSelectField()` instead)
- `Sulu\Component\Rest\ListBuilder\AbstractListBuilder::whereNot` (use `where()` instead)
- `Sulu\Bundle\Security\Entity\Role::getRole` (use `getIdentifier` instead)
- `Sulu\Bundle\AdminBundle\Admin\AdminPool::addAdmin` (use dependency injection via tagged service instead)
- `Sulu\Bundle\MediaBundle\Controller\MediaController::__construct` (MediaListBuilder and Representation are required
and some parameters have been removed)
- `Sulu\Bundle\AdminBundle\Metadata\MetadataProviderRegistry::addMetadataProvider` (use dependency injection via tagged service instead)
- `Sulu\Bundle\TagBundle\Entity\TagRepository::findAllTags` (use `findAll` instead)
- `Sulu\Bundle\TagBundle\Tag\TagManager::findAll` (use repository instead)
- `Sulu\Bundle\TagBundle\Tag\TagManagerInterface::resolveTagIds` (use the Repository)
- `Sulu\Bundle\TagBundle\Tag\TagManagerInterface::resolveTagNames` (use the Repository)
- `Sulu\Bundle\MediaBundle\Controller\AbstractMediaController::getTitleFromUpload` (use MediaManager function)

Removed unused arguments:

- `Sulu\Component\Webspace\Analyzer\Attributes\WebsiteRequestProcessor::__construct` `$contentMapper` (2nd argument) removed
- `Sulu\Bundle\SecurityBundle\UserManager\UserManager::__construct` `$groupRepository` (4th argument) removed
- `Sulu\Bundle\SecurityBundle\Admin\SecurityAdmin::__construct` `$urlGenerator` (3rd argument) removed
- `Sulu\Bundle\ContactBundle\Controller\ContactController::__construct` `$contactRepository` (7th argument) removed
- `Sulu\Bundle\ContactBundle\Controller\ContactController::__construct` `$userRepository` (9th argument) removed
- `Sulu\Bundle\ContactBundle\Controller\ContactController::__construct` `$suluSecuritySystem` (12th argument) removed
- `Sulu\Component\Webspace\PortalInformation::__construct` `$segment` (6th argument) removed

Removed kernel parameters:

- All `sulu_*.class` parameters for services where removed (use compilerpasses to replace class of a service definition)
- Parameter `%permissions%` was replaced in favor of `%sulu_security.permissions%`
- The `%sulu.common_cache_dir%` dir was replace by `%kernel.share_dir%`

Removed JavaScript files and methods:

 - `sulu-security-bundle/stores/securityContextStore/securityContextStore.js` `loadSecurityContextGroups`
 - `sulu-security-bundle/stores/securityContextStore/securityContextStore.js` `loadAvailableActions`
 - `sulu-admin-bundle/containers/List/loadingStrategies/FullLoadingStrategy.js`
 - `sulu-admin-bundle/stores/localizationStore/localizationStore.js` `loadLocalizations`
 - `sulu-page-bundle/stores/webspaceStore/webspaceStore.js` `loadWebspaces`
 - `sulu-page-bundle/stores/webspaceStore/webspaceStore.js` `loadWebspace`
 - `sulu-admin-bundle/Resources/js/components/PermissionHint/PermissionHint.js` -> `Hint`

Removed container parameters:

- `sulu_security.security_types.fixture`
- `sulu_media.media.max_file_size` (replaced by `sulu_media.media.max_filesize`)
- `sulu_media.adobe_creative_key`
- `sulu_media.format_manager.blocked_file_types` (replaced by `sulu_media.media.blocked_file_types`)

### Native PHP types for entities and interfaces

All entity classes and their corresponding interfaces have been updated to use native PHP type declarations instead of PHPDoc annotations. This includes parameter types, return types, and property types across all bundles.

**Impact:** If you extend any Sulu entity or implement any Sulu interface, you must update your code to include the same native type declarations. PHP will throw fatal errors if child classes or interface implementations have incompatible type signatures.

### Added return type hints

The following methods have been updated with a return type hint:

- `CacheLifetimeRequestStore::setCacheLifetime()` now returns `void`
- `TimestampableInterface::getCreated()`: now returns `\DateTimeImmutable`
- `TimestampableInterface::getChanged()`: now returns `\DateTimeImmutable`
- `UserBlameInterface::getCreator()`: now returns `?UserInterface`
- `UserBlameInterface::getChanger()`: now returns `?UserInterface`
- `NavigationItem::current()`: returns `NavigationItem`
- `NavigationItem::next()`: returns `void`
- `NavigationItem::key()`: returns `int`
- `NavigationItem::valid()`: returns `bool`
- `NavigationItem::rewind()`: returns `void`
- `AddressType::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `ContactType::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `EmailType::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `FaxType::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `PhoneType::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `Position::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `SocialMediaProfileType::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `UrlType::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `PropertyParameter::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `Localization::jsonSerialize()`: returns `array` (see its typehint for the exact shape)
- `WebspaceCollection::getIterator():` returns `\Traversable`
- `LinkProviderInterface::getConfiguration()`: replaced by `LinkProviderInterface::getConfigurationBuilder(): LinkConfigurationBuilder`
- `LinkProviderInterface::preload()`: returns `iterable`
- `LinkProviderPoolInterface::getProvider()`: returns `LinkProviderInterface`
- `LinkProviderPoolInterface::hasProvider()`: returns `bool`
- `LinkProviderPoolInterface::getConfiguration()`: returns `array`
- `MediaRepositoryInterface::count()`: returns `int`
- `DispatchSpecificDomainEventSubscriber::getSubscribedEvents()`: returns `array`
- `SetDomainEventUserSubscriber::getSubscribedEvents()`: returns `array`
- `StoreActivitySubscriber::getSubscribedEvents()`: returns `array`
- `DownloadLanguageCommand::configure()`: returns `void`
- `SuluAdminExtension::prepend()`: returns `void`
- `SchemaHandler::getSubscribingMethods()`: returns `array`
- `DropdownToolbarActionSubscriber::getSubscribedEvents()`: returns `array`
- `SaveWithFormDialogToolbarActionSubscriber::getSubscribedEvents()`: returns `array`
- `TogglerToolbarActionSubscriber::getSubscribedEvents()`: returns `array`
- `SuluAudienceTargetingExtension.php::load()`: returns `void`
- `SuluAudienceTargetingExtension::prepend()`: returns `void`
- `AudienceTargetingCacheListener::preHandle()`: returns `void`
- `AudienceTargetingCacheListener::postHandle()`: returns `void`
- `AudienceTargetingCacheListener::getSubscribedEvents()`: returns `array`
- `DeviceDetectorSubscriber::getSubscribedEvents()`: returns `array`
- `TargetGroupRuleSerializeSubscriber::getSubscribedEvents()`: returns `array`
- `TargetGroupSerializeSubscriber::getSubscribedEvents()`: returns `array`
- `RecoverCommand::configure()`: returns `void`
- `SuluCategoryExtension::load()`: returns `void`
- `SuluCategoryExtension::prepend()`: returns `void`
- `AccountRecoverCommand::configure()`: returns `void`
- `SuluContactExtension::load()`: returns `void`
- `SuluContactExtension::prepend()`: returns `void`
- `CsvHandlerCompilerPass::process()`: returns `void`
- `ListBuilderMetadataProviderCompilerPass::process()`: returns `void`
- `RemoveForeignContextServicesPass::process()`: returns `void`
- `SuluCoreExtension::load()`: returns `void`
- `SuluCoreExtension::prepend()`: returns `void`
- `SuluHashExtension::load()`: returns `void`
- `SuluHttpCacheExtension::load()`: returns `void`
- `SuluHttpCacheExtension::prepend()`: returns `void`
- `SuluLocationExtension::load()`: returns `void`
- `SuluLocationExtension::prepend()`: returns `void`
- `SuluMarkupExtension::load()`: returns `void`
- `SuluMarkupExtension::prepend()`: returns `void`
- `MailerListener::getSubscribedEvents()`: returns `array`
- `ClearCacheCommand::configure()`: returns `void`
- `FormatCacheCleanupCommand::configure()`: returns `void`
- `AbstractImageFormatCompilerPass::process()`: returns `void`
- `ImageTransformationCompilerPass::process()`: returns `void`
- `SuluMediaExtension::prepend()`: returns `void`
- `SuluMediaExtension::load()`: returns `void`
- `MediaTwigExtension::getFunctions()`: returns `array`
- `ActivityResolveTargetEntityResolverPass::process()`: returns `void`
- `ResolveTargetEntitiesPass::process()`: returns `void`
- `CreateRoleCommand::configure()`: returns `void`
- `CreateUserCommand::configure()`: returns `void`
- `AccessControlProviderPass::process()`: returns `void`
- `AliasForSecurityEncoderCompilerPass::process()`: returns `void`
- `SuluSecurityExtension::load()`: returns `void`
- `SuluSecurityExtension::prepend()`: returns `void`
- `LastLoginListener::onSecurityInteractiveLogin()`: returns `void`
- `LastLoginListener::updateLastLogin()`: returns `void`
- `SuluTagExtension::prepend()`: returns `void`
- `SuluTagExtension::load()`: returns `void`
- `SuluTestExtension::load()`: returns `void`
- `DumpSitemapCommand::configure()`: returns `void`
- `DeregisterDefaultRouteListenerCompilerPass::process()`: returns `void`
- `SuluWebsiteExtension::prepend()`: returns `void`
- `SuluWebsiteExtension::load()`: returns `void`
- `RedirectExceptionSubscriber::getSubscribedEvents()`: returns `array`
- `RouterListener::onKernelRequest()`: returns `void`
- `RouterListener::onKernelFinishRequest()`: returns `void`
- `RouterListener::getSubscribedEvents()`: returns `array`
- `SegmentCacheListener::getSubscribedEvents()`: returns `array`
- `SegmentCacheListener::preHandle()`: returns `void`
- `SegmentCacheListener::postHandle()`: returns `void`
- `TranslatorListener::getSubscribedEvents()`: returns `array`
- `AnalyticsSerializeEventSubscriber::getSubscribedEvents()`: returns `array`
- `DomainEventEventSubscriber::getSubscribedEvents()`: returns `array`
- `GeneratorEventSubscriber::getSubscribedEvents()`: returns `array`
- `HashSerializeEventSubscriber::getSubscribedEvents()`: returns `array`
- `DateHandler::getSubscribedEvents()`: returns `array`
- `SecuredEntitySubscriber::getSubscribedEvents()`: returns `array`
- `RepresentationSubscriber::getSubscribedEvents()`: returns `array`

The corresponding traits `TimestampableTrait` and `UserBlameTrait` have been updated with these return type hints.

**Impact:** If you have custom entities that directly implement these interfaces without using the traits, you need to add the proper return type hints to your implementation methods. This is a breaking change that will cause PHP fatal errors if not addressed.

### Moved classes for 3.0:

- `Sulu\Bundle\CoreBundle\ExpressionLanguage\ContainerExpressionLanguageProvider`: `Sulu\Bundle\AdminBundle\ExpressionLanguage\ContainerExpressionLanguageProvider`
- `Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser`: `Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\PropertiesXmlParser`
- `Sulu\Component\Content\Metadata\Parser\SchemaXmlParser`: `Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Parser\SchemaXmlParser`
- `Sulu\Bundle\PageBundle\Controller\TeaserController`: `Sulu\Bundle\AdminBundle\Controller\TeaserController`
- `Sulu\Bundle\PageBundle\Teaser\Teaser`: `Sulu\Bundle\AdminBundle\Teaser\ProviderTeaser`
- `Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPool`: `Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderPool`
- `Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderPoolInterface`: `Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderPoolInterface`
- `Sulu\Bundle\PageBundle\Teaser\TeaserManager`: `Sulu\Bundle\AdminBundle\Teaser\TeaserManager`
- `Sulu\Bundle\PageBundle\Teaser\TeaserManagerInterface`: `Sulu\Bundle\AdminBundle\Teaser\TeaserManagerInterface`
- `Sulu\Bundle\PageBundle\Teaser\Configuration\TeaserConfiguration`: `Sulu\Bundle\AdminBundle\Teaser\Configuration\TeaserConfiguration`
- `Sulu\Bundle\PageBundle\Teaser\Provider\TeaserProviderInterface`: `Sulu\Bundle\AdminBundle\Teaser\Provider\TeaserProviderInterface`
- `Sulu\Component\PHPCR\PathCleanupInterface`: `Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanupInterface`
- `Sulu\Component\PHPCR\PathCleanup`: `Sulu\Route\Application\ResourceLocator\PathCleanup\PathCleanup`
- `Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStore`: `Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStore`
- `Sulu\Bundle\WebsiteBundle\ReferenceStore\ReferenceStoreInterface`: `Sulu\Bundle\HttpCacheBundle\ReferenceStore\ReferenceStoreInterface`

### Moved services for 3.0:

- `sulu_core.expression_language`: `sulu_admin.expression_language`
- `sulu_core.symfony_expression_language_provider`: `sulu_admin.symfony_expression_language_provider`
- `sulu_page.structure.properties_xml_parser`: `sulu_admin.properties_xml_parser`
- `sulu_page.structure.schema_xml_parser`: `sulu_admin.schema_xml_parser`
- `sulu_page.teaser.provider_pool`: `sulu_admin.teaser_provider_pool`
- `sulu_page.teaser.manager`: `sulu_admin.teaser_manager`
- `sulu_page.teaser_controller`: `sulu_admin.teaser_controller`
- `sulu.content.path_cleaner`: `sulu_route.path_cleanup`
- `sulu_security.encoder_factory`: `security.password_hasher_factory`.

### Moved files for 3.0

 - `src/Sulu/Component/Content/Metadata/Loader/schema/form-1.0.xsd` -> `src/Sulu/Bundle/AdminBundle/Resources/config/schema/form-1.0.xsd`
 - `src/Sulu/Component/Content/Metadata/Loader/schema/properties-1.0.xsd` -> `src/Sulu/Bundle/AdminBundle/Resources/config/schema/properties-1.0.xsd`
 - `src/Sulu/Component/Content/Metadata/Loader/schema/schema-1.0.xsd` -> `src/Sulu/Bundle/AdminBundle/Resources/config/schema/schema-1.0.xsd`
 - `src/Sulu/Component/Content/Metadata/Loader/schema/template-1.0.xsd` -> `src/Sulu/Bundle/AdminBundle/Resources/config/schema/template-1.0.xsd`
 - `src/Sulu/Component/Content/Metadata/Loader/schema/xml.xsd` -> `src/Sulu/Bundle/AdminBundle/Resources/config/schema/xml.xsd`

### PathCleanup replacers xml no longer exists

The `replacers.xml` read for the PathCleanup service do no longer exists.
If you want to add custom replacers decorate the `PathCleanup` service via the `PathCleanupInterface`.

### Admin API changes for 3.0

- The `?flat=true` is default for all list endpoints in `ContactBundle` none flatted result is no longer supported.
- The `/admin/api/contacts` no longer provides the `bySystem=true` parameter.

### RouteBundle replaced

The `Sulu\Bundle\RouteBundle` was completely rewritten from scratch and is now replaced by the new `Sulu\Route` classes and services.
Services such as the `RouteManager` (`sulu_route.manager.route_manager`) have been replaced by the new `RouteRepository`,
and directly modifying the new `Route` entity is sufficient.

### Changed methods for 3.0

- `Sulu\Bundle\ContactBundle\Controller\AbstractMediaController::__construct`
- `Sulu\Page\Infrastructure\Symfony\Twig\Extension\NavigationTwigExtension::flatRootNavigationFunction` (added `?array $properties = null`)
- `Sulu\Page\Infrastructure\Symfony\Twig\Extension\NavigationTwigExtension::treeRootNavigationFunction` (added `?array $properties = null`)
- `Sulu\Page\Infrastructure\Symfony\Twig\Extension\NavigationTwigExtension::flatNavigationFunction` (added `?array $properties = null`)
- `Sulu\Page\Infrastructure\Symfony\Twig\Extension\NavigationTwigExtension::treeNavigationFunction` (added `?array $properties = null`)
- `Sulu\Page\Infrastructure\Symfony\Twig\Extension\NavigationTwigExtension::breadcrumbFunction` (added `?array $properties = null`)
- `Sulu\Page\Domain\Repository\NavigationRepositoryInterface::getNavigationFlat` (added `array $properties = []`)
- `Sulu\Page\Domain\Repository\NavigationRepositoryInterface::getNavigationTree` (added `array $properties = []`)
- `Sulu\Page\Domain\Repository\NavigationRepositoryInterface::getNavigationFlatByUuid` (added `array $properties = []`)
- `Sulu\Page\Domain\Repository\NavigationRepositoryInterface::getNavigationTreeByUuid` (added `array $properties = []`)
- `Sulu\Page\Domain\Repository\NavigationRepositoryInterface::getBreadcrumb` (added `array $properties = []`)

### Piwik replaced with Matomo script

The script provided by Sulu for the piwik implementation has been updated to use mataomo path so the script is now pointing to matomo.php instead of the piwik.php file.

### Changed Media Format HTTP Response Headers

Removed `Pragma` & `Expires` HTTP headers, as the `Cache-Control` header is enough.

If you still want to readd them use `sulu_media.format_manager.response_headers` configuration.

### Removing deprecated guzzle integration

As part of the update of flysystem the support for the guzzle client package `guzzlehttp/guzzle` has been removed. If you need it you need to manually require it.

The `GoogleGeolocator` and the `NominatimGeolocator` no longer support the Guzzle client and require a `Symfony\HttpClient` client instead.


### Removing custom KernelBrowser

The custom browser class Sulu\Bundle\TestBundle\Kernel\SuluKernelBrowser has been removed in favour of the default Symfony one.
This is an example on how to do that.

```diff
 $this->client->jsonRequest(
     'GET',
-    '/some-endpoint',
-    [ 'some_param' => '12345' ],
+    '/some-endpoint?some_param=12345',
 );
```

If your endpoint requires several parameters, you can compose the query string using the native function `http_build_query`:

```php
$param = [
    'some_param' => '12345'
];
$this->client->jsonRequest('GET', '/some-endpoint?'. http_build_query($param));
```

Please also note that if you have a 'GET' request that has a request body that these parameters will be ignored.

### Adjusted the ReferenceContext for Reference Entities

The `referenceContext` of the `Reference` entity has been adjusted to be consistent with the DimensionContentInterface::STAGE_DRAFT
and DimensionContentInterface::STAGE_LIVE constants. Therefore `referenceContext` now uses `draft` and `live` instead of
`admin` and `website`.

To update the references call the following command:

```bash
bin/console sulu:reference:refresh
```

### Upgrading Data from Sulu 2.6 to Sulu 3.0

To migrate the data from PHPCR to the new content storage a migration bundle was developed.

```shell
composer require sulu/phpcr-migration-bundle
```

Add the new migration bundle to your `config/bundles.php`:

```diff
// config/bundles.php

return [
    // ...
+   Sulu\Bundle\PhpcrMigrationBundle\SuluPhpcrMigrationBundle::class => ['all' => true],
```

Configure the SuluPhpcrMigrationBundle in `config/packages/sulu_phpcr_migration.yaml`:

> If you are currently using Jackrabbit, use the "jackrabbit://" based DSN string.
> After the upgrade, Apache Jackrabbit is no longer used by Sulu’s new content storage and can be removed from
> your projects in most situations.

```yaml
# config/packages/sulu_phpcr_migration.yaml

sulu_phpcr_migration:
    # dbal://<dbalConnection>?workspace=<workspaceName>
    # jackrabbit://<user>:<password>@<host>:<port>/server?workspace=<workspaceName>
    #    DSN: "dbal://default?workspace=%env(PHPCR_WORKSPACE)%"
    #    DSN: "jackrabbit://admin:admin@127.0.0.1:8080/server?workspace=%env(PHPCR_WORKSPACE)%"
    DSN: "dbal://default?workspace=%env(PHPCR_WORKSPACE)%"
    target:
        dbal:
            connection: default
```

Please ensure that you have executed all the database migrations for version 3.0. Once finished, you
can run the following command to update the content structure:

```shell
php bin/adminconsole sulu:phpcr-migration:migrate
```

In case of some errors on customized code, you can try to fix it and rerun the command. The migration command can be
rerun, the existing already migrated content will be overwritten and not duplicated.
If everything is done and the migration is successful, you can log in to the Sulu admin interface, set the permissions
for the articles and snippets and check if everything is working as expected.

### Migrating from Article Types to Template Groups

In Sulu 2.x with the SuluArticleBundle, article types were used to categorize different article templates with
tab-based filtering in the admin interface and separate permissions per article type. This functionality was configured
via the `sulu_article.types` configuration.

In Sulu 3.0, this concept has been replaced with **template groups**, which provide the same functionality but are now
defined directly in the template XML files.

#### What Changed

**Sulu 2.x (SuluArticleBundle):**
- Article types defined in `config/packages/sulu_article.yaml`
- Templates implicitly belonged to a type based on configuration
- Only worked for articles

**Sulu 3.0 (Template Groups):**
- Template groups defined directly in template XML files using the `<group>` element
- No separate YAML configuration needed
- Provides the same tab-based filtering and permission separation

#### Migration Steps

**1. Remove Article Type Configuration**

Remove the `types` configuration from your `config/packages/sulu_article.yaml` file:

```diff
# config/packages/sulu_article.yaml
sulu_article:
-    types:
-        blog:
-            translation_key: "app.article_types.blog"
-        news:
-            translation_key: "app.article_types.news"
```

**2. Add Group to Template XML Files**

For each article template, add a `<group>` element to specify which group it belongs to:

```diff
<!-- config/templates/articles/blog.xml -->
<template xmlns="http://schemas.sulu.io/template/template"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/template.xsd">

    <key>blog</key>
+   <group>blog</group>

    <view>views/articles/blog</view>
    <controller>Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction</controller>

    <meta>
        <title lang="en">Blog Article</title>
        <title lang="de">Blog-Artikel</title>
    </meta>

    <properties>
        <!-- ... -->
    </properties>
</template>
```

**3. Update Translations (Optional)**

The group identifier (e.g., `blog`) will be automatically converted to a human-readable title using ucfirst
(e.g., `Blog`). If you need custom translations, you can add them to your admin translations file
(`translations/admin.en.yaml`):

```yaml
sulu_admin.template_group.blog: Blog Articles
sulu_admin.template_group.news: News Articles
```

The translation key pattern is `sulu_admin.template_group.<group_name>`. If no translation is found, Sulu will
fall back to using `ucfirst(<group_name>)`.

**4. Update Permissions**

After the migration, you'll need to update user role permissions in the Sulu admin interface. Template groups create
separate permission contexts, so you'll need to grant permissions for each group to the appropriate user roles.

1. Log in to the Sulu admin interface
2. Navigate to Settings → User Roles
3. Edit each role and grant permissions for the new template groups under the Articles section


### Changing deprecated signatures

To maintain backward compatibility in previous versions, new arguments were previously added as doc comments only. These
arguments are now explicitly defined in the function signatures (with default values), which may result in breaking
changes for subclasses or overrides relying on the older method definitions.

Affected classes / interfaces:
- `Sulu\Bundle\CategoryBundle\Category\CategoryManager::delete` (added `bool $forceRemoveChildren`)
- `Sulu\Bundle\CategoryBundle\Category\CategoryManagerInterface::delete` (added `bool $forceRemoveChildren`)
- `Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManager::delete` (added `bool $forceRemoveChildren`)
- `Sulu\Bundle\MediaBundle\Collection\Manager\CollectionManagerInterface::delete` (added `bool $forceRemoveChildren`)
- `Sulu\Bundle\MediaBundle\Entity\MediaRepository::findMediaByIdForRendering` (added `?int $version`)
- `Sulu\Bundle\MediaBundle\Entity\MediaRepositoryInterface::findMediaByIdForRendering` (added `?int $version`)
- `Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManager::returnImage` (added `?int $version`)
- `Sulu\Bundle\MediaBundle\Media\FormatManager\FormatManagerInterface::returnImage` (added `?int $version`)
- `Sulu\Bundle\MediaBundle\Media\ImageConverter\MediaImageExtractor::extract` (added `string $resourceMimeType`)
- `Sulu\Bundle\MediaBundle\Media\ImageConverter\MediaImageExtractorInterface::extract` (added `string $resourceMimeType`)
- `Sulu\Bundle\WebsiteBundle\Cache\CacheClearer::clear` (added `?array $tags`)
- `Sulu\Bundle\WebsiteBundle\Cache\CacheClearerInterface::clear` (added `?array $tags`)
- `Sulu\Bundle\SecurityBundle\EventListener\SystemListener::__construct` (removed `?RequestAnalyzerInterface $requestAnalyzer`)

### Extending Block Settings

The `config/forms/page_block_settings.xml` file should be renamed to `config/forms/content_block_settings.xml`.
Additionally, ensure that the `<key>` value is changed as well:

```diff
<?xml version="1.0" ?>
<form xmlns="http://schemas.sulu.io/template/template"
      xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
      xsi:schemaLocation="http://schemas.sulu.io/template/template http://schemas.sulu.io/template/form-1.0.xsd">
-   <key>page_block_settings</key>
+   <key>content_block_settings</key>
    <properties>
       <!-- ... -->
    </properties>
</form>
