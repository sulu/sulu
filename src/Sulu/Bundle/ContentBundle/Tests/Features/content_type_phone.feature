Feature: Phone content type
    In order to provide a phone field
    As a user
    I need to be able to enter phone numbers into a page

    Background:
        Given there exists a page template "phone_page" with the following property configuration
        """
        <property name="phone" type="phone">
            <meta>
                <title lang="de">Phone</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter a valid phone number
        Given I am editing a page of type "phone_page"
        When I fill in "husky-input-phone" with "00331234123123"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
