// @flow
import {ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {Router} from 'sulu-admin-bundle/services';
import {Form} from 'sulu-admin-bundle/views';
import webspaceStore from '../../../../stores/WebspaceStore';
import TemplateToolbarAction from '../../toolbarActions/TemplateToolbarAction';

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/containers', () => ({
    ResourceFormStore: class {
        resourceStore;
        types = {};
        typesLoading = true;

        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        changeType = jest.fn();
        setType = jest.fn();
    },
}));

jest.mock('sulu-admin-bundle/services', () => ({
    Router: jest.fn(),
}));

jest.mock('sulu-admin-bundle/views', () => ({
    // $FlowFixMe
    AbstractFormToolbarAction: require.requireActual('sulu-admin-bundle/views').AbstractFormToolbarAction,
    Form: jest.fn(function() {
        this.submit = jest.fn();
    }),
}));

jest.mock('../../../../stores/WebspaceStore', () => ({
    loadWebspace: jest.fn(),
}));

function createTemplateToolbarAction() {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new TemplateToolbarAction(resourceFormStore, form, router, [], {});
}

test('Return item config with correct disabled, loading, options, icon, type and value ', () => {
    const templateToolbarAction = createTemplateToolbarAction();
    templateToolbarAction.resourceFormStore.typesLoading = false;
    templateToolbarAction.resourceFormStore.type = 'default';
    templateToolbarAction.resourceFormStore.types = {
        default: {
            key: 'default',
            title: 'Default',
        },
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
    };

    expect(templateToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
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
    const templateToolbarAction = createTemplateToolbarAction();
    templateToolbarAction.resourceFormStore.typesLoading = true;
    templateToolbarAction.resourceFormStore.type = 'homepage';
    templateToolbarAction.resourceFormStore.types = {};

    expect(templateToolbarAction.getToolbarItemConfig()).toEqual(expect.objectContaining({
        loading: true,
        options: [],
        value: 'homepage',
    }));
});

test('Set the first value as default if nothing is given', () => {
    const templateToolbarAction = createTemplateToolbarAction();

    templateToolbarAction.router.attributes = {
        webspace: 'sulu',
    };

    templateToolbarAction.resourceFormStore.typesLoading = false;
    templateToolbarAction.resourceFormStore.types = {
        default: {
            key: 'default',
            title: 'Default',
        },
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
    };

    const webspacePromise = Promise.resolve({
        defaultTemplates: {
            page: 'homepage',
        },
    });
    webspaceStore.loadWebspace.mockReturnValue(webspacePromise);

    const toolbarItemConfig = templateToolbarAction.getToolbarItemConfig();

    if (toolbarItemConfig.type !== 'select') {
        throw new Error(
            'The returned toolbar item must be of type "select", but "' + toolbarItemConfig.type + '" was given!'
        );
    }

    return webspacePromise.then(() => {
        templateToolbarAction.getToolbarItemConfig();
        expect(templateToolbarAction.resourceFormStore.setType).toBeCalledWith('homepage');
    });
});

test('Change the type of the FormStore when another type is selected', () => {
    const templateToolbarAction = createTemplateToolbarAction();
    templateToolbarAction.resourceFormStore.typesLoading = false;
    templateToolbarAction.resourceFormStore.type = 'default';
    templateToolbarAction.resourceFormStore.types = {
        default: {
            key: 'default',
            title: 'Default',
        },
        homepage: {
            key: 'homepage',
            title: 'Homepage',
        },
    };

    const toolbarItemConfig = templateToolbarAction.getToolbarItemConfig();

    if (toolbarItemConfig.type !== 'select') {
        throw new Error(
            'The returned toolbar item must be of type "select", but "' + toolbarItemConfig.type + '" was given!'
        );
    }

    toolbarItemConfig.onChange('homepage');

    expect(templateToolbarAction.resourceFormStore.changeType).toBeCalledWith('homepage');
});

test('Throw error if no types are available in FormStore', () => {
    const templateToolbarAction = createTemplateToolbarAction();
    templateToolbarAction.resourceFormStore.typesLoading = false;
    templateToolbarAction.resourceFormStore.types = {};

    expect(() => templateToolbarAction.getToolbarItemConfig()).toThrow(/actually supporting types/);
});
