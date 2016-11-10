Feature: Collection management
    In order to organize my medias
    As a user
    I need to be able to create and manage collections

    Background:
        Given I am logged in as an administrator

    Scenario: Create collection
        Given I am on "/admin/#media/collections/en"
        When I click the add icon
        And I expect an overlay to appear
        And I fill in husky field "title" with "Dornbirn"
        And I click the ok button
        And I expect to see "Dornbirn"
        Then the media collection "Dornbirn" should exist

    Scenario: Edit collection
        Given the media collection "Foobar" exists
        And I am on "/admin/#media/collections/en"
        When I click on the element ".tile"
        Then I expect the "husky.loader.initialized" event
        Then I should see "Foobar"
        Then I click on the element "li[data-id='editCollection']"
        And I expect an overlay to appear
        And I fill in husky field "title" with "Foobar changed"
        And I click the ok button
        Then I expect a success notification to appear
        Then the media collection "Foobar changed" should exist

    Scenario: Delete collection
        Given the media collection "Foobar" exists
        And I am editing the media collection "Foobar"
        When I click toolbar item "edit"
        And I click toolbar item "deleteCollection"
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
        When I click toolbar item "moveCollection"
        Then I expect an overlay to appear
        And I expect the "husky.column-navigation.collection-select.initialized" event
        And I click the column navigation item "All media"
        And I wait for the column navigation column 2
        And I double click the column navigation item "Foobar"
        And I click the ok button
        Then I am editing the media collection "Dornbirn"
        Then I expect to see "Foobar"

    Scenario: Delete item
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        And I am on the media collection edit page
        Then I expect to see "1" ".masonry-item" elements
        When I click on the element ".masonry-item"
        And I expect the "husky.datagrid.item.select" event
        And I click on the element ".toolbar-item[data-id='deleteSelected']"
        And I expect a confirmation dialog to appear
        And I confirm
        Then I expect a success notification to appear

    Scenario: Change item language
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        And I am on "/admin/#media/collections/en"
        When I click on the element ".masonry-item .action-icons-overlay"
        And I expect an overlay to appear
        Then I expect the "husky.tabs.overlaymedia-edit.initialized" event
        And I expect the "husky.dropzone.file-version.initialized" event
        And I fill in husky field "title" with "image of Dornbirn"
        And I select "de" from the husky "language-changer.husky-select"
        And I expect the "husky.datagrid.records.change" event
        Then I expect the "husky.tabs.overlaymedia-edit.initialized" event
        And I expect the "husky.dropzone.file-version.initialized" event
        And I fill in husky field "title" with "Foto von Dornbirn" in the overlay
        And I confirm
        Then I expect a success notification to appear
        And I should see "image of Dornbirn"
        And I click on the element ".language-changer"
        And I click on the element "[data-id='de']"
        And I expect the "husky.datagrid.initialized" event
        And I expect to see "Foto von Dornbirn"
