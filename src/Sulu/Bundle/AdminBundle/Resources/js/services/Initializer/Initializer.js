// @flow
import {action, observable} from 'mobx';
import moment from 'moment';
import CKEditor5 from '../../components/CKEditor5';
import {
    ColumnListAdapter,
    datagridAdapterRegistry,
    datagridFieldTransformerRegistry,
    FolderAdapter,
    TableAdapter,
    TreeTableAdapter,
    BytesFieldTransformer,
    DateFieldTransformer,
    DateTimeFieldTransformer,
    NumberFieldTransformer,
    TimeFieldTransformer,
    StringFieldTransformer,
    ThumbnailFieldTransformer,
    BoolFieldTransformer,
} from '../../containers/Datagrid';
import {
    Selection,
    Checkbox,
    ColorPicker,
    DatePicker,
    Email,
    fieldRegistry,
    Input,
    Number,
    PasswordConfirmation,
    Phone,
    ResourceLocator,
    SmartContent,
    SingleSelect,
    SingleSelection,
    TextArea,
    TextEditor,
    Time,
    Url,
} from '../../containers/Form';
import FieldBlocks from '../../containers/FieldBlocks';
import {smartContentConfigStore} from '../../containers/SmartContent';
import {textEditorRegistry} from '../../containers/TextEditor';
import userStore from '../../stores/UserStore';
import {navigationRegistry} from '../../containers/Navigation';
import resourceMetadataStore from '../../stores/ResourceMetadataStore';
import {routeRegistry} from '../Router';
import Config from '../Config';
import {setTranslations} from '../../utils/Translator';
import Requester from '../Requester';
import {bundlesReadyPromise} from '../../services/Bundles';
import {viewRegistry} from '../../containers/ViewRenderer';
import Form, {
    toolbarActionRegistry,
    DeleteToolbarAction,
    SaveWithPublishingToolbarAction,
    SaveToolbarAction,
    TypeToolbarAction,
} from '../../views/Form';
import ResourceTabs from '../../views/ResourceTabs';
import Datagrid from '../../views/Datagrid';

function registerViews() {
    viewRegistry.add('sulu_admin.form', Form);
    viewRegistry.add('sulu_admin.resource_tabs', (ResourceTabs: any));
    viewRegistry.add('sulu_admin.datagrid', Datagrid);
}

function registerDatagridAdapters() {
    datagridAdapterRegistry.add('column_list', ColumnListAdapter);
    datagridAdapterRegistry.add('folder', FolderAdapter);
    datagridAdapterRegistry.add('table', TableAdapter);
    datagridAdapterRegistry.add('tree_table', TreeTableAdapter);
    datagridAdapterRegistry.add('tree_table_slim', TreeTableAdapter, {showHeader: false});
}

function registerDatagridFieldTransformers() {
    datagridFieldTransformerRegistry.add('bytes', new BytesFieldTransformer());
    datagridFieldTransformerRegistry.add('date', new DateFieldTransformer());
    datagridFieldTransformerRegistry.add('time', new TimeFieldTransformer());
    datagridFieldTransformerRegistry.add('datetime', new DateTimeFieldTransformer());
    datagridFieldTransformerRegistry.add('number', new NumberFieldTransformer());
    datagridFieldTransformerRegistry.add('string', new StringFieldTransformer());
    datagridFieldTransformerRegistry.add('thumbnails', new ThumbnailFieldTransformer());
    datagridFieldTransformerRegistry.add('bool', new BoolFieldTransformer());

    // TODO: Remove this type when not needed anymore
    datagridFieldTransformerRegistry.add('title', new StringFieldTransformer());
}

function registerFieldTypes(fieldTypeOptions) {
    fieldRegistry.add('block', FieldBlocks);
    fieldRegistry.add('checkbox', Checkbox);
    fieldRegistry.add('color', ColorPicker);
    fieldRegistry.add('date', DatePicker);
    fieldRegistry.add('email', Email);
    fieldRegistry.add('number', Number);
    fieldRegistry.add('password_confirmation', PasswordConfirmation);
    fieldRegistry.add('phone', Phone);
    fieldRegistry.add('resource_locator', ResourceLocator, {generationUrl: Config.endpoints.generateUrl});
    fieldRegistry.add('smart_content', SmartContent);
    fieldRegistry.add('single_select', SingleSelect);
    fieldRegistry.add('text_line', Input);
    fieldRegistry.add('text_area', TextArea);
    fieldRegistry.add('text_editor', TextEditor);
    fieldRegistry.add('time', Time);
    fieldRegistry.add('url', Url);

    registerFieldTypesWithOptions(fieldTypeOptions['selection'], Selection);
    registerFieldTypesWithOptions(fieldTypeOptions['single_selection'], SingleSelection);
}

