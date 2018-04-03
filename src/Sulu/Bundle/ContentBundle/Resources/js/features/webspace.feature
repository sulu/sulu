Feature: Surf webspaces
    In order to manage a website within Sulu
    As a content manager
    I want to have a webspace

    Scenario: Surf webspace
        Given a example webspace is setup
        When I open the webspace module
        Then example webspace appears
        And I can see the homepage of the example webspace
