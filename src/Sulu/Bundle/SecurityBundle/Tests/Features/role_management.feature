Feature: Role management
    In order to manage the roles in the system
    As an admistrator
    I need to be able to create, edit, delete roles and assign permissions

    Background:
        Given I am logged in as an administrator

    Scenario: List roles
        Given the following roles exist:
            | name | system |
            | Content editor | Sulu |
            | Translator | Sulu | 
            | Designer | Sulu |
            | Boss | Foobar |
        And I am on "/admin/#settings/roles"
        Then I expect to see "Content editor"

    Scenario: Edit a role
        Given I am on "/admin/#settings/roles"
        And I wait to see "1" "#roles-list tbody .row" elements
        And I click the edit icon in the row containing "Sulu"
        And I expect the "husky.select.system.preselected.item" event
        And I fill in "name" with "Goat"
        And I click the save icon
        # TODO: Notification: Then I expect a success notification to appear
        # See: https://github.com/sulu-cmf/sulu/issues/708

    Scenario: Delete a role
        Given the following roles exist:
            | name | system |
            | Content editor | Sulu |
            | Translator | Sulu | 
        And I am on "/admin/#settings/roles"
        And I click the edit icon in the row containing "Translator"
        And I wait a second
        And I click delete from the drop down
        Then I expect a confirmation dialog to appear
        And I confirm
        And I expect the "husky.datagrid.view.rendered" event
        Then the role "Translator" should not exist

    Scenario: Create a role
        Given I am on "/admin/#settings/roles"
        And I wait a second
        And I click the add icon
        And I expect a form to appear
        And I fill in "name" with "Foobar"
        And I click the save icon
        And I wait a second
        Then the role "Foobar" should exist
        # TODO: Notification: Then I expect a success notification to appear
        # See: https://github.com/sulu-cmf/sulu/issues/708
