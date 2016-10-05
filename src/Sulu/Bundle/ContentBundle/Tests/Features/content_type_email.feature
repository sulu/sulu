Feature: Email content type
    In order to provide a email field
    As a user
    I need to be able to enter email numbers into a page

    Background:
        Given there exists a page template "email_page" with the following property configuration
        """
        <property name="email" type="email">
            <meta>
                <title lang="de">Email</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter a valid email
        Given I am editing a page of type "email_page"
        When I fill in "husky-input-email" with "daniel@dantleech.com"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear

    Scenario Outline: Enter a invalid email
        Given I am editing a page of type "email_page"
        When I fill in "husky-input-email" with "<email>"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then there should be 1 form errors
        Examples:
            | email |
            | http://foobar |
            | daniel@dan |
