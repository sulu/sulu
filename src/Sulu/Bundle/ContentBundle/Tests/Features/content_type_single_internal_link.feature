Feature: Single Internal links content type
    In order to provide a single link to an internal page
    As a user
    I need to be able to select internal link

    Background:
        Given there exists a page template "single_internal_link_page" with the following property configuration
        """
        <property name="link" type="single_internal_link">
            <meta>
                <title lang="de">Internal Link</title>
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

    Scenario: Single internal link
        Given I am editing a page of type "single_internal_link_page"
        And I expect the aura component "link" to appear
        When I click the link icon
        And I expect an overlay to appear
        And I expect the "husky.column-navigation.link.loaded" event
        And I click the column navigation item "sulu.io"
        And I expect the "husky.column-navigation.link.loaded" event
        And I double click the column navigation item "Articles"
        And I click the ok button
        And I expect the "sulu.content.changed" event
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
