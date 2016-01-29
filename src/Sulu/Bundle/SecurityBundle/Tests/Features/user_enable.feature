Feature: Enable user
    In order to activate the user in the system
    As an admistrator
    I need to be able to see the button when contact has a user, but the user is not activated

    Background:
        Given I am logged in as an administrator

    Scenario: Activate user
        Given the not enabled user "mrNotEnabled" exists with password "thisIsSecret"
        And  I am editing the permission of a user with username "mrNotEnabled"
        When I click toolbar item "enable"
        Then I expect a success notification to appear
        And  I expect the toolbar item "enable" to be hidden

    Scenario: Activated user
        Given the user "mrEnabled" exists with password "thisIsSecret"
        And  I am editing the permission of a user with username "mrEnabled"
        Then I expect the toolbar item "enable" to be hidden
