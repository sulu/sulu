Feature: Text area content type
    In order to manage a single area of text on a page
    As a user
    I need to be able to do that

    Background:
        Given there exists a page template "text_area_page" with the following property configuration
        """
        <property name="text_area" type="text_area">
            <meta>
                <title lang="de">Text area</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter some text in a text area
        Given I am editing a page of type "text_area_page"
        And I fill in "text_area" with "This is a area of text"
        And wait a second
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
