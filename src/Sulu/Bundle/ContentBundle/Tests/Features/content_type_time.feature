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
        And I fill in "husky-input-time" with "<time>"
        And I click the save icon
        Then I expect a success notification to appear
        Examples:
            | time |
            | 23:00 |
            | 5:00pm |
            | 1:00 |

    # TODO: time validation
    # See: https://github.com/sulu-cmf/sulu/issues/729
    # Scenario Outline: Enter a invalid time
    #     Given I am editing a page of type "time_page"
    #     And I fill in "husky-input-time" with "<time>"
    #     And I click the save icon
    #     Then there should be 1 form errors
    #     Examples:
    #         | time |
    #         | 25:00 |
    #         | asdasd |
    #         | -1234 |
