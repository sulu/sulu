Feature: Category management
    In order to manage categories
    As an administrator
    I want to be able to list, edit, create and delete categories

    Background:
        Given I am logged in as an administrator

    Scenario: List categories
        Given the category "Foobar Category" exists
        And the category "Barfoo Category" exists
        When I am on "/admin/#settings/categories"
        And I wait to see "Foobar Category"
        Then I should see "Barfoo Category"

    Scenario: Edit a category
        Given the category "Foobar Category" exists
        And I wait a second
        And I am on "/admin/#settings/categories"
        And I click the edit icon
        And I expect a form to appear
        And I expect the "sulu.tab.rendered" event
        And I expect to see "Name"
        And I expect to see "Key"
        When I fill in "change-name" with "New name"
        And I fill in "change-key" with "New key"
        And I click the save icon
        Then I expect a success notification to appear

    Scenario: Delete a category
        Given the category "Foobar Category" exists
        And I am on "/admin/#settings/categories"
        And I wait a second
        And I click the edit icon
        When I click delete from the drop down
        And I expect a confirmation dialog to appear
        And I confirm
        And I wait a second
        # TODO: Missing success notification
        # See: https://github.com/sulu-cmf/sulu/issues/708
        Then the category "Foobar Category" should not exist

    Scenario: Create a category
        Given I am on "/admin/#settings/categories"
        And I wait a second
        And I click the add icon
        And I expect a form to appear
        And I fill in "change-name" with "Chocolate"
        And I fill in "change-key" with "chocolate"
        When I click the save icon
        And I wait a second
        And the category "chocolate" should exist
