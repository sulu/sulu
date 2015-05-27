Feature: Login
    In order to use the backoffice
    As an administrator
    I need to login to the backoffice

    Scenario: Login
        Given I am logged in as an administrator
        Then I expect to see "Sulu"
