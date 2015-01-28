Feature: Manage snippets
    In order to play with snippets
    As an administrator
    I need to be able to list, edit and create snippets

    Background:
        Given there exists a snippet template "summer" with the following property configuration
        """
        <property name="color" type="text_editor">
            <meta>
                <title lang="de">Farbe</title>
            </meta>
        </property>
        """
        And there exists a snippet template "winter" with the following property configuration
        """
        <property name="color" type="text_editor">
            <meta>
                <title lang="de">Farbe</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: List snippets
        Given the following snippets exist:
            | template | title | data |
            | winter | Penguin | {"color": "grey"} |
            | summer | Giraffe | {"color": "green"} |
            | winter | Snowman | {"color": "white"} |
        And I am on "/admin/#snippet/snippets/de"
        And I expect a data grid to appear
        Then I expect to see "Penguin"
        And I should see "Giraffe"
        And I should see "Snowman"

    Scenario: Edit snippet
        Given the following snippets exist:
            | template | title | data |
            | winter | Penguin | {"color": "grey"} |
        And I am on "/admin/#snippet/snippets/de"
        And I expect a data grid to appear
        And I click the edit icon in the row containing "Penguin"
        And I expect a form to appear
        And I expect to see "Penguin"
        And I fill in "title" with "Duck"
        And I click the save icon
        Then I expect a success notification to appear

    Scenario: Delete a snippet from list
        Given the following snippets exist:
            | template | title | data |
            | winter | Penguin | {"color": "grey"} |
        And I am on "/admin/#snippet/snippets/de"
        And I expect a data grid to appear
        And I click on the element "#snippet-list th .custom-checkbox input"
        And I click the trash icon
        Then I expect a confirmation dialog to appear
        And I confirm
        And I wait a second
        Then I should not see "Penguin"

    Scenario: Create a snippet
        # TODO: The following line hides a bug - the default (as configured in the standard edition) does
        #       not exist by default, but no error is displayed in this case, the system will simply hang.
        #       See: https://github.com/sulu-cmf/sulu/issues/711
        Given there exists a snippet template "default" with the following property configuration
        """
        <property name="color" type="text_editor">
            <meta>
                <title lang="de">Farbe</title>
            </meta>
        </property>
        """
        And I am on "/admin/#snippet/snippets/de"
        And I expect a data grid to appear
        And I wait a second
        And I click the add icon
        And I expect a form to appear
        And I fill in "title" with "Cow"
        And I click the save icon
