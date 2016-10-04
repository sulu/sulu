Feature: Internal links content type
    In order to provide links to internal pages
    As a user
    I need to be able to select internal links

    Background:
        Given there exists a page template "internal_links_page" with the following property configuration
        """
        <property name="links" type="internal_links">
            <meta>
                <title lang="de">Interne Links</title>
                <title lang="en">Internal links</title>
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
        And I am logged in as an administrator

    Scenario: Internal links select
        Given I am editing a page of type "internal_links_page"
        And I expect the aura component "links" to appear
        And I click the action icon
        And I expect an overlay to appear
        And I expect the "husky.column-navigation.links.loaded" event
        When I click the column navigation item "sulu.io"
        And I expect the "husky.column-navigation.links.loaded" event
        And I double click the column navigation item "Articles"
        And I click the ok button
        And I should see "Articles"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
