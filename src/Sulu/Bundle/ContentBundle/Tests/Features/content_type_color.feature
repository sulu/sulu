Feature: Color content type
    In order to provide a color field
    As a user
    I need to be able to enter color numbers into a page

    Background:
        Given there exists a page template "color_page" with the following property configuration
        """
        <property name="color" type="color">
            <meta>
                <title lang="de">Color</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter a valid color
        Given I am editing a page of type "color_page"
        And I click on the element "#color"
        And I should see the color picker
        When I fill in "husky-input-color" with "#cc3131"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
