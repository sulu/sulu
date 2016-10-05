Feature: Smart content content type
    In order to embed excerpts from a selection of pages with a page
    As an administrator
    I need to be able to configure a smart content to do that

    Background:
        Given there exists a page template "smart_content_page" with the following property configuration
        """
        <property name="smart_content" type="smart_content">
            <meta>
                <title lang="de">Smart Content Auswahl</title>
            </meta>
        </property>
        """
        And there exists a page template "article" with the following property configuration
        """
        <property name="body" type="text_area">
            <meta>
                <title lang="de">Body</title>
            </meta>
        </property>
        """
        And the following pages exist:
            | template | url | title | parent | data |
            | article | /articles | Articles | | {"body": "This is article 1"} |
            | article | /articles/article1 | Article 1 | /articles | {"body": "This is article 1"} |
            | article | /articles/article2 | Article 2 | /articles | {"body": "This is article 2"} |
            | article | /articles/article3 | Article 3 | /articles | {"body": "This is article 3"} |
        And I am logged in as an administrator

    Scenario: Smart content select
        Given I am editing a page of type "smart_content_page"
        And I expect the aura component "smart_content" to appear
        And I click the first smart content filter icon
        And I expect an overlay to appear
        And I click the "Choose source" button
        And I expect the "husky.column-navigation.smart-contentsmart_content.loaded" event
        And I click the column navigation item "sulu.io"
        And I expect the "husky.column-navigation.smart-contentsmart_content.loaded" event
        And I double click the column navigation item "Articles"
        And I wait a second for the "husky.overlay.smart-content.smart_content.slide-to" event
        And I click the "Apply" button
        And I expect the "husky.smartfcontent.smart_content.data-retrieved" event
        Then I should see "Article 1"
        And I should see "Article 2"
        And I should see "Article 3"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear

