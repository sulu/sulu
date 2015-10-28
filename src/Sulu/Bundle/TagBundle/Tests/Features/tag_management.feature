Feature: Manage tags
    In order to add and remove tags
    As an administrator
    I need a page where I can do that

    Background:
        Given I am logged in as an administrator

    Scenario: Add tags
        Given I am on "/admin/#settings/tags"
        And I expect the "husky.datagrid.view.rendered" event
        When I click the add icon
        And I fill in the selector "table input" with "Tag One"
        And I leave the selector "table input"
        And I expect the "husky.datagrid.updated" event
        And I click the add icon
        And I fill in the selector "table input" with "Tag Two"
        And I leave the selector "table input"
        And I expect the "husky.datagrid.updated" event
        Then I expect to see "Tag One"
        And I should see "Tag Two"

    Scenario: Delete tags
        Given the following tags exist:
            | name |
            | bar |
            | baz |
        And I am on "/admin/#settings/tags"
        When I click on the element "table th input"
        And I click the trash icon
        And I expect a confirmation dialog to appear
        And I confirm
        And I wait a second
        Then I should not see "bar"
