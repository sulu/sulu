Feature: Category content type
    In order to use make use of categories
    As a user
    I want to be able to select which categories apply to a page

    Background:
        Given I am logged in as an administrator
        And there exists a page template "category_page" with the following property configuration
        """
        <property name="category_list_test" type="category_list">
            <meta>
                <title lang="de">Kategorien</title>
                <title lang="en">Categories</title>
            </meta>
        </property>
        """

    Scenario: Select categories
        Given the category "Foobar Category" exists
        When I am editing a page of type "category_page"
        And I expect the aura component "category_list_test" to appear
        Then I expect to see "Foobar Category"
        # Checkbox has no name or ID, testing not practical:
        # https://github.com/sulu-cmf/sulu/issues/573
