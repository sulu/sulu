Feature: Execute search requests over a web API
    In order to retrieve serialized search results 
    As a web developer
    I need to be able to query a REST API

    Background:
        Given I am logged in as an administrator
        Given there exists a page template "article" with the following property configuration
        """
        <property name="body" type="text_area">
            <meta>
                <title lang="de">Body</title>
            </meta>
            <tag name="sulu.search.field" role="description" />
        </property>
        """
        And the following pages exist:
            | locale | template | url | title | parent | data |
            | en | article | /travels | Travel | | {"body": "Travelling is awesome"} |
            | fr | article | /voyages | Voyage | | {"body": "J'aime bien les voyages"} |
            | en | article | /bears | Bear | | {"body": "Bears are also good"} |
            | fr | article | /les-ours | Ours | | {"body": "Les ours sont aussi bien"} |
        And the email type "home" exists
        And the contact "Daniel" "Leech" with "home" email "daniel@dantleech.com" exists
        And the contact "Bear" "Grylls" with "home" email "grylls@bear.com" exists

    Scenario: I search without a locale with no indexes
        Given I am on "/admin/search?q=Bear"
        Then I should receieve the following JSON response:
        """
        {}
        """