function registerFieldTypesWithOptions(fieldTypeOptions, Component) {
    if (fieldTypeOptions) {
        for (const fieldTypeKey in fieldTypeOptions) {
            fieldRegistry.add(fieldTypeKey, Component, fieldTypeOptions[fieldTypeKey]);
        }
    }
}

function registerTextEditors() {
    textEditorRegistry.add('ckeditor5', CKEditor5);
}

function registerToolbarActions() {
    toolbarActionRegistry.add('sulu_admin.delete', DeleteToolbarAction);
    toolbarActionRegistry.add('sulu_admin.save_with_publishing', SaveWithPublishingToolbarAction);
    toolbarActionRegistry.add('sulu_admin.save', SaveToolbarAction);
    toolbarActionRegistry.add('sulu_admin.type', TypeToolbarAction);
}

function processConfig(config: Object) {
    routeRegistry.clear();
    navigationRegistry.clear();
    resourceMetadataStore.clear();

    routeRegistry.addCollection(config['sulu_admin'].routes);
    navigationRegistry.set(config['sulu_admin'].navigation);
    resourceMetadataStore.setEndpoints(config['sulu_admin'].resourceMetadataEndpoints);
    smartContentConfigStore.setConfig(config['sulu_admin'].smartContent);
}

function getBrowserLanguage() {
    // detect browser locale (ie, ff, chrome fallbacks)
    const language = window.navigator.languages ? window.navigator.languages[0] : null;

    return language || window.navigator.language || window.navigator.browserLanguage || window.navigator.userLanguage;
}

function getDefaultLocale() {
    const browserLanguage = getBrowserLanguage();

    // select only language
    const locale = browserLanguage.slice(0, 2).toLowerCase();
    if (Config.translations.indexOf(locale) === -1) {
        return Config.fallbackLocale;
    }

    return locale;
}

function setMomentLocale() {
    moment.locale(getBrowserLanguage());
}

class Initializer {
    @observable initialized: boolean = false;
    @observable initializedTranslationsLocale: ?string;
    @observable loading: boolean = false;

    @action clear() {
        this.initialized = false;
        this.initializedTranslationsLocale = undefined;
        this.loading = false;
    }

    @action setInitialized() {
        this.initialized = true;
    }

    @action setInitializedTranslationsLocale(locale: string) {
        this.initializedTranslationsLocale = locale;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    initializeTranslations() {
        const locale = userStore.user ? userStore.user.locale : getDefaultLocale();

        const promise = this.initializedTranslationsLocale === locale
            ? Promise.resolve()
            : Requester.get(Config.endpoints.translations + '?locale=' + locale).then((translations) => {
                setTranslations(translations, locale);
                this.setInitializedTranslationsLocale(locale);
            });

        return promise.then(() => {
            this.setLoading(false);
        });
    }

    initialize() {
        this.setLoading(true);
        return bundlesReadyPromise.then(() => {
            return Requester.get(Config.endpoints.config).then((config) => {
                if (!this.initialized) {
                    registerViews();
                    registerDatagridAdapters();
                    registerDatagridFieldTransformers();
                    registerFieldTypes(config['sulu_admin'].fieldTypeOptions);
                    registerTextEditors();
                    registerToolbarActions();
                    setMomentLocale();
                }

                processConfig(config);

                userStore.setUser(config['sulu_admin'].user);
                userStore.setContact(config['sulu_admin'].contact);
                userStore.setLoggedIn(true);

                this.setInitialized();
                return this.initializeTranslations();
            }).catch((error) => {
                if (error.status !== 401) {
                    return Promise.reject(error);
                }
                return this.initializeTranslations();
            });
        });
    }
}

export default new Initializer();
