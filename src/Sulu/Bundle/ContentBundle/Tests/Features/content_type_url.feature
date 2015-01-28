Feature: Url content type
    In order to provide a url field
    As a user
    I need to be able to enter url numbers into a page

    Background:
        Given there exists a page template "url_page" with the following property configuration
        """
        <property name="url" type="url">
            <meta>
                <title lang="de">Url</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter a valid url
        Given I am editing a page of type "url_page"
        And I fill in "husky-input-url" with "foobar.com/asd"
        And I click the save icon
        Then I expect a success notification to appear

    Scenario Outline: Enter a invalid url
        Given I am editing a page of type "url_page"
        And I fill in "husky-input-url" with "<url>"
        And I click the save icon
        Then there should be 1 form errors
        Examples:
            | url |
            | http://foobar |
            | ??asdasd123--   234 |
            | foobar |
            | foobar  .com | 
