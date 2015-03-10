Feature: Category management
    In order to manage categories
    As an administrator
    I want to be able to list, edit, create and delete categories

    Background:
        Given I am logged in as an administrator

    Scenario: List categories
        Given the category "Foobar Category" exists
        And the category "Barfoo Category" exists
        And I am on "/admin/#settings/categories"
        And I expect a data grid to appear
        Then I expect to see "Foobar Category"
        And I should see "Barfoo Category"

    Scenario: Edit a category
        Given the category "Foobar Category" exists
        And I wait a second
        And I am on "/admin/#settings/categories"
        And I click the edit icon
        And I expect a form to appear
        Then I expect to see "Name"
        And I expect to see "Key"
        And I fill in "change-name" with "New name"
        And I fill in "change-key" with "New key"
        And I click the save icon
        Then I expect a success notification to appear

    Scenario: Delete a category
        Given the category "Foobar Category" exists
        And I am on "/admin/#settings/categories"
        And I wait a second
        And I click the edit icon
        And I click delete from the drop down
        Then I expect a confirmation dialog to appear
        And I confirm
        And I wait a second
        # TODO: Missing success notification
        # See: https://github.com/sulu-cmf/sulu/issues/708
        And the category "Foobar Category" should not exist

    Scenario: Create a category
        Given I am on "/admin/#settings/categories"
        And I click the add icon
        And I expect a form to appear
        And I fill in "change-name" with "Chocolate"
        And I fill in "change-key" with "chocolate"
        And I click the save icon
        Then I expect a success notification to appear
        And the category "chocolate" should exist
