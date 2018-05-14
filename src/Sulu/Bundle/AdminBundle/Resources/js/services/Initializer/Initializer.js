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

function registerDatagridAdapters() {
    datagridAdapterRegistry.add('column_list', ColumnListAdapter);
    datagridAdapterRegistry.add('folder', FolderAdapter);
    datagridAdapterRegistry.add('table', TableAdapter);
    datagridAdapterRegistry.add('tree_list', TreeListAdapter);
}

function registerDatagridFieldTypes() {
    datagridFieldTransformerRegistry.add('bytes', new BytesFieldTransformer());
    datagridFieldTransformerRegistry.add('date', new DateFieldTransformer());
    datagridFieldTransformerRegistry.add('datetime', new DateTimeFieldTransformer());
    datagridFieldTransformerRegistry.add('string', new StringFieldTransformer());
    datagridFieldTransformerRegistry.add('thumbnails', new ThumbnailFieldTransformer());
    datagridFieldTransformerRegistry.add('bool', new BoolFieldTransformer());

    // TODO: Remove this type when not needed anymore
    datagridFieldTransformerRegistry.add('title', new StringFieldTransformer());
}

function registerFieldTypes(fieldTypesConfig) {
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

    const assignmentConfigs = fieldTypesConfig['assignment'];
    if (assignmentConfigs) {
        for (const assignmentKey in assignmentConfigs) {
            fieldRegistry.add(assignmentKey, Assignment, assignmentConfigs[assignmentKey]);
        }
    }
}

function initializeConfig(config: Object, initialized: boolean) {
    if (!initialized) {
        registerFieldTypes(config['sulu_admin']['field_type_options']);
        routeRegistry.addCollection(config['sulu_admin'].routes);
        navigationRegistry.set(config['sulu_admin'].navigation);
        resourceMetadataStore.setEndpoints(config['sulu_admin'].endpoints);
    }

    userStore.setUser(config['sulu_admin'].user);
    userStore.setContact(config['sulu_admin'].contact);
    userStore.setLoggedIn(true);
}

class Initializer {
    @observable initialized: boolean = false;
    @observable translationInitialized: boolean = false;

    @action setInitialized(initialized: boolean) {
        this.initialized = initialized;
    }

    @action setTranslationInitialized(initialized: boolean) {
        this.translationInitialized = initialized;
    }

    registerDatagrid() {
        registerDatagridAdapters();
        registerDatagridFieldTypes();
    }

    initialize() {
        return bundlesReadyPromise.then(() => {
            const translationsPromise = Requester.get('/admin/v2/translations?locale=en').then((translations) => {
                setTranslations(translations);
                this.setTranslationInitialized(true);
            });

            const configPromise = Requester.get('/admin/v2/config').then((config) => {
                if (!config.hasOwnProperty('sulu_admin')) {
                    return;
                }

                initializeConfig(config, this.initialized);
                this.setInitialized(true);
            }).catch(() => {});

            return Promise.all([translationsPromise, configPromise]);
        });
    }
}

export default new Initializer();
