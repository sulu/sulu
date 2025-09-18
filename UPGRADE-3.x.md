## 3.0.0

The upgrade from Sulu 2.6 to Sulu 3.0 is a major upgrade and will require some migration steps.

### Pre Update step to 3.0 PHPCR Migration

Before upgrading to 3.0 make sure you have installed the latest Sulu 2.x versions of Sulu and the Sulu bundles.
Make sure that you have all PHPCR migration run via:

```shell
php bin/adminconsole phpcr:migrations:migrate
```

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
+sulu_next_route_api:
+    resource: "@SuluNextRouteBundle/config/routing_admin_api.yaml"
+    prefix: /admin/api
+
+sulu_next_page_api:
+    resource: "@SuluNextPageBundle/config/routing_admin_api.yaml"
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

Remove the bundles from your `config/bundles.php` file:

```diff
// config/bundles.php

return [
-    Massive\Bundle\SearchBundle\MassiveSearchBundle::class => ['all' => true],
-    Sulu\Bundle\SearchBundle\SuluSearchBundle::class => ['all' => true],
```

Remove eventually registered routes `config/routes/sulu_website.yaml` and `config/routes/sulu_admin.yaml`
or usages in the twig templates e.g.: `{{ path('sulu_search.website_search') }}`.

### Template Controller changes

The `DefaultController` and it's parent class `WebsiteController` has been removed in favor
of the new `ContentController`. Update your controllers to extend the new `ContentController`
and update your templates definition to use the new controller:

```diff
-<controller>Sulu\Bundle\WebsiteBundle\Controller\DefaultController::indexAction</controller>
+<controller>Sulu\Content\UserInterface\Controller\Website\ContentController::indexAction</controller>
```

### Add new Content storage tables

The new content storage architecture requires a new database schema. You can execute the following sql statements
to update your database schema.

The following SQL statements are examples based on MySQL. You might generate them via doctrine for your preferred database.

#### RouteBundle

```sql
CREATE TABLE ro_next_routes (id INT AUTO_INCREMENT NOT NULL, parent_id INT DEFAULT NULL, site VARCHAR(32) DEFAULT NULL, locale VARCHAR(15) NOT NULL, slug VARCHAR(144) NOT NULL, resource_key VARCHAR(32) NOT NULL, resource_id VARCHAR(70) NOT NULL, INDEX IDX_BD51B2DA727ACA70 (parent_id), INDEX ro_routes_resource_idx (locale, resource_key, resource_id), UNIQUE INDEX ro_routes_unique (site, locale, slug), PRIMARY KEY(id));
ALTER TABLE ro_next_routes ADD CONSTRAINT FK_BD51B2DA727ACA70 FOREIGN KEY (parent_id) REFERENCES ro_next_routes (id) ON DELETE SET NULL;
```

#### PageBundle

