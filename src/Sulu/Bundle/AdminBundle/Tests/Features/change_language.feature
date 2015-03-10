Feature: Change the language of the backoffice
    In order to use the backoffice in a differnet language
    As a user
    I need to be able to change the backoffice language

    Background:
        Given I am logged in as an administrator

    Scenario: Change the language
        Given I am on "/admin"
        And I select "de" from the husky "locale-dropdown"
        # It doesn't work: https://github.com/sulu-cmf/sulu/issues/574
