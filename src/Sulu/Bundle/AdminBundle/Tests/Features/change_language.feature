Feature: Change the language of the backoffice
    In order to use the backoffice in a differnet language
    As a user
    I need to be able to change the backoffice language

    Background:
        Given I am logged in as an administrator

    Scenario: Change the language
        Given I am on "/admin"
        And I expect to see "en"
        And I expect to see "Contacts"
        And I expect to see "Settings"
        And I select "de" from the husky "locale-dropdown"
        Then I expect to see "de"
        And I expect to see "Kontakte"
        And I expect to see "Einstellungen"