```sql
CREATE TABLE pa_page_dimension_contents (id INT AUTO_INCREMENT NOT NULL, route_id INT DEFAULT NULL, author_id INT DEFAULT NULL, title VARCHAR(191) DEFAULT NULL, stage VARCHAR(15) NOT NULL, locale VARCHAR(15) DEFAULT NULL, ghostLocale VARCHAR(15) DEFAULT NULL, availableLocales JSON DEFAULT NULL, shadowLocale VARCHAR(15) DEFAULT NULL, shadowLocales JSON DEFAULT NULL, templateKey VARCHAR(31) DEFAULT NULL, templateData JSON NOT NULL, seoTitle VARCHAR(255) DEFAULT NULL, seoDescription LONGTEXT DEFAULT NULL, seoKeywords LONGTEXT DEFAULT NULL, seoCanonicalUrl LONGTEXT DEFAULT NULL, seoNoIndex TINYINT(1) NOT NULL, seoNoFollow TINYINT(1) NOT NULL, seoHideInSitemap TINYINT(1) NOT NULL, excerptTitle VARCHAR(255) DEFAULT NULL, excerptMore VARCHAR(63) DEFAULT NULL, excerptDescription LONGTEXT DEFAULT NULL, excerptImageId INT DEFAULT NULL, excerptIconId INT DEFAULT NULL, authored DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', lastModified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', workflowPlace VARCHAR(31) DEFAULT NULL, workflowPublished DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', pageUuid VARCHAR(255) NOT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_209A42C034ECB4E6 (route_id), INDEX IDX_209A42C0F099EEF3 (pageUuid), INDEX IDX_209A42C0F675F31B (author_id), INDEX IDX_209A42C0DBF11E1D (idUsersCreator), INDEX IDX_209A42C030D07CD5 (idUsersChanger), INDEX idx_pa_page_dimension_contents_dimension (stage, locale), INDEX idx_pa_page_dimension_contents_locale (locale), INDEX idx_pa_page_dimension_contents_stage (stage), INDEX idx_pa_page_dimension_contents_template_key (templateKey), INDEX idx_pa_page_dimension_contents_workflow_place (workflowPlace), INDEX idx_pa_page_dimension_contents_workflow_published (workflowPublished), INDEX IDX_209A42C02F5A5F5D (excerptImageId), INDEX IDX_209A42C016996F78 (excerptIconId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE pa_page_dimension_content_excerpt_tags (page_dimension_content_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_66C81FDB67C2CFD5 (page_dimension_content_id), INDEX IDX_66C81FDBBAD26311 (tag_id), PRIMARY KEY(page_dimension_content_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE pa_page_dimension_content_excerpt_categories (page_dimension_content_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_BE45C16867C2CFD5 (page_dimension_content_id), INDEX IDX_BE45C16812469DE2 (category_id), PRIMARY KEY(page_dimension_content_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE pa_page_dimension_content_navigation_contexts (id INT AUTO_INCREMENT NOT NULL, page_dimension_content_id INT NOT NULL, name VARCHAR(64) NOT NULL, INDEX IDX_4C5FD8F767C2CFD5 (page_dimension_content_id), INDEX idx_page_navigation_context (name), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE pa_pages (uuid VARCHAR(255) NOT NULL, parent_id VARCHAR(255) DEFAULT NULL, webspaceKey VARCHAR(64) NOT NULL, lft INT NOT NULL, rgt INT NOT NULL, depth INT NOT NULL, created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_FF3DA1E2727ACA70 (parent_id), INDEX IDX_FF3DA1E2DBF11E1D (idUsersCreator), INDEX IDX_FF3DA1E230D07CD5 (idUsersChanger), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C034ECB4E6 FOREIGN KEY (route_id) REFERENCES ro_next_routes (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C0F099EEF3 FOREIGN KEY (pageUuid) REFERENCES pa_pages (uuid) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C0F675F31B FOREIGN KEY (author_id) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C0DBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C030D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C02F5A5F5D FOREIGN KEY (excerptImageId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE pa_page_dimension_contents ADD CONSTRAINT FK_209A42C016996F78 FOREIGN KEY (excerptIconId) REFERENCES me_media (id) ON DELETE SET NULL;
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
CREATE TABLE sn_snippet_dimension_contents (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(191) DEFAULT NULL, stage VARCHAR(15) NOT NULL, locale VARCHAR(15) DEFAULT NULL, ghostLocale VARCHAR(15) DEFAULT NULL, availableLocales JSON DEFAULT NULL, templateKey VARCHAR(31) DEFAULT NULL, templateData JSON NOT NULL, excerptTitle VARCHAR(255) DEFAULT NULL, excerptMore VARCHAR(63) DEFAULT NULL, excerptDescription LONGTEXT DEFAULT NULL, excerptImageId INT DEFAULT NULL, excerptIconId INT DEFAULT NULL, workflowPlace VARCHAR(31) DEFAULT NULL, workflowPublished DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', snippetUuid VARCHAR(255) NOT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_46D6814477F33FFB (snippetUuid), INDEX IDX_46D68144DBF11E1D (idUsersCreator), INDEX IDX_46D6814430D07CD5 (idUsersChanger), INDEX idx_sn_snippet_dimension_contents_dimension (stage, locale), INDEX idx_sn_snippet_dimension_contents_locale (locale), INDEX idx_sn_snippet_dimension_contents_stage (stage), INDEX idx_sn_snippet_dimension_contents_template_key (templateKey), INDEX idx_sn_snippet_dimension_contents_workflow_place (workflowPlace), INDEX idx_sn_snippet_dimension_contents_workflow_published (workflowPublished), INDEX IDX_46D681442F5A5F5D (excerptImageId), INDEX IDX_46D6814416996F78 (excerptIconId), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE sn_snippet_dimension_content_excerpt_tags (snippet_dimension_content_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_96BD1E357891499D (snippet_dimension_content_id), INDEX IDX_96BD1E35BAD26311 (tag_id), PRIMARY KEY(snippet_dimension_content_id, tag_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE sn_snippet_dimension_content_excerpt_categories (snippet_dimension_content_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_464EB1547891499D (snippet_dimension_content_id), INDEX IDX_464EB15412469DE2 (category_id), PRIMARY KEY(snippet_dimension_content_id, category_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
CREATE TABLE sn_snippets (uuid VARCHAR(255) NOT NULL, created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_E68115CFDBF11E1D (idUsersCreator), INDEX IDX_E68115CF30D07CD5 (idUsersChanger), PRIMARY KEY(uuid)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D6814477F33FFB FOREIGN KEY (snippetUuid) REFERENCES sn_snippets (uuid) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D68144DBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D6814430D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D681442F5A5F5D FOREIGN KEY (excerptImageId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE sn_snippet_dimension_contents ADD CONSTRAINT FK_46D6814416996F78 FOREIGN KEY (excerptIconId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE sn_snippet_dimension_content_excerpt_tags ADD CONSTRAINT FK_96BD1E357891499D FOREIGN KEY (snippet_dimension_content_id) REFERENCES sn_snippet_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_content_excerpt_tags ADD CONSTRAINT FK_96BD1E35BAD26311 FOREIGN KEY (tag_id) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_content_excerpt_categories ADD CONSTRAINT FK_464EB1547891499D FOREIGN KEY (snippet_dimension_content_id) REFERENCES sn_snippet_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE sn_snippet_dimension_content_excerpt_categories ADD CONSTRAINT FK_464EB15412469DE2 FOREIGN KEY (category_id) REFERENCES ca_categories (id) ON DELETE CASCADE;
ALTER TABLE sn_snippets ADD CONSTRAINT FK_E68115CFDBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE sn_snippets ADD CONSTRAINT FK_E68115CF30D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
```

