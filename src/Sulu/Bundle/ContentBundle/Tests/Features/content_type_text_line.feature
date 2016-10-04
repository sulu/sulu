Feature: Text line content type
    In order to manage a single line of text on a page
    As a user
    I need to be able to do that

    Background:
        Given there exists a page template "text_line_page" with the following property configuration
        """
        <property name="text_line" type="text_line">
            <meta>
                <title lang="de">Text line</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter a line of text
        Given I am editing a page of type "text_line_page"
        And I fill in "text_line" with "This is a line of text"
        And wait a second
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
