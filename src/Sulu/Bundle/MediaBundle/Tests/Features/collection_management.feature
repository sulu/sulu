Feature: Collection management
    In order to organize my medias
    As a user
    I need to be able to create and manage collections

    Background:
        Given I am logged in as an administrator

    Scenario: Create collection
        Given I am on "/admin/#media/collections"
        And I click the add icon
        And I fill in husky field "title" with "Dornbirn"
        And I click the tick button
        Then I expect to see "Dornbirn"
        And the media collection "Dornbirn" should exist

    Scenario: Edit collection
        Given the media collection "Foobar" exists
        And I am on "/admin/#media/collections"
        And I expect a thumbnail to appear
        And I click on the element ".thumbnail"
        And I expect the "husky.loader.initialized" event
        Then I should see "Foobar"

    Scenario: Delete collection
        Given the media collection "Foobar" exists
        And I am on the settings page for the media collection
        And I click the trash icon
        Then I expect a confirmation dialog to appear
        And I confirm
        # TODO: No notification: And I expect a success notification to appear
        # See: https://github.com/sulu-cmf/sulu/issues/708
        And I wait a second
        And I wait a second
        Then the media collection "Foobar" should not exist

    Scenario: View collection
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        Given the file "image2.png" has been uploaded to the "Dornbirn" collection
        Given the file "image3.jpg" has been uploaded to the "Dornbirn" collection
        Given the file "image4.jpg" has been uploaded to the "Dornbirn" collection
        And I am on the media collection edit page
        Then I expect to see "4" ".item" elements

    Scenario: Delete item
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        And I am on the media collection edit page
        And I click on the element ".item"
        And I click the trash icon
        Then I expect a confirmation dialog to appear
        And I confirm
        # TODO: Notiication: And I expect a success notification to appear
        # See: https://github.com/sulu-cmf/sulu/issues/708
