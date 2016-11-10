Feature: Text editor content type
    In order to manage a single editor of text on a page
    As a user
    I need to be able to do that

    Background:
        Given there exists a page template "text_editor_page" with the following property configuration
        """
        <property name="text_editor" type="text_editor">
            <meta>
                <title lang="de">Text editor</title>
            </meta>
        </property>
        """
        And I am logged in as an administrator

    Scenario: Enter some text in a text editor
        Given I am editing a page of type "text_editor_page"
        And I focus ".ckeditor-preview"
        And I expect the "husky.ckeditor.text_editor.initialized" event
        And I fill in CKEditor instance "text_editor" with "Hello this is some text"
        And I click the save icon
        And I click toolbar item "savePublish"
        And I confirm
        Then I expect a success notification to appear
