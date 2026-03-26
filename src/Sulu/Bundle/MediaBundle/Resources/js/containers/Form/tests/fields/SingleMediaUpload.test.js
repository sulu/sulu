// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {observable} from 'mobx';
import SingleMediaUpload from '../../fields/SingleMediaUpload';
import SingleMediaUploadComponent from '../../../SingleMediaUpload';
import MediaUploadStore from '../../../../stores/MediaUploadStore';

jest.mock('../../../SingleMediaUpload', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'userContentLocale',
}));

function getLatestSingleMediaUploadProps() {
    const calls = ((SingleMediaUploadComponent: any).mock.calls: any);
    return calls[calls.length - 1][0];
}

function createFormInspector(locale: any) {
    return new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale}),
            'test'
        )
    );
}

function renderSingleMediaUpload(props: Object = {}) {
    return render(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={createFormInspector(observable.box('en'))}
            schemaOptions={{
                collection_id: {
                    name: 'collection_id',
                    value: 2,
                },
            }}
            {...props}
        />
    );
}

function expectRenderToThrow(renderFn: () => void, expectedMessage: RegExp | string) {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    expect(renderFn).toThrow(expectedMessage);

    consoleErrorSpy.mockRestore();
}

beforeEach(() => {
    jest.clearAllMocks();
});

test('Pass correct props', () => {
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 3,
        },
        empty_icon: {
            name: 'empty_icon',
            value: 'su-icon',
        },
        image_size: {
            name: 'image_size',
            value: 'sulu-400x400-inset',
        },
        upload_text: {
            name: 'upload_text',
            infoText: 'Drag and drop',
        },
    };

    renderSingleMediaUpload({
        disabled: true,
        schemaOptions,
    });

    expect(getLatestSingleMediaUploadProps().collectionId).toEqual(3);
    expect(getLatestSingleMediaUploadProps().emptyIcon).toEqual('su-icon');
    expect(getLatestSingleMediaUploadProps().imageSize).toEqual('sulu-400x400-inset');
    expect(getLatestSingleMediaUploadProps().uploadText).toEqual('Drag and drop');
    expect(getLatestSingleMediaUploadProps().disabled).toEqual(true);
});

test('Pass correct skin to props', () => {
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
        skin: {
            name: 'skin',
            value: 'round',
        },
    };

    renderSingleMediaUpload({
        schemaOptions,
    });

    expect(getLatestSingleMediaUploadProps().skin).toEqual('round');
});

test('Throw if emptyIcon is set but not a valid value', () => {
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
        empty_icon: {
            name: 'empty_icon',
            value: [],
        },
    };

    expectRenderToThrow(
        () => renderSingleMediaUpload({
            schemaOptions,
        }),
        /"empty_icon"/
    );
});

test('Throw if skin is set but not a valid value', () => {
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
        skin: {
            name: 'skin',
            value: 'test',
        },
    };

    expectRenderToThrow(
        () => renderSingleMediaUpload({
            schemaOptions,
        }),
        /"default" or "round"/
    );
});

test('Throw if image_size is set but not a valid value', () => {
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
        image_size: {
            name: 'image_size',
            value: 3,
        },
    };

    expectRenderToThrow(
        () => renderSingleMediaUpload({
            schemaOptions,
        }),
        /"image_size"/
    );
});

test('Throw if collectionId is not set', () => {
    expectRenderToThrow(
        () => renderSingleMediaUpload({
            schemaOptions: {},
        }),
        /"collection_id"/
    );
});

test('Call onChange and onFinish when upload has completed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const media = {name: 'test.jpg'};
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
    };

    renderSingleMediaUpload({
        onChange: changeSpy,
        onFinish: finishSpy,
        schemaOptions,
    });

    getLatestSingleMediaUploadProps().onUploadComplete(media);

    expect(changeSpy).toBeCalledWith(media);
    expect(finishSpy).toBeCalledWith();
});

test('Create a MediaUploadStore when constructed', () => {
    renderSingleMediaUpload({
        schemaOptions: {
            collection_id: {
                name: 'collection_id',
                value: 2,
            },
        },
    });

    const mediaUploadStore = getLatestSingleMediaUploadProps().mediaUploadStore;
    expect(mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(mediaUploadStore.locale.get()).toEqual('en');
    expect(mediaUploadStore.media).toEqual(undefined);
});

test('Create MediaUploadStore with content-locale of user if locale is not present in form-inspector', () => {
    const formInspector = createFormInspector(undefined);

    render(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={{
                collection_id: {
                    name: 'collection_id',
                    value: 2,
                },
            }}
        />
    );

    const mediaUploadStore = getLatestSingleMediaUploadProps().mediaUploadStore;
    expect(mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(mediaUploadStore.locale.get()).toEqual('userContentLocale');
});

test('Create a MediaUploadStore when constructed with data', () => {
    const data = {
        adminUrl: '',
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
    };

    renderSingleMediaUpload({
        schemaOptions: {
            collection_id: {
                name: 'collection_id',
                value: 2,
            },
        },
        value: data,
    });

    const mediaUploadStore = getLatestSingleMediaUploadProps().mediaUploadStore;
    expect(mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(mediaUploadStore.media).toEqual(data);
});
