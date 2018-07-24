/* eslint-disable flowtype/require-valid-file-annotation */
import React from 'react';
import {observable} from 'mobx';
import {mount} from 'enzyme';
import {findWithToolbarFunction} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/containers/Toolbar/withToolbar', () => jest.fn((Component) => {
    return Component;
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.delete':
                return 'Delete';
            case 'sulu_admin.save':
                return 'Save';
            case 'sulu_admin.save_draft':
                return 'Save as draft';
            case 'sulu_admin.save_publish':
                return 'Save and publish';
            default:
                throw Error(key);
        }
    },
}));

jest.mock('sulu-admin-bundle/containers/Form/registries/FieldRegistry', () => ({
    get: jest.fn().mockReturnValue(function() {
        return null;
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    put: jest.fn(),
    post: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
}));

beforeEach(() => {
    jest.resetModules();
});

test('Should navigate to defined route on back button click', () => {
    const withToolbar = require('sulu-admin-bundle/containers/Toolbar/withToolbar');
    const PageForm = require('../PageForm').default;
    const ResourceStore = require('sulu-admin-bundle/stores/ResourceStore').default;
    const toolbarFunction = findWithToolbarFunction(withToolbar, PageForm);
    const resourceStore = new ResourceStore('pages', 1, {locale: observable.box()});

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: true,
            },
        },
        attributes: {
            webspace: 'sulu',
        },
    };
    const form = mount(<PageForm locales={['en', 'de']} router={router} resourceStore={resourceStore} />);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(form.instance());
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('sulu_content.webspaces', {locale: 'de'});
});

test('Should change locale in form store via locale chooser', () => {
    const withToolbar = require('sulu-admin-bundle/containers/Toolbar/withToolbar');
    const PageForm = require('../PageForm').default;
    const ResourceStore = require('sulu-admin-bundle/stores/ResourceStore').default;
    const toolbarFunction = findWithToolbarFunction(withToolbar, PageForm);
    const resourceStore = new ResourceStore('pages', 1, {locale: observable.box()});

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: true,
            },
        },
        attributes: {
            webspace: 'sulu',
            locale: 'de',
        },
    };

    const pageForm = mount(<PageForm locales={['en', 'de']} router={router} resourceStore={resourceStore} />);
    pageForm.instance().formStore.locale.set('en');

    const toolbarConfig = toolbarFunction.call(pageForm.instance());
    expect(toolbarConfig.locale.value).toBe('en');
    expect(toolbarConfig.locale.options).toEqual(
        expect.arrayContaining(
            [
                expect.objectContaining({label: 'en', value: 'en'}),
                expect.objectContaining({label: 'de', value: 'de'}),
            ]
        )
    );
});

test('Should show loading templates chooser in toolbar while types are loading', () => {
    const withToolbar = require('sulu-admin-bundle/containers/Toolbar/withToolbar');
    const PageForm = require('../PageForm').default;
    const ResourceStore = require('sulu-admin-bundle/stores/ResourceStore').default;
    const toolbarFunction = findWithToolbarFunction(withToolbar, PageForm);
    const resourceStore = new ResourceStore('pages', 1, {locale: observable.box()});

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: true,
            },
        },
        attributes: {
            webspace: 'sulu',
            locale: 'de',
        },
    };

    const pageForm = mount(<PageForm router={router} resourceStore={resourceStore} />);

    const toolbarConfig = toolbarFunction.call(pageForm.instance());
    expect(toolbarConfig).toMatchSnapshot();
});

test('Should show templates chooser in toolbar if types are available', () => {
    const PageForm = require('../PageForm').default;
    const withToolbar = require('sulu-admin-bundle/containers/Toolbar/withToolbar');
    const ResourceStore = require('sulu-admin-bundle/stores/ResourceStore').default;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');

    const resourceStore = new ResourceStore('pages', 1, {locale: observable.box()});
    resourceStore.loading = false;
    resourceStore.locale.set('de');
    resourceStore.data.template = 'homepage';

    const toolbarFunction = findWithToolbarFunction(withToolbar, PageForm);

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: true,
            },
        },
        attributes: {
            webspace: 'sulu',
            locale: 'de',
        },
    };

    const typesPromise = Promise.resolve({
        sidebar: {key: 'sidebar', title: 'Sidebar'},
        footer: {key: 'footer', title: 'Footer'},
    });
    metadataStore.getSchemaTypes.mockReturnValue(typesPromise);

    const pageForm = mount(<PageForm locales={['en', 'de']} router={router} resourceStore={resourceStore} />);

    return typesPromise.then(() => {
        const toolbarConfig = toolbarFunction.call(pageForm.instance());
        expect(toolbarConfig).toMatchSnapshot();
    });
});

