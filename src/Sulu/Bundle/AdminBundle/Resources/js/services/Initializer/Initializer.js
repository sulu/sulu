// @flow
import {action, observable} from 'mobx';
import {
    ColumnListAdapter,
    datagridAdapterRegistry,
    datagridFieldTransformerRegistry,
    FolderAdapter,
    TableAdapter,
    TreeListAdapter,
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
    Assignment,
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
    SingleSelect,
    SingleSelection,
    TextArea,
    Time,
} from '../../containers/Form';
import FieldBlocks from '../../containers/FieldBlocks';
import userStore from '../../stores/UserStore';
import {navigationRegistry} from '../../containers/Navigation';
import resourceMetadataStore from '../../stores/ResourceMetadataStore';
import {routeRegistry} from '../Router';
import {setTranslations} from '../../utils/Translator';
import Requester from '../Requester';
import {bundlesReadyPromise} from '../../services/Bundles';
import {viewRegistry} from '../../containers/ViewRenderer';
import Form from '../../views/Form';
import ResourceTabs from '../../views/ResourceTabs';
import Datagrid from '../../views/Datagrid';

declare var SULU: Object;

function registerViews() {
    viewRegistry.add('sulu_admin.form', Form);
    viewRegistry.add('sulu_admin.resource_tabs', ResourceTabs);
    viewRegistry.add('sulu_admin.datagrid', Datagrid);
}

function registerDatagridAdapters() {
    datagridAdapterRegistry.add('column_list', ColumnListAdapter);
    datagridAdapterRegistry.add('folder', FolderAdapter);
    datagridAdapterRegistry.add('table', TableAdapter);
    datagridAdapterRegistry.add('tree_list', TreeListAdapter);
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
    fieldRegistry.add('resource_locator', ResourceLocator);
    fieldRegistry.add('single_select', SingleSelect);
    fieldRegistry.add('text_line', Input);
    fieldRegistry.add('text_area', TextArea);
    fieldRegistry.add('time', Time);

    registerFieldTypesWithOptions(fieldTypeOptions['assignment'], Assignment);
    registerFieldTypesWithOptions(fieldTypeOptions['single_selection'], SingleSelection);
}

function registerFieldTypesWithOptions(fieldTypeOptions, Component) {
    if (fieldTypeOptions) {
        for (const fieldTypeKey in fieldTypeOptions) {
            fieldRegistry.add(fieldTypeKey, Component, fieldTypeOptions[fieldTypeKey]);
        }
    }
}

function processConfig(config: Object) {
    routeRegistry.clear();
    navigationRegistry.clear();
    resourceMetadataStore.clear();

    routeRegistry.addCollection(config['sulu_admin'].routes);
    navigationRegistry.set(config['sulu_admin'].navigation);
    resourceMetadataStore.setEndpoints(config['sulu_admin'].endpoints);
}

function getDefaultLocale() {
    // detect browser locale (ie, ff, chrome fallbacks)
    let locale = window.navigator.languages ? window.navigator.languages[0] : null;
    locale = locale || window.navigator.language || window.navigator.browserLanguage || window.navigator.userLanguage;

    // select only language
    locale = locale.slice(0, 2).toLowerCase();
    if (SULU.translations.indexOf(locale) === -1) {
        return SULU.fallbackLocale;
    }

    return locale;
}

class Initializer {
    SULU = SULU;
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

        if (this.initializedTranslationsLocale === locale) {
            return Promise.resolve();
        }

        return Requester.get(SULU.endpoints.translations + '?locale=' + locale).then((translations) => {
            setTranslations(translations);
            this.setInitializedTranslationsLocale(locale);
        });
    }

    initialize() {
        this.setLoading(true);
        return bundlesReadyPromise.then(() => {
            return Requester.get(SULU.endpoints.config).then((config) => {
                if (!this.initialized) {
                    registerViews();
                    registerDatagridAdapters();
                    registerDatagridFieldTransformers();
                    registerFieldTypes(config['sulu_admin']['field_type_options']);
                }

                processConfig(config);

                userStore.setUser(config['sulu_admin'].user);
                userStore.setContact(config['sulu_admin'].contact);
                userStore.setLoggedIn(true);
            }).catch((error) => {
                if (error.status !== 401) {
                    return Promise.reject(error);
                }
            }).finally(() => {
                return this.initializeTranslations().then(() => {
                    this.setLoading(false);
                    this.setInitialized();
                });
            });
        });
    }
}

export default new Initializer();
