// @flow
import React from 'react';
import {render} from 'enzyme';
import {ResourceFormStore} from '../../../../containers/Form';
import ResourceStore from '../../../../stores/ResourceStore';
import Router from '../../../../services/Router';
import Form from '../../../../views/Form';
import formToolbarActionRegistry from '../../registries/formToolbarActionRegistry';
import DropdownToolbarAction from '../../toolbarActions/DropdownToolbarAction';

jest.mock('../../../../containers/Form/stores/ResourceFormStore', () => (
    class {
        resourceStore;
        constructor(resourceStore) {
            this.resourceStore = resourceStore;
        }

        get dirty() {
            return this.resourceStore.dirty;
        }

        get saving() {
            return this.resourceStore.saving;
        }

        get data() {
            return this.resourceStore.data;
        }
    }
));

jest.mock('../../../../services/Router', () => jest.fn());

jest.mock('../../../../views/Form', () => jest.fn(function() {
    this.submit = jest.fn();
}));

jest.mock('../../registries/formToolbarActionRegistry', () => ({
    get: jest.fn(),
}));

function createDropdownToolbarAction(options = {}) {
    const resourceStore = new ResourceStore('test');
    const resourceFormStore = new ResourceFormStore(resourceStore, 'test');
    const router = new Router({});
    const form = new Form({
        locales: [],
        resourceStore,
        route: router.route,
        router,
    });

    return new DropdownToolbarAction(resourceFormStore, form, router, [], options);
}

test('Return item config with an option for every action in array and skip undefined button', () => {
    const deleteClickSpy = jest.fn();
    const copyClickSpy = jest.fn();

    formToolbarActionRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'sulu_admin.delete':
                return class {
                    getToolbarItemConfig() {
                        return {
                            label: 'Delete',
                            onClick: deleteClickSpy,
                        };
                    }
                };
            case 'sulu_admin.copy':
                return class {
                    getToolbarItemConfig() {
                        return {
                            label: 'Copy',
                            onClick: copyClickSpy,
                        };
                    }
                };
            case 'sulu_admin.nothing':
                return class {
                    getToolbarItemConfig() {

                    }
                };
        }
    });

    const dropdownToolbarAction = createDropdownToolbarAction({
        icon: 'su-edit',
        label: 'edit',
        actions: [
            'sulu_admin.delete',
            'sulu_admin.copy',
            'sulu_admin.nothing',
        ],
    });

    expect(dropdownToolbarAction.getToolbarItemConfig()).toEqual({
        icon: 'su-edit',
        label: 'edit',
        options: [
            {
                label: 'Delete',
                onClick: deleteClickSpy,
            },
            {
                label: 'Copy',
                onClick: copyClickSpy,
            },
        ],
        type: 'dropdown',
    });
});

test('Return item config with options passed to child ToolbarActions', () => {
    formToolbarActionRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'sulu_admin.delete':
                return class {
                    options;

                    constructor(resourceFormStore, form, router, locales, options) {
                        this.options = options;
                    }

                    getToolbarItemConfig() {
                        return {
                            label: this.options.label,
                            onClick: jest.fn(),
                        };
                    }
                };
            case 'sulu_admin.copy':
                return class {
                    options;

                    constructor(resourceFormStore, form, router, locales, options) {
                        this.options = options;
                    }

                    getToolbarItemConfig() {
                        return {
                            label: this.options.title,
                            onClick: jest.fn(),
                        };
                    }
                };
        }
    });

    const dropdownToolbarAction = createDropdownToolbarAction({
        icon: 'su-edit',
        label: 'edit',
        actions: {
            'sulu_admin.delete': {
                label: 'Delete',
            },
            'sulu_admin.copy': {
                title: 'Copy',
            },
        },
    });

    expect(dropdownToolbarAction.getToolbarItemConfig()).toEqual({
        icon: 'su-edit',
        label: 'edit',
        options: [
            expect.objectContaining({
                label: 'Delete',
            }),
            expect.objectContaining({
                label: 'Copy',
            }),
        ],
        type: 'dropdown',
    });
});

test('Throw error if child ToolbarAction is a dropdown', () => {
    formToolbarActionRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'sulu_admin.delete':
                return class {
                    getToolbarItemConfig() {
                        return {
                            options: [],
                            type: 'dropdown',
                        };
                    }
                };
        }
    });

    const dropdownToolbarAction = createDropdownToolbarAction({
        icon: 'su-edit',
        label: 'edit',
        actions: [
            'sulu_admin.delete',
        ],
    });
    expect(() => dropdownToolbarAction.getToolbarItemConfig()).toThrow(/not being a dropdown/);
});

test('Throw error if child ToolbarAction has no onClick handler', () => {
    formToolbarActionRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'sulu_admin.delete':
                return class {
                    getToolbarItemConfig() {
                        return {
                            label: 'Test',
                        };
                    }
                };
        }
    });

    const dropdownToolbarAction = createDropdownToolbarAction({
        icon: 'su-edit',
        label: 'edit',
        actions: [
            'sulu_admin.delete',
        ],
    });
    expect(() => dropdownToolbarAction.getToolbarItemConfig()).toThrow(/onClick/);
});

test('Throw error if child Toolbaraction has no label', () => {
    formToolbarActionRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'sulu_admin.delete':
                return class {
                    getToolbarItemConfig() {
                        return {
                        };
                    }
                };
        }
    });

    const dropdownToolbarAction = createDropdownToolbarAction({
        icon: 'su-edit',
        label: 'edit',
        actions: [
            'sulu_admin.delete',
        ],
    });
    expect(() => dropdownToolbarAction.getToolbarItemConfig()).toThrow(/label/);
});

test('Return JSX for all child ToolbarActions', () => {
    formToolbarActionRegistry.get.mockImplementation((key) => {
        switch (key) {
            case 'sulu_admin.delete':
                return class {
                    getNode() {
                        return <div className="delete" key="delete" />;
                    }
                };
            case 'sulu_admin.copy':
                return class {
                    getNode() {
                        return <div className="copy" key="copy" />;
                    }
                };
            case 'sulu_admin.nothing':
                return class {
                    getNode() {}
                };
        }
    });

    const dropdownToolbarAction = createDropdownToolbarAction({
        icon: 'su-edit',
        label: 'edit',
        actions: [
            'sulu_admin.delete',
            'sulu_admin.copy',
            'sulu_admin.nothing',
        ],
    });

    expect(render(dropdownToolbarAction.getNode())).toMatchSnapshot();
});

test('Throw error if actions are neither an object nor an array', () => {
    expect(() => createDropdownToolbarAction({
        icon: 'su-edit',
        label: 'edit',
        actions: false,
    })).toThrow(/actions/);
});
