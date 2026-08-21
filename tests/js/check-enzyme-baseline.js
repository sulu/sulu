/* eslint-disable flowtype/require-valid-file-annotation, import/no-nodejs-modules, max-len */

const fs = require('fs');
const path = require('path');

// Files that still use Enzyme. THIS LIST ONLY SHRINKS.
// Do not add entries: new tests are written with @testing-library/react.
const BASELINE = [
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Application/tests/Application.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Badge/tests/Badge.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/CKEditor5/tests/CKEditor5.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/DeleteDependantResourcesDialog/tests/DeleteDependantResourcesDialog.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/DeleteReferencedResourceDialog/tests/DeleteReferencedResourceDialog.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/FieldBlocks/tests/FieldBlocks.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/FieldBlocks/tests/FieldRenderer.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/Field.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/CardCollection.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/ChangelogLine.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Checkbox.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/ColorPicker.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/DatePicker.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Email.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Heading.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Input.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Link.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Number.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/PasswordConfirmation.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Phone.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/ResourceLocator.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Select.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Selection.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/SingleIconSelect.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/SingleSelect.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/SingleSelection.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/SmartContent.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/TextArea.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/TextEditor.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/fields/Url.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/Form.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/GhostDialog.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/MissingTypeDialog.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/Renderer.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Form/tests/Section.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/FormOverlay/tests/FormOverlay.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Link/tests/Link.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Link/tests/overlays/ExternalLinkTypeOverlay.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Link/tests/overlays/LinkTypeOverlay.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/adapters/ColumnListAdapter.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/adapters/FolderAdapter.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/adapters/TableAdapter.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/adapters/TreeTableAdapter.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/AdapterSwitch.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/FieldFilter.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/FieldFilterItem.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/fieldFilterTypes/BooleanFieldFilterType.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/fieldFilterTypes/DateTimeFieldFilterType.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/fieldFilterTypes/NumberFieldFilterType.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/fieldFilterTypes/SelectFieldFilterType.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/fieldFilterTypes/SelectionFieldFilterType.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/fieldFilterTypes/TextFieldFilterType.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/fieldTransformers/HtmlFieldTransformer.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/List.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/List/tests/Search.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/ListOverlay/tests/ListOverlay.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Login/tests/ForgotPasswordForm.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Login/tests/Login.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Login/tests/LoginForm.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Login/tests/ResetPasswordForm.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Login/tests/TwoFactorForm.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/MultiAutoComplete/tests/MultiAutoComplete.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/MultiListOverlay/tests/MultiListOverlay.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/MultiSelection/tests/MultiSelection.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Navigation/tests/Navigation.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/ProfileFormOverlay/tests/ProfileFormOverlay.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/ResourceCheckboxGroup/tests/ResourceCheckboxGroup.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/ResourceLocatorHistory/tests/ResourceLocatorHistory.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/ResourceMultiSelect/tests/ResourceMultiSelect.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/ResourceSingleSelect/tests/EditLine.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/ResourceSingleSelect/tests/EditOverlay.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/ResourceSingleSelect/tests/ResourceSingleSelect.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Sidebar/tests/Sidebar.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Sidebar/tests/withSidebar.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/SingleAutoComplete/tests/SingleAutoComplete.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/SingleListOverlay/tests/SingleListOverlay.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/SingleSelection/tests/SingleSelection.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/SmartContent/tests/FilterOverlay.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/SmartContent/tests/SmartContent.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/SmartContent/tests/SmartContentItem.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/TextEditor/tests/adapters/CKEditor5.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/TextEditor/tests/TextEditor.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Toolbar/tests/Toolbar.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/Toolbar/tests/withToolbar.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/containers/ViewRenderer/tests/ViewRenderer.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/Form.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/CopyLocaleToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/CopyToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/DeleteDraftToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/DeleteToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/DropdownToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/ReloadFormStoreToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/SaveWithFormDialogToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/SetUnpublishedToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/TypeToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Form/tests/toolbarActions/UpdateFormStoreToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/FormOverlayList/tests/FormOverlayList.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/List/tests/List.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/List/tests/toolbarActions/ExportToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/List/tests/toolbarActions/UploadToolbarAction.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/PreviewForm/tests/PreviewForm.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/ResourceTabs/tests/ResourceTabs.test.js',
    'src/Sulu/Bundle/AdminBundle/Resources/js/views/Tabs/tests/Tabs.test.js',
    'src/Sulu/Bundle/AudienceTargetingBundle/Resources/js/containers/Form/tests/fields/TargetGroupRules.test.js',
    'src/Sulu/Bundle/AudienceTargetingBundle/Resources/js/containers/TargetGroupRules/tests/Condition.test.js',
    'src/Sulu/Bundle/AudienceTargetingBundle/Resources/js/containers/TargetGroupRules/tests/ConditionList.test.js',
    'src/Sulu/Bundle/AudienceTargetingBundle/Resources/js/containers/TargetGroupRules/tests/RuleOverlay.test.js',
    'src/Sulu/Bundle/AudienceTargetingBundle/Resources/js/containers/TargetGroupRules/tests/ruleTypes/Input.test.js',
    'src/Sulu/Bundle/AudienceTargetingBundle/Resources/js/containers/TargetGroupRules/tests/ruleTypes/KeyValue.test.js',
    'src/Sulu/Bundle/AudienceTargetingBundle/Resources/js/containers/TargetGroupRules/tests/ruleTypes/SingleSelect.test.js',
    'src/Sulu/Bundle/AudienceTargetingBundle/Resources/js/containers/TargetGroupRules/tests/ruleTypes/SingleSelection.test.js',
    'src/Sulu/Bundle/AudienceTargetingBundle/Resources/js/containers/TargetGroupRules/tests/TargetGroupRules.test.js',
    'src/Sulu/Bundle/ContactBundle/Resources/js/containers/ContactAccountSelection/tests/ContactAccountSelection.test.js',
    'src/Sulu/Bundle/ContactBundle/Resources/js/containers/Form/tests/fields/Bic.test.js',
    'src/Sulu/Bundle/ContactBundle/Resources/js/containers/Form/tests/fields/ContactAccountSelection.test.js',
    'src/Sulu/Bundle/ContactBundle/Resources/js/containers/Form/tests/fields/ContactDetails.test.js',
    'src/Sulu/Bundle/ContactBundle/Resources/js/containers/Form/tests/fields/Iban.test.js',
    'src/Sulu/Bundle/ContactBundle/Resources/js/containers/List/tests/fieldFilterTypes/CountryFieldFilterType.test.js',
    'src/Sulu/Bundle/ContactBundle/Resources/js/views/List/tests/toolbarActions/AddContactToolbarAction.test.js',
    'src/Sulu/Bundle/ContactBundle/Resources/js/views/List/tests/toolbarActions/AddMediaToolbarAction.test.js',
    'src/Sulu/Bundle/ContactBundle/Resources/js/views/List/tests/toolbarActions/DeleteMediaToolbarAction.test.js',
    'src/Sulu/Bundle/CustomUrlBundle/Resources/js/containers/Form/tests/fields/CustomUrl.test.js',
    'src/Sulu/Bundle/CustomUrlBundle/Resources/js/containers/Form/tests/fields/CustomUrlsDomainSelect.test.js',
    'src/Sulu/Bundle/CustomUrlBundle/Resources/js/containers/Form/tests/fields/CustomUrlsLocaleSelect.test.js',
    'src/Sulu/Bundle/LocationBundle/Resources/js/containers/Form/tests/fields/Location.test.js',
    'src/Sulu/Bundle/LocationBundle/Resources/js/containers/Location/tests/Location.test.js',
    'src/Sulu/Bundle/LocationBundle/Resources/js/containers/Location/tests/LocationOverlay.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/Form/tests/fields/ImageMap.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/Form/tests/fields/MediaSelection.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/Form/tests/fields/MediaVersionUpload.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/Form/tests/fields/SingleMediaSelection.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/Form/tests/fields/SingleMediaUpload.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/ImageMap/tests/Button.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/Link/tests/overlays/MediaLinkTypeOverlay.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/List/tests/adapters/MediaCardAdapter.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/List/tests/adapters/MediaCardOverviewAdapter.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MediaCollection/tests/CollectionFormOverlay.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MediaCollection/tests/MediaCollection.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MediaCollection/tests/PermissionFormOverlay.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MediaSelectionOverlay/tests/MediaSelectionOverlay.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MediaVersionUpload/tests/CropOverlay.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MediaVersionUpload/tests/FocusPointOverlay.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MediaVersionUpload/tests/MediaVersionUpload.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MultiMediaDropzone/tests/MultiMediaDropzone.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MultiMediaSelection/tests/MultiMediaSelection.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/MultiMediaSelectionOverlay/tests/MultiMediaSelectionOverlay.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/SingleMediaSelection/tests/SingleMediaSelection.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/SingleMediaSelectionOverlay/tests/SingleMediaSelectionOverlay.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/containers/SingleMediaUpload/tests/SingleMediaUpload.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/views/MediaFormats/tests/MediaFormats.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/views/MediaHistory/tests/MediaHistory.test.js',
    'src/Sulu/Bundle/MediaBundle/Resources/js/views/MediaOverview/tests/MediaOverview.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/containers/Form/tests/fields/PageSettingsNavigationSelect.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/containers/Form/tests/fields/PageSettingsShadowLocaleSelect.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/containers/Form/tests/fields/SearchResult.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/containers/Form/tests/fields/SegmentSelect.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/containers/Form/tests/fields/SettingsVersions.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/containers/Form/tests/fields/TeaserSelection.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/containers/SegmentSelect/tests/SegmentSelect.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/containers/TeaserSelection/tests/Item.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/containers/TeaserSelection/tests/TeaserSelection.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/views/List/tests/itemActions/RestoreVersionItemAction.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/views/PageList/tests/PageList.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/views/PageTabs/tests/PageTabs.test.js',
    'src/Sulu/Bundle/PageBundle/Resources/js/views/WebspaceTabs/tests/WebspaceTabs.test.js',
    'src/Sulu/Bundle/PreviewBundle/Resources/js/containers/Preview/tests/Preview.test.js',
    'src/Sulu/Bundle/PreviewBundle/Resources/js/containers/Preview/tests/PreviewLinkPopover.test.js',
    'src/Sulu/Bundle/RouteBundle/Resources/js/containers/Form/tests/fields/PageTreeRoute.test.js',
    'src/Sulu/Bundle/SearchBundle/Resources/js/containers/Search/tests/Search.test.js',
    'src/Sulu/Bundle/SearchBundle/Resources/js/containers/Search/tests/SearchField.test.js',
    'src/Sulu/Bundle/SearchBundle/Resources/js/containers/Search/tests/SearchResult.test.js',
    'src/Sulu/Bundle/SearchBundle/Resources/js/views/Search/tests/Search.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/Form/tests/fields/Permissions.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/Form/tests/fields/RoleAssignments.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/Form/tests/fields/RolePermissions.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/Form/tests/fields/TwoFactor.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/Permissions/tests/PermissionMatrix.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/Permissions/tests/Permissions.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/RoleAssignments/tests/RoleAssignment.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/RoleAssignments/tests/RoleAssignments.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/RolePermissions/tests/RolePermissions.test.js',
    'src/Sulu/Bundle/SecurityBundle/Resources/js/containers/RolePermissions/tests/SystemRolePermissions.test.js',
    'src/Sulu/Bundle/SnippetBundle/Resources/js/views/SnippetAreas/tests/SnippetAreas.test.js',
    'src/Sulu/Bundle/TrashBundle/Resources/js/containers/RestoreFormOverlay/tests/RestoreFormOverlay.test.js',
    'src/Sulu/Bundle/TrashBundle/Resources/js/views/List/itemActions/tests/RestoreItemAction.test.js',
    'src/Sulu/Bundle/WebsiteBundle/Resources/js/containers/CacheClearToolbarAction/tests/CacheClearToolbarAction.test.js',
    'src/Sulu/Bundle/WebsiteBundle/Resources/js/containers/Form/tests/fields/AnalyticsDomainSelect.test.js',
    'tests/js/testSetup.config.js',
];

