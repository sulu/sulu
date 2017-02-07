Feature: Content management
    In order to manage pages
    As an content manager
    I want to be able to list, edit, create and delete pages

    Background:
        Given there exists a page template "article" with the following property configuration
        """
        <property name="body" type="text_area"/>
        """
        And the following pages exist:
            | template | url        | title     | parent | data                          |
            | article  | /article-1 | Article 1 |        | {"body": "This is article 1"} |
            | article  | /article-2 | Article 2 |        | {"body": "This is article 2"} |
        And I am logged in as an administrator

    Scenario: List pages
        Given I am on "/admin/#content/contents/sulu_io/de"
        And I expect the "husky.column-navigation.node.loaded" event
        Then I expect to see "Article 1"
        And I expect to see "Article 2"

    Scenario: View page
        Given I am on "/admin/#content/contents/sulu_io/de"
        And I expect the "husky.column-navigation.node.loaded" event
        And I expect to see "Article 1"
        When I double click the column navigation item "Article 1"
        And I expect the "sulu.content.initialized" event
        Then I expect the value of the property "title" is "Article 1"
        Then I expect the value of the property "url" is "/article-1"
        Then I expect the value of the property "body" is "This is article 1"

    Scenario: Edit page
        Given I am editing a page of type "article"
        When I fill in "title" with "Dornbirn"
        And I fill in "body" with "Dornbirn is great"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        And I expect a success notification to appear
        And I click the back icon
        Then I expect to see "Dornbirn"
        And I expect to see "Article 1"
        And I expect to see "Article 2"
        When I double click the column navigation item "Dornbirn"
        And I expect the "sulu.content.initialized" event
        And I wait and expect to see element "#title"
        Then I expect the value of the property "title" is "Dornbirn"
        And I expect the value of the property "body" is "Dornbirn is great"

    Scenario: Publish page
        Given I am editing a page of type "article"
        Then I expect the page state to be "Test"
        When I click the save icon
        And I click toolbar item "publish"
        And I confirm
        And I expect the "sulu.content.initialized" event
        Then I expect the page state to be "Published"
        When I fill in "title" with "Dornbirn"
        And I click the save icon
        And I click toolbar item "saveDraft"
        Then I expect the "sulu.header.tabs.label.show" event


    Scenario: Delete page
        Given I am on "/admin/#content/contents/sulu_io/de"
        And I expect the "husky.column-navigation.node.loaded" event
        And I expect to see "Article 1"
        When I click the column navigation item "Article 1"
        # FIXME should be improved
        #  - reload should not be necessary
        #  - drop-down item should have a speaking name
        And I click 1 from the drop down
        And I confirm
        And I reload the page
        Then I expect to see "Article 2"
        And I expect to see "1" ".column-item" elements

    Scenario: Create new translation
        Given I am editing a page of type "article"
        And I click "en" from the drop down
        And I confirm
        And I expect the "sulu.content.initialized" event
        And I wait and expect to see element "#title"
        Then I expect the value of the property "title" is ""

    Scenario: Create new translation with copy
        Given I am editing a page of type "article"
        And I click "en" from the drop down
        And I expect an overlay to appear
        # FIXME should be improved
        #  - drop-down item should have a speaking name
        And I click "0" from the drop down
        And I confirm
        And I wait and expect to see element "#title"
        Then I expect the value of the property "title" is "Behat Test Content"

# TODO missing testcases
#  - internal link
#  - external link
#  - multiple step tests (create - translate - to list)
#  - multiple step tests (create - internal-link - translate - to list)
