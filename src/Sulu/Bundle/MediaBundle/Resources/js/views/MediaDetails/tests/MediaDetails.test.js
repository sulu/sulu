/* eslint-disable flowtype/require-valid-file-annotation */
import 'url-search-params-polyfill';
import React from 'react';
import {observable} from 'mobx';
import {mount, render} from 'enzyme';
import {findWithHighOrderFunction} from 'sulu-admin-bundle/utils/TestHelper';

jest.mock('sulu-admin-bundle/containers', () => ({
    withToolbar: jest.fn((Component) => Component),
    Form: require.requireActual('sulu-admin-bundle/containers').Form,
    ResourceFormStore: require.requireActual('sulu-admin-bundle/containers').ResourceFormStore,
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
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
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

jest.mock('../../../stores/FormatStore', () => ({
    loadFormats: jest.fn().mockReturnValue(Promise.resolve([{key: 'test', scale: {}}])),
}));

beforeEach(() => {
    jest.resetModules();
});

test('Render a loading MediaDetails view', () => {
    const MediaDetails = require('../MediaDetails').default;
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

    expect(render(
        <MediaDetails resourceStore={resourceStore} router={router} />
    )).toMatchSnapshot();
});

test('Should change locale via locale chooser', () => {
    const MediaDetails = require('../MediaDetails').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaDetails);
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
    const mediaDetails = mount(<MediaDetails resourceStore={resourceStore} router={router} />).get(0);
    resourceStore.locale.set('de');

    const toolbarConfig = toolbarFunction.call(mediaDetails);
    toolbarConfig.locale.onChange('en');
    expect(resourceStore.locale.get()).toBe('en');
});

test('Should navigate to defined route on back button click', () => {
    const MediaDetails = require('../MediaDetails').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaDetails);
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
    const mediaDetails = mount(<MediaDetails resourceStore={resourceStore} router={router} />).get(0);
    resourceStore.setLocale('de');

    const toolbarConfig = toolbarFunction.call(mediaDetails);
    toolbarConfig.backButton.onClick();
    expect(router.restore).toBeCalledWith('sulu_media.overview', {locale: 'de'});
});

test('Should show locales from router options in toolbar', () => {
    const MediaDetails = require('../MediaDetails').default;
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaDetails);
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
    const mediaDetails = mount(<MediaDetails resourceStore={resourceStore} router={router} />).get(0);

    const toolbarConfig = toolbarFunction.call(mediaDetails);
    expect(toolbarConfig.locale.options).toEqual([
        {value: 'en', label: 'en'},
        {value: 'de', label: 'de'},
    ]);
});

test('Should call update method of MediaUploadStore if a file was dropped', (done) => {
    const testId = 1;
    const testFile = {name: 'test.jpg'};
    const MediaDetails = require('../MediaDetails').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    resourceStore.set('id', testId);
    resourceStore.loading = false;

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
    const mediaDetails = mount(<MediaDetails resourceStore={resourceStore} router={router} />);

    Promise.all([schemaTypesPromise, metadataPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            mediaDetails.update();
            mediaDetails.instance().mediaUploadStore.update.mockReturnValue(promise);
            mediaDetails.find('SingleMediaDropzone').prop('onDrop')(testFile);
            expect(mediaDetails.instance().mediaUploadStore.update).toHaveBeenCalledWith(testFile);
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should update resourceStore after SingleMediaUpload has completed upload', (done) => {
    const testFile = {name: 'test.jpg'};
    const MediaDetails = require('../MediaDetails').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('test', 1, {locale: observable.box()});
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
        navigate: jest.fn(),
        bind: jest.fn(),
        route: {
            options: {
                locales: [],
            },
        },
        attributes: {},
    };
    const mediaDetails = mount(<MediaDetails resourceStore={resourceStore} router={router} />);

    Promise.all([schemaTypesPromise, metadataPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            mediaDetails.update();
            mediaDetails.find('SingleMediaUpload').prop('onUploadComplete')(testFile);
            expect(resourceStore.data).toEqual(testFile);
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should initialize the ResourceStore with a schema', () => {
    const MediaDetails = require('../MediaDetails').default;
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
    mount(<MediaDetails resourceStore={resourceStore} router={router} />);

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
        return toolbarFunction.call(form).items.find((item) => item.label === 'sulu_admin.save');
    }

    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const MediaDetails = require('../MediaDetails').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaDetails);
    const resourceStore = new ResourceStore('snippets', 12, {locale: observable.box()});

    const router = {
        bind: jest.fn(),
        navigate: jest.fn(),
        route: {
            options: {},
        },
        attributes: {},
    };
    const form = mount(<MediaDetails resourceStore={resourceStore} router={router} />).get(0);

    expect(getSaveItem().disabled).toBe(true);

    resourceStore.dirty = true;
    expect(getSaveItem().disabled).toBe(false);
});

test('Should save form when submitted', (done) => {
    const ResourceRequester = require('sulu-admin-bundle/services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const MediaDetails = require('../MediaDetails').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');
    const Form = require('sulu-admin-bundle/containers').Form;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box()});
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaDetails);
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
    const mediaDetails = mount(<MediaDetails resourceStore={resourceStore} router={router} />);

    Promise.all([schemaTypesPromise, metadataPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            mediaDetails.update();
            expect(toolbarFunction.call(mediaDetails.instance()).showSuccess.get()).toEqual(false);

            mediaDetails.find(Form).instance().submit().then(() => {
                mediaDetails.update();
                expect(ResourceRequester.put).toBeCalledWith('media', {value: 'Value'}, {id: 4, locale: 'en'});
                expect(toolbarFunction.call(mediaDetails.instance()).showSuccess.get()).toEqual(true);
                done();
            });
        });
    });

    jsonSchemaResolve({});
});

