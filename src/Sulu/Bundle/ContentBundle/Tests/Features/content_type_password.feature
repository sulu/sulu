Feature: Password content type
    In order to provide a password field
    As a user
    I need to be able to enter password numbers into a page

    Background:
        Given there exists a page template "password_page" with the following property configuration
        """
        <property name="password" type="password">
            <meta>
                <title lang="de">Password</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter a valid password number
        Given I am editing a page of type "password_page"
        When I fill in "husky-input-password" with "thisisapassword"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