test('Should change template on click in template chooser', () => {
    const PageForm = require('../PageForm').default;
    const withToolbar = require('sulu-admin-bundle/containers/Toolbar/withToolbar');
    const ResourceStore = require('sulu-admin-bundle/stores/ResourceStore').default;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');

    const resourceStore = new ResourceStore('pages', 1, {locale: observable.box()});
    resourceStore.loading = false;
    resourceStore.locale.set('de');
    resourceStore.data.template = 'homepage';

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: true,
            },
        },
        attributes: {
            webspace: 'sulu',
            locale: 'de',
        },
    };

    const typesPromise = Promise.resolve({
        homepage: {key: 'homepage', title: 'Homepage'},
        default: {key: 'default', title: 'Default'},
    });
    metadataStore.getSchemaTypes.mockReturnValue(typesPromise);

    const homepageTemplatePromise = Promise.resolve({
        title: {
            label: 'Title',
            type: 'text_line',
        },
        description: {
            label: 'Description',
            type: 'text_line',
        },
    });
    const defaultTemplateMetadata = {
        title: {
            label: 'Title',
            type: 'text_line',
        },
    };
    const defaultTemplatePromise = Promise.resolve(defaultTemplateMetadata);
    metadataStore.getSchema.mockImplementation((resourceKey, type) => {
        switch (type) {
            case 'homepage':
                return homepageTemplatePromise;
            case 'default':
                return defaultTemplatePromise;
        }
    });
    let jsonSchemaResolve;
    const jsonSchemaPromise = new Promise((resolve) => {
        jsonSchemaResolve = resolve;
    });
    metadataStore.getJsonSchema.mockReturnValue(jsonSchemaPromise);

    const pageForm = mount(<PageForm locales={['en', 'de']} router={router} resourceStore={resourceStore} />);

    jsonSchemaResolve({});

    return Promise.all([
        typesPromise,
        homepageTemplatePromise,
        defaultTemplatePromise,
        jsonSchemaPromise]
    ).then(() => {
        return jsonSchemaPromise.then(() => {
            pageForm.update();
            expect(pageForm.find('Item')).toHaveLength(2);

            const toolbarOptions = findWithToolbarFunction(withToolbar, PageForm).call(pageForm.instance());
            toolbarOptions.items[1].onChange('default');
            const schemaPromise = Promise.resolve(defaultTemplateMetadata);
            metadataStore.getSchema.mockReturnValue(schemaPromise);

            return Promise.all([schemaPromise, jsonSchemaPromise]).then(() => {
                pageForm.update();
                expect(pageForm.find('Item')).toHaveLength(1);
            });
        });
    });
});

test('Should render save buttons disabled only if form is not dirty', () => {
    function getSaveItem(label) {
        const saveButtonDropdown = toolbarFunction.call(pageForm.instance())
            .items.find((item) => item.label === 'Save');
        return saveButtonDropdown.options.find((option) => option.label === label);
    }

    const withToolbar = require('sulu-admin-bundle/containers/Toolbar/withToolbar');
    const PageForm = require('../PageForm').default;
    const ResourceStore = require('sulu-admin-bundle/stores/ResourceStore').default;
    const toolbarFunction = findWithToolbarFunction(withToolbar, PageForm);
    const resourceStore = new ResourceStore('pages', 1, {locale: observable.box()});

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: true,
            },
        },
        attributes: {
            webspace: 'sulu',
            locale: 'de',
        },
    };

    const pageForm = mount(<PageForm locales={['en', 'de']} router={router} resourceStore={resourceStore} />);
    pageForm.instance().formStore.locale.set('en');

    expect(getSaveItem('Save as draft').disabled).toBe(true);
    expect(getSaveItem('Save and publish').disabled).toBe(true);

    resourceStore.dirty = true;
    expect(getSaveItem('Save as draft').disabled).toBe(false);
    expect(getSaveItem('Save and publish').disabled).toBe(false);
});