test('Should destroy the store on unmount', () => {
    const MediaDetails = require('../MediaDetails').default;
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

    const mediaDetails = mount(<MediaDetails resourceStore={resourceStore} router={router} />);

    const formStore = mediaDetails.instance().formStore;
    formStore.destroy = jest.fn();

    mediaDetails.unmount();
    expect(formStore.destroy).toBeCalled();
});

test('Should open and close focus point overlay', (done) => {
    const ResourceRequester = require('sulu-admin-bundle/services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const MediaDetails = require('../MediaDetails').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box()});
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
    const mediaDetails = mount(<MediaDetails resourceStore={resourceStore} router={router} />);

    Promise.all([schemaTypesPromise, metadataPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            mediaDetails.update();
            expect(mediaDetails.find('FocusPointOverlay').prop('open')).toEqual(false);

            mediaDetails.find('Button[icon="su-focus"]').prop('onClick')();
            mediaDetails.update();
            expect(mediaDetails.find('FocusPointOverlay').prop('open')).toEqual(true);

            mediaDetails.find('FocusPointOverlay').prop('onClose')();
            mediaDetails.update();
            expect(mediaDetails.find('FocusPointOverlay').prop('open')).toEqual(false);
            done();
        });
    });

    jsonSchemaResolve({});
});

test('Should save focus point overlay', (done) => {
    const ResourceRequester = require('sulu-admin-bundle/services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const MediaDetails = require('../MediaDetails').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box()});
    const withToolbar = require('sulu-admin-bundle/containers').withToolbar;
    const toolbarFunction = findWithHighOrderFunction(withToolbar, MediaDetails);
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
    const mediaDetails = mount(<MediaDetails resourceStore={resourceStore} router={router} />);

    Promise.all([schemaTypesPromise, metadataPromise, jsonSchemaPromise]).then(() => {
        jsonSchemaPromise.then(() => {
            mediaDetails.update();
            mediaDetails.find('Button[icon="su-focus"]').prop('onClick')();

            mediaDetails.update();
            expect(mediaDetails.find('FocusPointOverlay').prop('open')).toEqual(true);

            mediaDetails.find('ImageFocusPoint').prop('onChange')({x: 0, y: 2});
            mediaDetails.find('FocusPointOverlay Overlay').prop('onConfirm')();

            expect(ResourceRequester.put).toBeCalledWith(
                'media',
                {focusPointX: 0, focusPointY: 2},
                {id: 4, locale: undefined}
            );

            setTimeout(() => {
                mediaDetails.update();
                expect(toolbarFunction.call(mediaDetails.instance()).showSuccess.get()).toEqual(true);
                expect(mediaDetails.find('FocusPointOverlay').prop('open')).toEqual(false);
                done();
            });
        });
    });

    jsonSchemaResolve({});
});

test('Should open and close crop overlay', (done) => {
    const ResourceRequester = require('sulu-admin-bundle/services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));
    const MediaDetail = require('../MediaDetail').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box()});
    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';

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
            expect(mediaDetail.find('CropOverlay').prop('open')).toEqual(false);
            expect(mediaDetail.find('CropOverlay').prop('image')).toEqual('image.jpg');

            mediaDetail.find('Button[icon="su-cut"]').prop('onClick')();
            mediaDetail.update();
            expect(mediaDetail.find('CropOverlay').prop('open')).toEqual(true);

            mediaDetail.find('CropOverlay').prop('onClose')();
            mediaDetail.update();
            expect(mediaDetail.find('CropOverlay').prop('open')).toEqual(false);
            done();
        });
    });

    jsonSchemaResolve({});
});
