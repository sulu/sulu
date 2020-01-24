// @flow
import TypeToolbarAction from '../../toolbarActions/TypeToolbarAction';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';

jest.mock('../../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../../stores/ResourceStore', () => jest.fn());

jest.mock('../../../../containers/Form', () => ({
    ResourceFormStore: class {
        data = {};
        resourceStore;
        types = {};
        typesLoading = true;

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get id() {
            return this.resourceStore.id;
        }

        changeType = jest.fn();
    },
}));

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

function createTypeToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new TypeToolbarAction(resourceFormStore, form, router, [], options);
}

test('Return item config with correct disabled, loading, options, icon, type and value ', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.type = 'default';
    typeToolbarAction.resourceFormStore.types = {
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
    typeToolbarAction.resourceFormStore.typesLoading = true;
    typeToolbarAction.resourceFormStore.type = 'homepage';
    typeToolbarAction.resourceFormStore.types = {};

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: true,
        options: [],
        value: 'homepage',
    }));
});

test('Change the type of the FormStore when another type is selected', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.type = 'default';
    typeToolbarAction.resourceFormStore.types = {
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

    expect(typeToolbarAction.resourceFormStore.changeType).toBeCalledWith('homepage');
});

test('Throw error if no types are available in FormStore', () => {
    const typeToolbarAction = createTypeToolbarAction();
    typeToolbarAction.resourceFormStore.typesLoading = false;
    typeToolbarAction.resourceFormStore.types = {};

    expect(() => typeToolbarAction.getToolbarItemConfig()).toThrow(/actually supporting types/);
});

test('Return empty item config when passed visible condition is not met', () => {
    const typeToolbarAction = createTypeToolbarAction({visible_condition: 'url == "/"'});

    expect(typeToolbarAction.getToolbarItemConfig()).toBeUndefined();
});

test('Return item config when passed visible condition is met', () => {
    const typeToolbarAction = createTypeToolbarAction({visible_condition: 'url == "/"'});
    typeToolbarAction.resourceFormStore.data.url = '/';

    expect(typeToolbarAction.getToolbarItemConfig()).toBeDefined();
});

test('Return disabled true when passed disable condition is not met', () => {
    const typeToolbarAction = createTypeToolbarAction({disable_condition: 'url == "/"'});

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({disabled: false}));
});

test('Return disabled true when passed disable condition is met', () => {
    const typeToolbarAction = createTypeToolbarAction({disable_condition: 'url == "/"'});
    typeToolbarAction.resourceFormStore.data.url = '/';

    expect(typeToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({disabled: true}));
});
