Feature: Single select content type
    In order to provide a select field
    As a user
    I need to be able to select an item in a page

    Background:
        Given there exists a page template "single_select_page" with the following property configuration
        """
        <property name="select" type="single_select">
            <meta>
                <title lang="de">Select</title>
            </meta>
            <params>
                <param name="values" type="collection">
                    <param name="single_option1">
                        <meta>
                            <title lang="de">Option 1</title>
                            <title lang="en">Option 1</title>
                        </meta>
                    </param>
                    <param name="single_option2">
                        <meta>
                            <title lang="de">Option 2</title>
                            <title lang="en">Option 2</title>
                        </meta>
                    </param>
                </param>
            </params>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter a valid checkbox
        Given I am editing a page of type "single_select_page"
        And I click on the element "#select_1"
        When I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
