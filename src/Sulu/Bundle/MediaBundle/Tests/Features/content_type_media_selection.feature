Feature: Content type media selection
    In order to display medias on my page
    As a user
    I need a content type to do that

    Background:
        Given there exists a page template "media_page" with the following property configuration
        """
        <property name="media" type="media_selection">
            <meta>
                <title lang="de">Media</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator
        Given the file "image1.png" has been uploaded to the "Dornbirn" collection
        And the file "image2.png" has been uploaded to the "Dornbirn" collection
        And the file "image3.jpg" has been uploaded to the "Dornbirn" collection
        And the file "image4.jpg" has been uploaded to the "Dornbirn" collection

    Scenario: Select some medias
        Given I am editing a page of type "media_page"
        And I click the add icon
        And I click on the element "#media-selection-media-grid-group .entity-title"
        And I click on the element "#media-selection-media-grid-group .custom-checkbox input"
        And I click the tick button
        And I click the save icon
        Then I expect a success notification to appear
