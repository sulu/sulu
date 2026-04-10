-- Migration: rename workflowPublished column to published
-- in all dimension_content tables.
--
-- Run this ONCE on existing installations when upgrading to
-- the version that renames this column. New installations
-- created from scratch do not need this script.
--
-- Tested against MySQL / MariaDB. For PostgreSQL replace
-- CHANGE COLUMN ... with ALTER COLUMN ... TYPE and handle
-- indexes separately.

-- Pages
ALTER TABLE pa_page_dimension_contents
    CHANGE COLUMN workflowPublished published DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)';

DROP INDEX idx_pa_page_dimension_contents_workflow_published
    ON pa_page_dimension_contents;
CREATE INDEX idx_pa_page_dimension_contents_published
    ON pa_page_dimension_contents (published);

-- Snippets
ALTER TABLE sn_snippet_dimension_contents
    CHANGE COLUMN workflowPublished published DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)';

DROP INDEX idx_sn_snippet_dimension_contents_workflow_published
    ON sn_snippet_dimension_contents;
CREATE INDEX idx_sn_snippet_dimension_contents_published
    ON sn_snippet_dimension_contents (published);

-- Articles
ALTER TABLE ar_article_dimension_contents
    CHANGE COLUMN workflowPublished published DATETIME DEFAULT NULL COMMENT '(DC2Type:datetime_immutable)';

DROP INDEX idx_ar_article_dimension_contents_workflow_published
    ON ar_article_dimension_contents;
CREATE INDEX idx_ar_article_dimension_contents_published
    ON ar_article_dimension_contents (published);