const SCAN_DIRECTORIES = ['src', 'tests'];
const IGNORED_DIRECTORIES = new Set(['node_modules', 'vendor', 'flow-typed', 'build']);
const SOURCE_EXTENSIONS = new Set(['.js', '.jsx']);
const ENZYME_IMPORT = /(?:from|require\()\s*['"](?:enzyme|enzyme-to-json|@wojtekmaj\/enzyme-adapter[^'"]*)(?:\/[^'"]*)?['"]/;

function collectSourceFiles(directory, files) {
    for (const entry of fs.readdirSync(directory, {withFileTypes: true})) {
        const entryPath = path.join(directory, entry.name);

        if (entry.isDirectory()) {
            if (!IGNORED_DIRECTORIES.has(entry.name)) {
                collectSourceFiles(entryPath, files);
            }
        } else if (SOURCE_EXTENSIONS.has(path.extname(entry.name))) {
            files.push(entryPath.split(path.sep).join('/'));
        }
    }

    return files;
}

function findFilesUsingEnzyme() {
    const files = SCAN_DIRECTORIES.reduce((collected, directory) => collectSourceFiles(directory, collected), []);

    return files.filter((file) => ENZYME_IMPORT.test(fs.readFileSync(file, 'utf8'))).sort();
}

function report(headline, files, hint) {
    process.stderr.write('\x1b[31m' + headline + '\x1b[0m\n');
    files.forEach((file) => process.stderr.write('  ' + file + '\n'));
    process.stderr.write('\n' + hint + '\n\n');
}

const filesUsingEnzyme = findFilesUsingEnzyme();
const baseline = new Set(BASELINE);
const found = new Set(filesUsingEnzyme);

const added = filesUsingEnzyme.filter((file) => !baseline.has(file));
const removed = BASELINE.filter((file) => !found.has(file));

if (added.length > 0) {
    report(
        'Enzyme is used in files that are not part of the baseline:',
        added,
        'Enzyme is deprecated in this repository. Write new tests with @testing-library/react.'
    );
}

if (removed.length > 0) {
    report(
        'Baseline files that no longer use Enzyme:',
        removed,
        'They were migrated, renamed or deleted. Remove them from BASELINE in tests/js/check-enzyme-baseline.js.'
    );
}

if (added.length > 0 || removed.length > 0) {
    process.exitCode = 1;
} else {
    process.stdout.write('No new Enzyme usage. ' + filesUsingEnzyme.length + ' file(s) left to migrate.\n');
}
