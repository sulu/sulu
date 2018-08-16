// @flow
import TypeToolbarAction from '../../toolbarActions/TypeToolbarAction';
import {FormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn(function() {}));

jest.mock('../../../../containers/Form', () => ({
    FormStore: class {
        resourceStore;
        types = {};
        typesLoading = true;

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        changeType = jest.fn();
    },
}));

jest.mock('../../../../services/Router', () => jest.fn(function() {}));

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createTypeToolbarAction() {
    const resourceStore = new ResourceStore('test');
    const formStore = new FormStore(resourceStore);
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new TypeToolbarAction(formStore, form, router);
}

test('Return item config with correct disabled, loading, options, icon, type and value ', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.formStore.typesLoading = false;
    typeToolbarAction.formStore.type = 'default';
    typeToolbarAction.formStore.types = {
        default: {
            key: 'default',
            title: 'Default',
        },
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
    };

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        icon: 'su-brush',
        loading: false,
        options: [
            {
                label: 'Default',
                value: 'default',
            },
            {
                label: 'Homepage',
                value: 'homepage',
            },
        ],
        type: 'select',
        value: 'default',
    }));
});

test('Return item config with loading select', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.formStore.typesLoading = true;
    typeToolbarAction.formStore.type = 'homepage';
    typeToolbarAction.formStore.types = {};

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: true,
        options: [],
        value: 'homepage',
    }));
});

test('Change the type of the FormStore when another type is selcted', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.formStore.typesLoading = false;
    typeToolbarAction.formStore.type = 'default';
    typeToolbarAction.formStore.types = {
        default: {
            key: 'default',
            title: 'Default',
        },
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
    };

    const toolbarItemConfig = typeToolbarAction.getToolbarItemConfig();

    if (toolbarItemConfig.type !== 'select') {
        throw new Error(
            'The returned toolbar item must be of type "select", but "' + toolbarItemConfig.type + '" was given!'
        );
    }

    toolbarItemConfig.onChange('homepage');

    expect(typeToolbarAction.formStore.changeType).toBeCalledWith('homepage');
});

test('Throw error if no types are available in FormStore', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.formStore.typesLoading = false;
    typeToolbarAction.formStore.types = {};

    expect(() => typeToolbarAction.getToolbarItemConfig()).toThrow(/actually supporting types/);
});
