/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import React from 'react';
import {observable} from 'mobx';
import {mount} from 'enzyme';
import {findWithToolbarFunction} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component) => Component),
    Form: require.requireActual('sulu-admin-bundle/containers').Form,
    FormStore: require.requireActual('sulu-admin-bundle/containers').FormStore,
}));

jest.mock('sulu-admin-bundle/containers/Form/registries/FieldRegistry', () => ({
    get: jest.fn().mockReturnValue(function() {
        return null;
    }),
    getOptions: jest.fn().mockReturnValue({}),
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve([])),
}));

jest.mock('sulu-admin-bundle/services/Initializer', () => ({
    initializedTranslationsLocale: true,
}));

jest.mock('sulu-admin-bundle/utils', () => ({
    translate: function(key) {
        switch (key) {
            case 'sulu_admin.save':
                return 'Save';
            case 'sulu_media.upload_or_replace':
                return 'Upload or replace';
        }
    },
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    put: jest.fn(),
}));

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.id = 1;
    this.media = {};
    this.update = jest.fn();
    this.upload = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
}));

beforeEach(() => {
    jest.resetModules();
});

test('Render a loading MediaDetail view', () => {
    const MediaDetail = require('../MediaDetail').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
    };
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});
    resourceStore.loading = true;

    expect(mount(
        <MediaDetail resourceStore={resourceStore} router={router} />
    ).render()).toMatchSnapshot();
});

test('Should change locale via locale chooser', () => {
    const MediaDetail = require('../MediaDetail').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithToolbarFunction(withToolbar, MediaDetail);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                backRoute: 'test_route',
                locales: [],
            },
        },
    };
    const mediaDetail = mount(<MediaDetail resourceStore={resourceStore} router={router} />).get(0);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaDetail);
    toolbarConfig.locale.onChange('en');
    expect(resourceStore.locale.get()).toBe('en');
});

test('Should navigate to defined route on back button click', () => {
    const MediaDetail = require('../MediaDetail').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithToolbarFunction(withToolbar, MediaDetail);
    const resourceStore = new ResourceStore('media', '1', {locale: observable.box()});

    const router = {
        restore: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {},
    };
    const mediaDetail = mount(<MediaDetail resourceStore={resourceStore} router={router} />).get(0);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(mediaDetail);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('sulu_media.overview', {locale: 'de'});
});

test('Should show locales from router options in toolbar', () => {
    const MediaDetail = require('../MediaDetail').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithToolbarFunction(withToolbar, MediaDetail);
    const resourceStore = new ResourceStore('media', 1, {locale: observable.box()});

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: ['en', 'de'],
            },
        },
        attributes: {},
    };
    const mediaDetail = mount(<MediaDetail resourceStore={resourceStore} router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(mediaDetail);
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should call update method of MediaUploadStore if a file was dropped', (done) => {
    const testId = 1;
    const testFile = {name: 'test.jpg'};
    const MediaDetail = require('../MediaDetail').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    resourceStore.set('id', testId);

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    let jsonSchemaResolve;
    const jsonSchemaPromise = new Promise((resolve) => {
        jsonSchemaResolve = resolve;
    });

    const promise = Promise.resolve({name: 'test.jpg'});

    const router = {
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {},
    };
    const mediaDetail = mount(<MediaDetail resourceStore={resourceStore} router={router} />);

    Promise.all([schemaTypesPromise, metadataPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            mediaDetail.update();
            mediaDetail.instance().mediaUploadStore.update.mockReturnValue(promise);
            mediaDetail.find('SingleMediaDropzone').prop('onDrop')(testFile);
            expect(mediaDetail.instance().mediaUploadStore.update).toHaveBeenCalledWith(testFile);
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should initialize the ResourceStore with a schema', () => {
    const MediaDetail = require('../MediaDetail').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box()});
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');

    const router = {
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {
            id: 4,
        },
    };

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve({
        title: {},
        description: {},
    });
    metadataStore.getSchema.mockReturnValue(metadataPromise);
    mount(<MediaDetail resourceStore={resourceStore} router={router} />);

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        expect(resourceStore.resourceKey).toBe('media');
        expect(resourceStore.id).toBe(4);
        expect(resourceStore.data).toEqual({
            title: undefined,
            description: undefined,
        });
    });
});

test('Should render save button disabled only if form is not dirty', () => {
    function getSaveItem() {
        return toolbarFunction.call(form).items.find((item) => item.value === 'Save');
    }

    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaDetail = require('../MediaDetail').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithToolbarFunction(withToolbar, MediaDetail);
    const resourceStore = new ResourceStore('snippets', 12, {locale: observable.box()});

    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {},
        },
        attributes: {},
    };
    const form = mount(<MediaDetail resourceStore={resourceStore} router={router} />).get(0);

    expect(getSaveItem().disabled).toBe(true);

    resourceStore.dirty = true;
    expect(getSaveItem().disabled).toBe(false);
});

test('Should save form when submitted', (done) => {
    const ResourceRequester = require('sulu-admin-bundle/services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const MediaDetail = require('../MediaDetail').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');
    const Form = require('sulu-admin-bundle/containers').Form;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box()});
    resourceStore.locale.set('en');
    resourceStore.data = {value: 'Value'};
    resourceStore.loading = false;

    const schemaTypesPromise = Promise.resolve({});
    metadataStore.getSchemaTypes.mockReturnValue(schemaTypesPromise);

    const metadataPromise = Promise.resolve({});
    metadataStore.getSchema.mockReturnValue(metadataPromise);

    let jsonSchemaResolve;
    const jsonSchemaPromise = new Promise((resolve) => {
        jsonSchemaResolve = resolve;
    });

    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {
            id: 4,
        },
    };
    const mediaDetail = mount(<MediaDetail resourceStore={resourceStore} router={router} />);

    Promise.all([schemaTypesPromise, metadataPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            mediaDetail.update();
            mediaDetail.find(Form).instance().submit();
            expect(ResourceRequester.put).toBeCalledWith('media', 4, {value: 'Value'}, {locale: 'en'});
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should destroy the store on unmount', () => {
    const MediaDetail = require('../MediaDetail').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 12, {locale: observable.box()});
    const router = {
        bind: jest.fn(),
        route: {
            options: {
                resourceKey: 'snippets',
                locales: [],
            },
        },
        attributes: {},
    };

    const mediaDetail = mount(<MediaDetail resourceStore={resourceStore} router={router} />);

    const formStore = mediaDetail.instance().formStore;
    formStore.destroy = jest.fn();

    mediaDetail.unmount();
    expect(formStore.destroy).toBeCalled();
});
