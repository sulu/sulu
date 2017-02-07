Feature: Snippet content type
    In order to provide snippet data in the frontend
    As an administrator
    I need to be able to associate snippets with a page

    Background:
        Given there exists a page template "snippet_page" with the following property configuration
        """
        <property name="snippet" type="snippet">
            <meta>
                <title lang="de">Schnipsel</title>
            </meta>
        </property>
        """
        And there exists a snippet template "winter" with the following property configuration
        """
        <property name="color" type="text_editor">
            <meta>
                <title lang="de">Farbe</title>
            </meta>
        </property>
        """
        And the following snippets exist:
            | template | title | data |
            | winter | Penguin | {} |
            | winter | Snowman | {} |
        And I am logged in as an administrator

    Scenario: Snippet select
        Given I am editing a page of type "snippet_page"
        When I click the action icon
        And I click on the element "#snippet-content-snippet-column-navigation th input"
        And I click the ok button
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
        And I expect to see "Penguin"
