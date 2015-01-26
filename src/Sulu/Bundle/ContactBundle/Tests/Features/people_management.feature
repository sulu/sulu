Feature: Manage people
    In order to manage the people in the system
    As a user
    I want to be able to list, edit, delete and create people

    Background:
        Given I am logged in as an administrator
        And the email type "home" exists

    Scenario: List people
        Given the contact "Daniel" "Leech" with "home" email "daniel@dantleech.com" exists
        And I am on "/admin/#contacts/contacts"
        Then I expect to see "Daniel Leech"

    Scenario: Edit a person
        Given the contact "Daniel" "Leech" with "home" email "daniel@dantleech.com" exists
        And I am on "/admin/#contacts/contacts"
        And I click the edit icon in the row containing "Daniel Leech"
        And I expect a form to appear
        And I clear and fill in "first-name" with "John"
        And I clear and fill in "last-name" with "Smith"
        And I click the save icon
        # TODO: Then I expect a success notification to appear
        # See: https://github.com/sulu-cmf/sulu/issues/708
        And I wait for the ajax request
        And the contact "John" "Smith" should exist


    Scenario: Delete a person
        Given the contact "Daniel" "Leech" with "home" email "daniel@dantleech.com" exists
        And I am on "/admin/#contacts/contacts"
        And I expect a data grid to appear
        And I wait a second
        And I click the edit icon in the row containing "Daniel Leech"
        And I expect a form to appear
        And I click delete from the drop down
        Then I expect a confirmation dialog to appear
        And I confirm
        # TODO: Then I expect a success notification to appear
        # See: https://github.com/sulu-cmf/sulu/issues/708
        And I wait for the ajax request
        And the contact "Daniel" "Leech" should not exist

    Scenario: Create a person
        Given I am on "/admin/#contacts/contacts"
        And I expect a data grid to appear
        And I click the add icon
        Then I expect to see "Details"
        And I expect a form to appear
        And I fill in "first-name" with "Jane"
        And I fill in "last-name" with "Doe"
        And I fill in husky field "email" with "jane@doe.com"
        And I select "Mrs." from the husky "form-of-address"
        And I click the save icon
        # We should wait for the notification, not the AJAX request
        And I wait for the ajax request
        # TODO: Notification does not work
        # See: https://github.com/sulu-cmf/sulu/issues/708
        Then the contact "Jane" "Doe" should exist
