// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {observable} from 'mobx';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import SingleMediaUpload from '../../fields/SingleMediaUpload';
import MediaUploadStore from '../../../../stores/MediaUploadStore';

jest.mock('sulu-admin-bundle/stores/ResourceStore', () => jest.fn(function(resourceKey, id, observableOptions) {
    this.locale = observableOptions.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/stores/ResourceFormStore', () => jest.fn(function(resourceStore) {
    this.locale = resourceStore.locale;
}));

jest.mock('sulu-admin-bundle/containers/Form/FormInspector', () => jest.fn(function(formStore) {
    this.locale = formStore.locale;
}));

jest.mock('../../../SingleMediaUpload', () => jest.fn(() => null));

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'userContentLocale',
}));

const SingleMediaUploadComponentMock: any = jest.requireMock('../../../SingleMediaUpload');

const createFormInspector = (locale: ?string = 'en') => new FormInspector(
    new ResourceFormStore(
        new ResourceStore('test', undefined, {locale: locale ? observable.box(locale) : undefined}),
        'test'
    )
);

const createProps = (overrides = {}) => ({
    ...fieldTypeDefaultProps,
    formInspector: createFormInspector(),
    schemaOptions: {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
    },
    ...overrides,
});

const renderSingleMediaUpload = (overrides = {}) => render(<SingleMediaUpload {...(createProps(overrides): any)} />);
const expectThrowSilently = (renderCallback, errorText) => {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});
    expect(renderCallback).toThrow(errorText);
    consoleErrorSpy.mockRestore();
};

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

    expect(getLatestMockProps(SingleMediaUploadComponentMock).collectionId).toEqual(3);
    expect(getLatestMockProps(SingleMediaUploadComponentMock).emptyIcon).toEqual('su-icon');
    expect(getLatestMockProps(SingleMediaUploadComponentMock).imageSize).toEqual('sulu-400x400-inset');
    expect(getLatestMockProps(SingleMediaUploadComponentMock).uploadText).toEqual('Drag and drop');
    expect(getLatestMockProps(SingleMediaUploadComponentMock).disabled).toEqual(true);
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

    renderSingleMediaUpload({schemaOptions});

    expect(getLatestMockProps(SingleMediaUploadComponentMock).skin).toEqual('round');
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

    expectThrowSilently(() => renderSingleMediaUpload({schemaOptions}), '"empty_icon"');
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

    expectThrowSilently(() => renderSingleMediaUpload({schemaOptions}), '"default" or "round"');
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

    expectThrowSilently(() => renderSingleMediaUpload({schemaOptions}), '"image_size"');
});

test('Throw if collectionId is not set', () => {
    expectThrowSilently(() => renderSingleMediaUpload({schemaOptions: {}}), '"collection_id"');
});

test('Call onChange and onFinish when upload has completed', () => {
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const media = {name: 'test.jpg'};

    renderSingleMediaUpload({
        onChange: changeSpy,
        onFinish: finishSpy,
    });

    getLatestMockProps(SingleMediaUploadComponentMock).onUploadComplete(media);

    expect(changeSpy).toBeCalledWith(media);
    expect(finishSpy).toBeCalledWith();
});

test('Create a MediaUploadStore when constructed', () => {
    renderSingleMediaUpload();

    const mediaUploadStore = getLatestMockProps(SingleMediaUploadComponentMock).mediaUploadStore;
    expect(mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(mediaUploadStore.locale.get()).toEqual('en');
    expect(mediaUploadStore.media).toEqual(undefined);
});

test('Create MediaUploadStore with content-locale of user if locale is not present in form-inspector', () => {
    renderSingleMediaUpload({
        formInspector: createFormInspector(null),
    });

    const mediaUploadStore = getLatestMockProps(SingleMediaUploadComponentMock).mediaUploadStore;
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

    renderSingleMediaUpload({value: data});

    const mediaUploadStore = getLatestMockProps(SingleMediaUploadComponentMock).mediaUploadStore;
    expect(mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(mediaUploadStore.media).toEqual(data);
});
