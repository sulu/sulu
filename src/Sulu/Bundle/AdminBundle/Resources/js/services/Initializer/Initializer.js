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
    datagridFieldTransformerRegistry.add('datetime', new DateTimeFieldTransformer());
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
    fieldRegistry.add('password_confirmation', PasswordConfirmation);
    fieldRegistry.add('phone', Phone);
    fieldRegistry.add('resource_locator', ResourceLocator);
    fieldRegistry.add('single_select', SingleSelect);
    fieldRegistry.add('text_line', Input);
    fieldRegistry.add('text_area', TextArea);
    fieldRegistry.add('time', Time);

    registerFieldTypesFromConfiguration(fieldTypeOptions['assignment'], Assignment);
    registerFieldTypesFromConfiguration(fieldTypeOptions['single_selection'], SingleSelection);
}

function registerFieldTypesFromConfiguration(fieldTypeOptions, Component) {
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

class Initializer {
    @observable initialized: boolean = false;
    @observable translationInitialized: boolean = false;
    @observable loading: boolean = false;

    @action clear() {
        this.initialized = false;
        this.translationInitialized = false;
        this.loading = false;
    }

    @action setInitialized() {
        this.initialized = true;
    }

    @action setTranslationInitialized(initialized: boolean) {
        this.translationInitialized = initialized;
    }

    @action setLoading(loading: boolean) {
        this.loading = loading;
    }

    initialize() {
        this.setLoading(true);
        return bundlesReadyPromise.then(() => {
            // TODO: Use correct locale here
            // TODO: Get this url from backend
            const translationsPromise = Requester.get('/admin/v2/translations?locale=en').then((translations) => {
                setTranslations(translations);
                this.setTranslationInitialized(true);
            });

            // TODO: Get this url from backend
            const configPromise = Requester.get('/admin/v2/config').then((config) => {
                if (!config.hasOwnProperty('sulu_admin')) {
                    return;
                }

                if (!this.initialized) {
                    registerViews();
                    registerDatagridAdapters();
                    registerDatagridFieldTransformers();
                    registerFieldTypes(config['sulu_admin']['field_type_options']);

                    this.setInitialized();
                }

                processConfig(config);

                userStore.setUser(config['sulu_admin'].user);
                userStore.setContact(config['sulu_admin'].contact);
                userStore.setLoggedIn(true);
            }).catch((error) => {
                if (error.status !== 401) {
                    return Promise.reject(error);
                }
            });

            return Promise.all([translationsPromise, configPromise])
                .finally(() => {
                    this.setLoading(false);
                });
        });
    }
}

export default new Initializer();
