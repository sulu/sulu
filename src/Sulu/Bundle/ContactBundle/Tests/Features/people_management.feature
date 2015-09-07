Feature: Manage people
    In order to manage the people in the system
    As a user
    I want to be able to list, edit, delete and create people

    Background:
        Given I am logged in as an administrator
        And the email type "home" exists

    Scenario: List people
        Given the contact "Daniel" "Leech" with "home" email "daniel@dantleech.com" exists
        When I am on "/admin/#contacts/contacts"
        Then I expect to see "Daniel"

    Scenario: Edit a person
        Given the contact "Daniel" "Leech" with "home" email "daniel@dantleech.com" exists
        And I am on "/admin/#contacts/contacts"
        And I click the card containing "Daniel Leech"
        And I expect a form to appear
        And I clear and fill in "first-name" with "John"
        And I clear and fill in "last-name" with "Smith"
        When I click the save icon
        Then I expect a success notification to appear
        Then the contact "John" "Smith" should exist


    Scenario: Delete a person
        Given the contact "Daniel" "Leech" with "home" email "daniel@dantleech.com" exists
        And I am on "/admin/#contacts/contacts"
        And I expect a data grid to appear
        And I wait a second
        And I click the card containing "Daniel Leech"
        And I expect a form to appear
        When I click delete from the drop down
        And I expect a confirmation dialog to appear
        And I confirm
        Then I expect a success notification to appear
        Then the contact "Daniel" "Leech" should not exist

    Scenario: Create a person
        Given I am on "/admin/#contacts/contacts"
        And I expect a data grid to appear
        And I click the add icon
        And I expect a form to appear
        And I expect to see "First Name"
        And I expect to see "Last Name"
        And I fill in "first-name" with "Jane"
        And I fill in "last-name" with "Doe"
        And I fill in husky field "email" with "jane@doe.com"
        And I select "Mrs." from the husky "form-of-address"
        When I click the save icon
        Then I expect a success notification to appear
        Then the contact "Jane" "Doe" should exist
