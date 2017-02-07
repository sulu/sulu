Feature: Time content type
    In order to provide a time field
    As a user
    I need to be able to enter time numbers into a page

    Background:
        Given there exists a page template "time_page" with the following property configuration
        """
        <property name="time" type="time">
            <meta>
                <title lang="de">Time</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario Outline: Enter a valid time
        Given I am editing a page of type "time_page"
        When I fill in "husky-input-time" with "<time>"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
        Examples:
            | time |
            | 5:00 pm |
            | 05:00 pm |
            | 11:00 pm |
            | 5:00 am |
            | 05:00 am |
            | 11:00 am |

    Scenario Outline: Enter a invalid time
        Given I am editing a page of type "time_page"
        When I fill in "husky-input-time" with "<time>"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then there should be 1 form errors
        Examples:
            | time |
            | 25:00 |
            | 01:61 am |
            | asdasd |
            | -1234 |
