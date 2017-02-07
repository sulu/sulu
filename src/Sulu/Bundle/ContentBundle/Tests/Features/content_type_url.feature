Feature: Url content type
    In order to provide a url field
    As a user
    I need to be able to enter url numbers into a page

    Background:
        Given there exists a page template "url_page" with the following property configuration
        """
        <property name="this_url" type="url">
            <meta>
                <title lang="de">Url</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario Outline: Enter a valid url
        Given I am editing a page of type "url_page"
        When I set the value of the property "this_url" to "<url>"
        Then I expect to see "<scheme>"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
        Examples:
        | url | scheme |
        | http://foobar.com | http:// |
        | https://foobar.com | https:// |
        | ftp://foobar.com | ftp:// |
        | ftps://foobar.com | ftps:// |

    Scenario Outline: Enter a invalid url
        Given I am editing a page of type "url_page"
        When I fill in the selector "#this_url .specific-part-input" with "<url>"
        And I leave the selector "#this_url .specific-part-input"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then there should be 1 form errors
        Examples:
            | url |
            | http://foobar |
            | ??asdasd123--   234 |
            | foobar |
            | foobar  .com |
