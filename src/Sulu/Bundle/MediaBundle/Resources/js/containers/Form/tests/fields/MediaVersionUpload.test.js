// @flow
import React from 'react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers/Form';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {observable} from 'mobx';
import {mount, render, shallow} from 'enzyme/build';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import MediaVersionUpload from '../../fields/MediaVersionUpload';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/MetadataStore', () => ({
    getSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getJsonSchema: jest.fn().mockReturnValue(Promise.resolve({})),
    getSchemaTypes: jest.fn().mockReturnValue(Promise.resolve([])),
}));

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue({
        then: jest.fn(),
    }),
    put: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('sulu-media-bundle/views/MediaDetails/CropOverlay', () => function CropOverlay() {
    return <div />;
});

test('Render a loading MediaVersionUpload field', () => {
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = true;
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );
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
    expect(render(
        <MediaVersionUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
        />
    )).toMatchSnapshot();
});

test('Render a non loading MediaVersionUpload field', () => {
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );
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
    expect(render(
        <MediaVersionUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            router={router}
        />
    )).toMatchSnapshot();
});

test('Should initialize the ResourceStore', () => {
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const metadataStore = require('sulu-admin-bundle/containers/Form/stores/MetadataStore');

    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('media', 4, {locale: observable.box('en')}),
            'test'
        )
    );
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
    shallow(<MediaVersionUpload {...fieldTypeDefaultProps} formInspector={formInspector} router={router} />);

    return Promise.all([schemaTypesPromise, metadataPromise]).then(() => {
        if (!formInspector.formStore.resourceStore){
            throw new Error();
        }
        const resourceStore = (formInspector.formStore.resourceStore: ResourceStore);
        expect(resourceStore.resourceKey).toBe('media');
        expect(resourceStore.id).toBe(4);
        expect(resourceStore.data).toEqual({
            title: undefined,
            description: undefined,
        });
    });
});

test('Should update resourceStore after SingleMediaUpload has completed upload', (done) => {
    const testFile = {name: 'test.jpg'};
    const MediaVersionUpload = require('../../fields/MediaVersionUpload').default;
    const ResourceStore = require('sulu-admin-bundle/stores').ResourceStore;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );
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
    const mediaVersionUpload = mount(<MediaVersionUpload
        {...fieldTypeDefaultProps}
        formInspector={formInspector}
        router={router}
    />);

    mediaVersionUpload.update();
    mediaVersionUpload.find('SingleMediaUpload').prop('onUploadComplete')(testFile);
    expect(resourceStore.data).toEqual(testFile);
    done();
});

test('Should open and close crop overlay', () => {
    jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
        put: jest.fn().mockReturnValue(Promise.resolve({})),
    }));
    const MediaVersionUpload = require('../../fields/MediaVersionUpload').default;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';

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

    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );

    const mediaVersionUpload = mount(<MediaVersionUpload
        {...fieldTypeDefaultProps}
        formInspector={formInspector}
        router={router}
    />);

    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('CropOverlay').prop('open')).toEqual(false);
    expect(mediaVersionUpload.find('CropOverlay').prop('image')).toEqual('image.jpg');
    expect(mediaVersionUpload.find('CropOverlay').prop('id')).toEqual(4);
    expect(mediaVersionUpload.find('CropOverlay').prop('locale')).toEqual('de');

    mediaVersionUpload.find('Button[icon="su-cut"]').prop('onClick')();
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('CropOverlay').prop('open')).toEqual(true);

    mediaVersionUpload.find('CropOverlay').prop('onClose')();
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('CropOverlay').prop('open')).toEqual(false);
});

test('Should open and close focus point overlay', () => {
    jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
        put: jest.fn().mockReturnValue(Promise.resolve({})),
    }));

    const MediaVersionUpload = require('../../fields/MediaVersionUpload').default;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});

    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';

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
    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );

    const mediaVersionUpload = mount(<MediaVersionUpload
        {...fieldTypeDefaultProps}
        formInspector={formInspector}
        router={router}
    />);
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(false);

    mediaVersionUpload.find('Button[icon="su-focus"]').prop('onClick')();
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(true);

    mediaVersionUpload.find('FocusPointOverlay').prop('onClose')();
    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(false);
});

test('Should save focus point overlay', (done) => {
    const ResourceRequester = require('sulu-admin-bundle/services/ResourceRequester');
    ResourceRequester.put.mockReturnValue(Promise.resolve({}));

    const MediaVersionUpload = require('../../fields/MediaVersionUpload').default;
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});

    resourceStore.loading = false;
    resourceStore.data.url = 'image.jpg';

    const formInspector = new FormInspector(
        new ResourceFormStore(
            resourceStore, 'test'
        )
    );

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
    const mediaVersionUpload = mount(<MediaVersionUpload
        {...fieldTypeDefaultProps}
        formInspector={formInspector}
        router={router}
    />);

    mediaVersionUpload.update();
    mediaVersionUpload.find('Button[icon="su-focus"]').prop('onClick')();

    mediaVersionUpload.update();
    expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(true);

    mediaVersionUpload.find('ImageFocusPoint').prop('onChange')({x: 0, y: 2});
    mediaVersionUpload.find('FocusPointOverlay Overlay').prop('onConfirm')();

    expect(ResourceRequester.put).toBeCalledWith(
        'media',
        {focusPointX: 0, focusPointY: 2, url: 'image.jpg'},
        {id: 4, locale: 'de'}
    );
    setTimeout(() => {
        mediaVersionUpload.update();
        expect(mediaVersionUpload.find('FocusPointOverlay').prop('open')).toEqual(false);
        done();
    });
});
