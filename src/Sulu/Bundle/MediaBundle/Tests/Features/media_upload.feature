Feature: Media upload
    In order to organize my medias
    As a user
    I need to be able to upload medias

    Background:
        Given I am logged in as an administrator

    # FIXME: Currently not working with Saucelabs (https://github.com/instaclick/php-webdriver/issues/63)
    #Scenario: Upload new media
    #    Given the media collection "Foobar" exists
    #    And I am editing the media collection "Foobar"
    #    And I wait to see "Drag and drop assets here to upload"
    #    When I attach the file "images/image1.png" to the current drop-zone
    #    And I attach the file "images/image2.png" to the current drop-zone
    #    And I attach the file "images/image3.jpg" to the current drop-zone
    #    And I attach the file "images/image4.jpg" to the current drop-zone
    #    Then I expect a success notification to appear
    #    And I expect to see "4" ".datagrid-container .item" elements

    Scenario: Upload new version
         Given the media collection "Foobar" exists
         And the file "image1.png" has been uploaded to the "Foobar" collection
         And I am editing the media collection "Foobar"
         And I wait to see "1" ".masonry-item" elements
         When I click on the element ".masonry-item:nth-child(1)"
         And I click on the element ".toolbar-item[data-id='editSelected']"
         And I wait for an overlay to appear
         # FIXME: Currently not working with Saucelabs (https://github.com/instaclick/php-webdriver/issues/63)
         #And I wait to see "Click or drag and drop new version"
         #When I attach the file "images/image2.png" to the current drop-zone
         #Then I expect a success notification to appear
         And I click the overlay tab "History"
         And I expect to see "1" "#media-versions .media-edit-link" elements
         # FIXME: Why is there only one version
         #And I expect to see "2" ".media-edit-link" elements
