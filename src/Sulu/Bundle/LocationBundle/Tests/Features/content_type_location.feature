Feature: Location content type
    In order to manage a single area of text on a page
    As a user
    I need to be able to do that

    Background:
        Given there exists a page template "location_page" with the following property configuration
        """
        <property name="location" type="location">
            <meta>
                <title lang="de">Location</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter some text in a text area
        Given I am editing a page of type "location_page"
        And I expect the aura component "location" to appear
        When I click the gears icon
        And I expect an overlay to appear
        And I fill in husky field "title" with "My Location" in the overlay
        And I fill in husky field "street" with "Dornbirn Straße" in the overlay
        And I fill in husky field "number" with "1" in the overlay
        And I fill in husky field "code" with "123456" in the overlay
        And I fill in husky field "town" with "Dornbirn" in the overlay
        And I fill in husky field "long" with "9.7669" in the overlay
        And I fill in husky field "lat" with "47.405" in the overlay
        And I fill in husky field "zoom" with "10" in the overlay
        And I click the ok button
        And wait a second
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear

        # TODO: Closing and opening does not work
        # See: https://github.com/sulu-cmf/sulu/issues/710
        # Scenario: Close and reopen the dialog
        #     Given I am editing a page of type "location_page"
        #     And I expect the aura component "location" to appear
        #     And I click the gears icon
        #     Then I should see "Ort auswählen"
        #     And I click the ok button
        #     And I click the gears icon
        #     Then I should see "Ort auswählen"
