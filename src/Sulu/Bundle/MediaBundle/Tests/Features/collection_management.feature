Feature: Collection management
    In order to organize my medias
    As a user
    I need to be able to create and manage collections

    Background:
        Given I am logged in as an administrator

    Scenario: Create collection
        Given I am on "/admin/#media/collections/root"
        # FIXME bad fix here but data-navigation needs additional time ...
        And I expect a data-navigation to appear
        When I click the add icon
        And I expect an overlay to appear
        And I fill in husky field "title" with "Dornbirn"
        And I click the ok button
        And I expect to see "Dornbirn"
        Then the media collection "Dornbirn" should exist

    Scenario: Edit collection
        Given the media collection "Foobar" exists
        And I am on "/admin/#media/collections/root"
        And I expect a data-navigation to appear
        When I click on the element ".data-navigation-item"
        Then I expect the "husky.loader.initialized" event
        Then I should see "Foobar"

    Scenario: Delete collection
        Given the media collection "Foobar" exists
        And I am on the settings page for the media collection
        And I expect a data-navigation to appear
        When I click deleteCollection from the drop down
        And I expect a confirmation dialog to appear
        And I confirm
        Then I expect a success notification to appear
        Then the media collection "Foobar" should not exist

    Scenario: View collection
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        And the file "image2.png" has been uploaded to the "Dornbirn" collection
        And the file "image3.jpg" has been uploaded to the "Dornbirn" collection
        And the file "image4.jpg" has been uploaded to the "Dornbirn" collection
        When I am on the media collection edit page
        Then I expect to see "4" ".masonry-item" elements

    Scenario: Move collection
        Given the media collection "Dornbirn" exists
        And the media collection "Foobar" exists
        And I am editing the media collection "Dornbirn"
        And I expect a data-navigation to appear
        When I click the toolbar button "Move collection"
        Then I expect an overlay to appear
        And I expect the "husky.column-navigation.collection-select.initialized" event
        And I click the column navigation item "Collections"
        And I wait for the column navigation column 2
        And I double click the column navigation item "Foobar"
        And I am on "/admin/"
        And I wait a second
        And I am editing the media collection "Foobar"
        Then I expect to see "1" ".data-navigation-item" elements

    Scenario: Delete item
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        And I am on the media collection edit page
        Then I expect to see "1" ".masonry-item" elements
        When I click on the element ".masonry-item"
        And I wait a second
        And I click on the element ".toolbar-item[data-id='deleteSelected']"
        And I expect a confirmation dialog to appear
        And I confirm
        Then I expect a success notification to appear

    Scenario: Change item language
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        And I am on "/admin/#media/collections/root"
        # FIXME bad fix here but data-navigation needs additional time ...
        Then I expect a data-navigation to appear
        When I click on the element ".masonry-item .head-image"
        And I expect an overlay to appear
        Then I expect the "husky.tabs.overlaymedia-edit.initialized" event
        And I fill in husky field "title" with "image of Dornbirn"
        And I select "de" from the husky "language-changer .husky-select"
        Then I expect the "husky.tabs.overlaymedia-edit.initialized" event
        And I fill in husky field "title" with "Foto von Dornbirn" in the overlay
        And I confirm
        Then I should see "image of Dornbirn"
        And I click on the element ".language-changer"
        And I click on the element "[data-id='de']"
        And I expect the "husky.datagrid.updated" event
        And I should see "Foto von Dornbirn"

