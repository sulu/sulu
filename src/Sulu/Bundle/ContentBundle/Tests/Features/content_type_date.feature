Feature: Date content type
    In order to provide a date field
    As a user
    I need to be able to enter date numbers into a page

    Background:
        Given there exists a page template "date_page" with the following property configuration
        """
        <property name="date" type="date">
            <meta>
                <title lang="de">Date</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter a valid date
        Given I am editing a page of type "date_page"
        And I expect the "husky.input.date.initialized" event
        When I click on the element "input[name='husky-input-date']"
        And I should see the date picker
        And I click on the element ".day"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
