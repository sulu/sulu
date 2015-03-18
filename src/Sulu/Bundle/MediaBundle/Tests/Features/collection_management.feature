Feature: Collection management
    In order to organize my medias
    As a user
    I need to be able to create and manage collections

    Background:
        Given I am logged in as an administrator

    Scenario: Create collection
        Given I am on "/admin/"
        And I click the "collections-edit" navigation item
        And I wait a second
        And I click the "Add collection" button
        And I wait a second
        And I fill in husky field "title" with "Dornbirn"
        And I click the tick button
        Then I expect to see "Dornbirn"
        And the media collection "Dornbirn" should exist

    Scenario: Edit collection
        Given the media collection "Foobar" exists
        And I click the "collections-edit" navigation item
        And wait a second
        And I click on the element ".data-navigation-item-thumb"
        And I expect the "husky.loader.initialized" event
        Then I should see "Foobar"

    Scenario: Delete collection
        Given the media collection "Foobar" exists
        And I am on the settings page for the media collection
        And I click toolbar item "delete"
        Then I expect a confirmation dialog to appear
        And I confirm
        # TODO: No notification: And I expect a success notification to appear
        # See: https://github.com/sulu-cmf/sulu/issues/708
        And I wait a second
        And I wait a second
        And the media collection "Foobar" should not exist

    Scenario: View collection
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        And the file "image2.png" has been uploaded to the "Dornbirn" collection
        And the file "image3.jpg" has been uploaded to the "Dornbirn" collection
        And the file "image4.jpg" has been uploaded to the "Dornbirn" collection
        And I am on the media collection edit page
        Then I expect to see "4" ".item" elements

    Scenario: Move collection
        Given the media collection "Dornbirn" exists
        And the media collection "Foobar" exists
        And I am editing the media collection "Dornbirn"
        When I click toolbar item "collection-move"
        Then I expect an overlay to appear
        And I expect the "husky.column-navigation.collection-select.initialized" event
        And I click the column navigation item "Collections"
        And I wait a second
        And I double click the column navigation item "Foobar"
        And I am on "/admin/"
        And I wait a second
        And I am editing the media collection "Foobar"
        And I expect to see "1" ".data-navigation-item" elements

    Scenario: Delete item
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        And I am on the media collection edit page
        And I click on the element ".item"
        And I click the trash icon
        Then I expect a confirmation dialog to appear
        And I confirm
        # TODO: Notiication: And I expect a success notification to appear
        # See: https://github.com/sulu-cmf/sulu/issues/708
