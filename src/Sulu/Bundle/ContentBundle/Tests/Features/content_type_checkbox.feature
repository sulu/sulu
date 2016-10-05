Feature: Checkbox content type
    In order to provide a checkbox field
    As a user
    I need to be able to enter checkbox numbers into a page

    Background:
        Given there exists a page template "checkbox_page" with the following property configuration
        """
        <property name="checkbox" type="checkbox">
            <meta>
                <title lang="de">Checkbox</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter a valid checkbox
        Given I am editing a page of type "checkbox_page"
        And I click on the element "#checkbox"
        When I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