#### ArticleBundle

```sql
CREATE TABLE ar_article_dimension_contents (id INT AUTO_INCREMENT NOT NULL, route_id INT DEFAULT NULL, author_id INT DEFAULT NULL, title VARCHAR(191) DEFAULT NULL, stage VARCHAR(15) NOT NULL, locale VARCHAR(15) DEFAULT NULL, ghostLocale VARCHAR(15) DEFAULT NULL, availableLocales JSON DEFAULT NULL, shadowLocale VARCHAR(15) DEFAULT NULL, shadowLocales JSON DEFAULT NULL, templateKey VARCHAR(31) DEFAULT NULL, templateData JSON NOT NULL, seoTitle VARCHAR(255) DEFAULT NULL, seoDescription LONGTEXT DEFAULT NULL, seoKeywords LONGTEXT DEFAULT NULL, seoCanonicalUrl LONGTEXT DEFAULT NULL, seoNoIndex TINYINT(1) NOT NULL, seoNoFollow TINYINT(1) NOT NULL, seoHideInSitemap TINYINT(1) NOT NULL, excerptTitle VARCHAR(255) DEFAULT NULL, excerptMore VARCHAR(63) DEFAULT NULL, excerptDescription LONGTEXT DEFAULT NULL, excerptImageId INT DEFAULT NULL, excerptIconId INT DEFAULT NULL, mainWebspace VARCHAR(255) DEFAULT NULL, authored DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', lastModified DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', workflowPlace VARCHAR(31) DEFAULT NULL, workflowPublished DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)', articleUuid VARCHAR(255) NOT NULL, created DATETIME NOT NULL, changed DATETIME NOT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_5674F7BF34ECB4E6 (route_id), INDEX IDX_5674F7BFAE39C518 (articleUuid), INDEX IDX_5674F7BFF675F31B (author_id), INDEX idx_ar_article_dimension_contents_dimension (stage, locale), INDEX idx_ar_article_dimension_contents_locale (locale), INDEX idx_ar_article_dimension_contents_stage (stage), INDEX idx_ar_article_dimension_contents_template_key (templateKey), INDEX idx_ar_article_dimension_contents_workflow_place (workflowPlace), INDEX idx_ar_article_dimension_contents_workflow_published (workflowPublished), INDEX IDX_5674F7BF2F5A5F5D (excerptImageId), INDEX IDX_5674F7BF16996F78 (excerptIconId), INDEX IDX_5674F7BFDBF11E1D (idUsersCreator), INDEX IDX_5674F7BF30D07CD5 (idUsersChanger), PRIMARY KEY(id));
CREATE TABLE ar_article_dimension_content_excerpt_tags (article_dimension_content_id INT NOT NULL, tag_id INT NOT NULL, INDEX IDX_B45854027C1747D1 (article_dimension_content_id), INDEX IDX_B4585402BAD26311 (tag_id), PRIMARY KEY(article_dimension_content_id, tag_id));
CREATE TABLE ar_article_dimension_content_excerpt_categories (article_dimension_content_id INT NOT NULL, category_id INT NOT NULL, INDEX IDX_971AE52D7C1747D1 (article_dimension_content_id), INDEX IDX_971AE52D12469DE2 (category_id), PRIMARY KEY(article_dimension_content_id, category_id));
CREATE TABLE ar_articles (uuid VARCHAR(255) NOT NULL, created DATETIME NOT NULL, changed DATETIME NOT NULL, idUsersCreator INT DEFAULT NULL, idUsersChanger INT DEFAULT NULL, INDEX IDX_7F75CD17DBF11E1D (idUsersCreator), INDEX IDX_7F75CD1730D07CD5 (idUsersChanger), PRIMARY KEY(uuid));
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF34ECB4E6 FOREIGN KEY (route_id) REFERENCES ro_next_routes (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BFAE39C518 FOREIGN KEY (articleUuid) REFERENCES ar_articles (uuid) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BFF675F31B FOREIGN KEY (author_id) REFERENCES co_contacts (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF2F5A5F5D FOREIGN KEY (excerptImageId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF16996F78 FOREIGN KEY (excerptIconId) REFERENCES me_media (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BFDBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_contents ADD CONSTRAINT FK_5674F7BF30D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE ar_article_dimension_content_excerpt_tags ADD CONSTRAINT FK_B45854027C1747D1 FOREIGN KEY (article_dimension_content_id) REFERENCES ar_article_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_excerpt_tags ADD CONSTRAINT FK_B4585402BAD26311 FOREIGN KEY (tag_id) REFERENCES ta_tags (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_excerpt_categories ADD CONSTRAINT FK_971AE52D7C1747D1 FOREIGN KEY (article_dimension_content_id) REFERENCES ar_article_dimension_contents (id) ON DELETE CASCADE;
ALTER TABLE ar_article_dimension_content_excerpt_categories ADD CONSTRAINT FK_971AE52D12469DE2 FOREIGN KEY (category_id) REFERENCES ca_categories (id) ON DELETE CASCADE;
ALTER TABLE ar_articles ADD CONSTRAINT FK_7F75CD17DBF11E1D FOREIGN KEY (idUsersCreator) REFERENCES se_users (id) ON DELETE SET NULL;
ALTER TABLE ar_articles ADD CONSTRAINT FK_7F75CD1730D07CD5 FOREIGN KEY (idUsersChanger) REFERENCES se_users (id) ON DELETE SET NULL;
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

### Migrate on TimestampableInterface depend entities to DateTimeImmutable

The following columns now contain immutable datetime objects:

All `created` and `changed` columns generated by the `TimestampableInterface`.

Run this SQL to migrate the database:

```sql
ALTER TABLE re_references CHANGE created created DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)', CHANGE changed changed DATETIME NOT NULL COMMENT '(DC2Type:datetime_immutable)';
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
        snippets:
            default_type: 'my_snippet_key'
            directories:
                snippet_project_a:
                    path: '%kernel.project_dir%/config/templates/snippets/projectA'
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

