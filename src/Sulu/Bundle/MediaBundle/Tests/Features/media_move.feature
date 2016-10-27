Feature: Media move
    In order to organize my medias
    As a user
    I need to be able to move medias

    Background:
        Given the media collection "Foobar" exists
        And the file "image1.png" has been uploaded to the "Dornbirn" collection
        And the file "image2.png" has been uploaded to the "Dornbirn" collection
        And the file "image3.jpg" has been uploaded to the "Dornbirn" collection
        And the file "image4.jpg" has been uploaded to the "Dornbirn" collection

    Scenario: Move media
        Given I am logged in as an administrator
        When I am editing the media collection "Dornbirn"
        And I expect the "husky.datagrid.view.rendered" event
        Then I expect to see "4" ".masonry-item" elements
        When I click on the element ".masonry-item:nth-child(1) .custom-checkbox"
        And I click toolbar item "settings"
        And I click toolbar item "media-move"
        And I expect an overlay to appear
        And I expect the "husky.column-navigation.collection-select.initialized" event
        And I double click the column navigation item "Foobar"
        And I click the ok button
        Then I expect a success notification to appear
        When I am editing the media collection "Foobar"
        Then I expect to see "1" ".masonry-item" elements
        When I am editing the media collection "Dornbirn"
        Then I expect to see "3" ".masonry-item" elements

    Scenario: Move multiple media
        Given I am logged in as an administrator
        And I am editing the media collection "Dornbirn"
        And I expect the "husky.datagrid.view.rendered" event
        And I expect to see "4" ".masonry-item" elements
        When I click on the element ".masonry-item:nth-child(1) .custom-checkbox"
        And I click on the element ".masonry-item:nth-child(3) .custom-checkbox"
        And I click toolbar item "settings"
        And I click toolbar item "media-move"
        Then I expect an overlay to appear
        And I expect the "husky.column-navigation.collection-select.initialized" event
        When I double click the column navigation item "Foobar"
        And I click the ok button
        Then I expect a success notification to appear
        When I am editing the media collection "Foobar"
        Then I expect to see "2" ".masonry-item" elements
        When I am editing the media collection "Dornbirn"
        Then I expect to see "2" ".masonry-item" elements