test('Should set showSuccess flag after form submission', (done) => {
    const PageForm = require('../PageForm').default;
    const ResourceStore = require('sulu-admin-bundle/stores/ResourceStore').default;
    const locale = observable.box();
    const resourceStore = new ResourceStore('pages', undefined, {locale: locale});
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const router = {
        navigate: jest.fn(),
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: true,
                editRoute: 'test_route',
            },
        },
        attributes: {
            webspace: 'sulu',
            locale: 'de',
            parentId: 'test-parent-id',
        },
    };

    const pageForm = mount(<PageForm locales={['en', 'de']} router={router} resourceStore={resourceStore} />);
    pageForm.instance().formStore.save = jest.fn().mockReturnValue(Promise.resolve({}));

    resourceStore.locale.set('de');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    pageForm.find('Form').at(0).instance().submit().then(() => {
        expect(pageForm.instance().showSuccess.get()).toEqual(true);
        done();
    });
});

test('Should keep errors after form submission has failed', (done) => {
    const PageForm = require('../PageForm').default;
    const ResourceStore = require('sulu-admin-bundle/stores/ResourceStore').default;
    const locale = observable.box();
    const resourceStore = new ResourceStore('pages', undefined, {locale: locale});
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const router = {
        navigate: jest.fn(),
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: true,
                editRoute: 'test_route',
            },
        },
        attributes: {
            webspace: 'sulu',
            locale: 'de',
            parentId: 'test-parent-id',
        },
    };

    const pageForm = mount(<PageForm locales={['en', 'de']} router={router} resourceStore={resourceStore} />);
    const error = {code: 100, message: 'Something went wrong'};
    const errorPromise = Promise.resolve(error);
    const formSavePromise = Promise.reject({json: jest.fn().mockReturnValue(errorPromise)});
    pageForm.instance().formStore.save = jest.fn().mockReturnValue(formSavePromise);

    resourceStore.locale.set('de');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    pageForm.find('Form').at(0).instance().submit().then(() => {
        expect(pageForm.instance().errors).toEqual([error]);
        done();
    });
});

test('Should save form when submitted and redirect to editRoute when creating a new page', () => {
    const PageForm = require('../PageForm').default;
    const ResourceStore = require('sulu-admin-bundle/stores/ResourceStore').default;
    const locale = observable.box();
    const resourceStore = new ResourceStore('pages', undefined, {locale: locale});
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const schemaPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(schemaPromise);

    const router = {
        navigate: jest.fn(),
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: true,
                editRoute: 'test_route',
            },
        },
        attributes: {
            webspace: 'sulu',
            locale: 'de',
            parentId: 'test-parent-id',
        },
    };

    const pageForm = mount(<PageForm locales={['en', 'de']} router={router} resourceStore={resourceStore} />);
    pageForm.instance().formStore.save = jest.fn();
    const savePromise = Promise.resolve({id: 'newId'});
    pageForm.instance().formStore.save.mockImplementation(() => {
        resourceStore.id = 'newId';
        return savePromise;
    });

    resourceStore.locale.set('de');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;
    resourceStore.destroy = jest.fn();

    expect(pageForm.instance().formStore.options).toEqual({
        parentId: 'test-parent-id',
        webspace: 'sulu',
    });

    return Promise.all([schemaTypesPromise, schemaPromise]).then(() => {
        pageForm.update();
        pageForm.find('Form').at(0).instance().props.onSubmit('publish');
        expect(resourceStore.destroy).toBeCalled();
        expect(pageForm.instance().formStore.save).toBeCalledWith(
            {
                action: 'publish',
            }
        );

        return savePromise.then(() => {
            pageForm.update();
            resourceStore.id = 'newId';
            expect(router.navigate).toBeCalled();
            expect(router.navigate.mock.calls[0][0]).toBe('test_route');
            const secondArgument = router.navigate.mock.calls[0][1];
            expect(secondArgument.locale).toBe(locale);
            expect(secondArgument.id).toBe('newId');
            expect(secondArgument.webspace).toBe('sulu');
        });
    });
});