The new content structure used in Sulu 3.0 requires that all the `resource_locators` or `route` properties must be
renamed to `url` in your templates and use the `route` always.

```diff
-        <property name="routePath" type="route">
+        <property name="url" type="route">
            <meta>
                <title lang="en">Resourcelocator</title>
                <title lang="de">Adresse</title>
            </meta>

            <tag name="sulu_article.article_route"/>
        </property>
```

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

### Removed deprecations for 3.0

Removed classes / services:

- `Sulu\Bundle\MarkupBundle\Listener\SwiftMailerListener`
- `Sulu\Bundle\DocumentManagerBundle\Slugifier\Urlizer`
- `Sulu\Bundle\CategoryBundle\DependencyInjection\DeprecationCompilerPass`
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
- `Sulu\Bundle\MediaBundle\Media\Storage\S3Storage`
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
- `src\Sulu\Component\Content\Metadata\XmlParserTrait`  (internal)
- `Sulu\Component\Content\Metadata\Parser\PropertiesXmlParser` (moved and internal)
- `Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\TextPropertyMetadataMapper` (moved and internal)
- `Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SelectionPropertyMetadataMapper` (moved and internal)
- `Sulu\Bundle\AdminBundle\Metadata\SchemaMetadata\SingleSelectionPropertyMetadataMapper` (moved and internal)
- `ContentTypes` implementing `PropertyMetadataMapperInterface` (moved and internal)
- `Sulu\Bundle\AdminBundle\Metadata\FormMetadata\Loader\AbstractLoader` (moved and internal)
- `Sulu\Commponent\Content\Document\Subscriber\Compat\ContentMapperSubscriber`
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
- `Sulu\Bundle\SecurityBundle\EventListener\AuhenticationFailureListener` (moved and internal)
- `Sulu\Bundle\MediaBundle\DependencyInjection\FormatCacheClearerCompilerPass` (use tagged_iterator)

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

