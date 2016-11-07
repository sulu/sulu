Feature: Content type: Tag list
    In order to apply tags to pages or snippets
    As an administrator
    I need to be able to select tags using a content type

    Background:
        Given there exists a page template "tag_page" with the following property configuration
        """
        <property name="tags" type="tag_list">
            <meta>
                <title lang="de">Tag Auswahl</title>
                <title lang="en">Tag selection</title>
            </meta>
        </property>
        """
        And the following tags exist:
            | name |
            | bar |
            | baz |
        And I am logged in as an administrator

    Scenario: Tag select
        Given I am editing a page of type "tag_page"
        And I expect the aura component "tags" to appear
        When I fill in "tags" with "bar, boo, bim,"
        And I wait a second
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
        And the tag "boo" should exist
        And the tag "bim" should exist
        And the tag "bar" should exist
