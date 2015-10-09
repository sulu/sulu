<?php

namespace Application\Migrations;

use Doctrine\DBAL\Migrations\AbstractMigration;
use Doctrine\DBAL\Schema\Schema;

/**
 * Upgrade contact and account urls.
 */
class Version20151009110609 extends AbstractMigration
{
    /**
     * @param Schema $schema
     */
    public function up(Schema $schema)
    {
        $this->addSql("UPDATE co_urls AS url SET url.url = CONCAT('http://', url.url) WHERE url.url NOT LIKE 'http://%'");
    }

    /**
     * @param Schema $schema
     */
    public function down(Schema $schema)
    {
        $this->addSql("UPDATE co_urls AS url SET url.url = REPLACE(url.url, 'http://', '') WHERE url.url LIKE 'http://%'");
        $this->addSql("UPDATE co_urls AS url SET url.url = REPLACE(url.url, 'https://', '') WHERE url.url LIKE 'https://%'");
        $this->addSql("UPDATE co_urls AS url SET url.url = REPLACE(url.url, 'ftp://', '') WHERE url.url LIKE 'ftp://%'");
        $this->addSql("UPDATE co_urls AS url SET url.url = REPLACE(url.url, 'ftps://', '') WHERE url.url LIKE 'ftps://%'");
    }
}