Removed unused arguments:

- `Sulu\Component\Webspace\Analyzer\Attributes\WebsiteRequestProcessor::__construct` `$contentMapper` (2nd argument) removed
- `Sulu\Bundle\SecurityBundle\UserManager\UserManager::__construct` `$groupRepository` (4th argument) removed
- `Sulu\Bundle\SecurityBundle\Admin\SecurityAdmin::__construct` `$urlGenerator` (3rd argument) removed
- `Sulu\Bundle\ContactBundle\Controller\ContactController::__construct` `$contactRepository` (7rd argument) removed
- `Sulu\Bundle\ContactBundle\Controller\ContactController::__construct` `$userRepository` (9rd argument) removed
- `Sulu\Bundle\ContactBundle\Controller\ContactController::__construct` `$suluSecuritySystem` (12rd argument) removed

Removed kernel parameters:

- All `sulu_*.class` parameters for services where removed (use compilerpasses to replace class of a service definition)
- Parameter `%permissions%` was replaced in favor of `%sulu_security.permissions%`

Removed JavaScript files and methods:

 - `sulu-security-bundle/stores/securityContextStore/securityContextStore.js` `loadSecurityContextGroups`
 - `sulu-security-bundle/stores/securityContextStore/securityContextStore.js` `loadAvailableActions`
 - `sulu-admin-bundle/containers/List/loadingStrategies/FullLoadingStrategy.js`
 - `sulu-admin-bundle/stores/localizationStore/localizationStore.js` `loadLocalizations`
 - `sulu-page-bundle/stores/webspaceStore/webspaceStore.js` `loadWebspaces`
 - `sulu-page-bundle/stores/webspaceStore/webspaceStore.js` `loadWebspace`

Removed container parameters:

- `sulu_security.security_types.fixture`
- `sulu_media.media.max_file_size` (replaced by `sulu_media.media.max_filesize`)

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

### Piwik replaced with Matomo script

The script provided by Sulu for the piwik implementation has been updated to use mataomo path so the script is now pointing to matomo.php instead of the piwik.php file.

### Changed Media Format HTTP Response Headers

Removed `Pragma` & `Expires` HTTP headers, as the `Cache-Control` header is enough.

If you still want to readd them use `sulu_media.format_manager.response_headers` configuration.

### Removing deprecated guzzle integration

As part of the update of flysystem the support for the guzzle client package `guzzlehttp/guzzle` has been removed. If you need it you need to manually require it.

The `GoogleGeolocator` and the `NominatimGeolocator` no longer support the Guzzle client and require a `Symfony\HttpClient` client instead.

### Adjusted the ReferenceContext for Reference Entities

The `referenceContext` of the `Reference` entity has been adjusted to be consistent with the DimensionContentInterface::STAGE_DRAFT
and DimensionContentInterface::STAGE_LIVE constants. Therefore `referenceContext` now uses `draft` and `live` instead of
`admin` and `website`.

To update the references call the following command:

```bash
bin/console sulu:reference:refresh

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
