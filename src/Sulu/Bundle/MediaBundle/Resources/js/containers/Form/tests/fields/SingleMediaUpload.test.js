// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, FormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import SingleMediaUpload from '../../fields/SingleMediaUpload';
import SingleMediaUploadComponent from '../../../SingleMediaUpload';
import MediaUploadStore from '../../../../stores/MediaUploadStore';

jest.mock('sulu-admin-bundle/containers', () => ({
    FormInspector: jest.fn(),
    FormStore: jest.fn(),
}));

jest.mock('sulu-admin-bundle/stores', () => ({
    ResourceStore: jest.fn(),
}));

test('Pass correct props', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        collection_id: {
            value: 3,
        },
        empty_icon: {
            value: 'su-icon',
        },
        image_size: {
            value: 'sulu-400x400-inset',
        },
        upload_text: {
            infoText: 'Drag and drop',
        },
    };

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    expect(singleMediaUpload.prop('collectionId')).toEqual(3);
    expect(singleMediaUpload.prop('emptyIcon')).toEqual('su-icon');
    expect(singleMediaUpload.prop('imageSize')).toEqual('sulu-400x400-inset');
    expect(singleMediaUpload.prop('uploadText')).toEqual('Drag and drop');
});

test('Pass correct skin to props', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        collection_id: {
            value: 2,
        },
        skin: {
            value: 'round',
        },
    };

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    expect(singleMediaUpload.prop('skin')).toEqual('round');
});

test('Throw if emptyIcon is set but not a valid value', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        collection_id: {
            value: 2,
        },
        empty_icon: {
            value: [],
        },
    };

    expect(
        () => shallow(
            <SingleMediaUpload
                dataPath=""
                error={undefined}
                fieldTypeOptions={{}}
                maxOccurs={undefined}
                minOccurs={undefined}
                onChange={jest.fn()}
                onFinish={jest.fn()}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
                schemaPath=""
                showAllErrors={false}
                types={undefined}
                value={undefined}
            />
        )
    ).toThrow('"empty_icon"');
});

test('Throw if skin is set but not a valid value', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        collection_id: {
            value: 2,
        },
        skin: {
            value: 'test',
        },
    };

    expect(
        () => shallow(
            <SingleMediaUpload
                dataPath=""
                error={undefined}
                fieldTypeOptions={{}}
                maxOccurs={undefined}
                minOccurs={undefined}
                onChange={jest.fn()}
                onFinish={jest.fn()}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
                schemaPath=""
                showAllErrors={false}
                types={undefined}
                value={undefined}
            />
        )
    ).toThrow('"default" or "round"');
});

test('Throw if image_size is set but not a valid value', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        collection_id: {
            value: 2,
        },
        image_size: {
            value: 3,
        },
    };

    expect(
        () => shallow(
            <SingleMediaUpload
                dataPath=""
                error={undefined}
                fieldTypeOptions={{}}
                maxOccurs={undefined}
                minOccurs={undefined}
                onChange={jest.fn()}
                onFinish={jest.fn()}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
                schemaPath=""
                showAllErrors={false}
                types={undefined}
                value={undefined}
            />
        )
    ).toThrow('"image_size"');
});

test('Throw if collectionId is not set', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {};

    expect(
        () => shallow(
            <SingleMediaUpload
                dataPath=""
                error={undefined}
                fieldTypeOptions={{}}
                formInspector={formInspector}
                maxOccurs={undefined}
                minOccurs={undefined}
                onChange={jest.fn()}
                onFinish={jest.fn()}
                schemaOptions={schemaOptions}
                schemaPath=""
                showAllErrors={false}
                types={undefined}
                value={undefined}
            />
        )
    ).toThrow('"collection_id"');
});

test('Call onChange and onFinish when upload has completed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const media = {name: 'test.jpg'};
    const schemaOptions = {
        collection_id: {
            value: 2,
        },
    };

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    singleMediaUpload.find(SingleMediaUploadComponent).simulate('uploadComplete', media);

    expect(changeSpy).toBeCalledWith(media);
    expect(finishSpy).toBeCalledWith();
});

test('Create a MediaUploadStore when constructed', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const schemaOptions = {
        collection_id: {
            value: 2,
        },
    };
    const singleMediaUpload = shallow(
        <SingleMediaUpload
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={undefined}
        />
    );

    expect(singleMediaUpload.instance().mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(singleMediaUpload.instance().mediaUploadStore.media).toEqual(undefined);
});

test('Create a MediaUploadStore when constructed with data', () => {
    const formInspector = new FormInspector(new FormStore(new ResourceStore('test')));
    const data = {
        id: 1,
        mimeType: 'image/jpeg',
        thumbnails: {},
        url: '',
    };
    const schemaOptions = {
        collection_id: {
            value: 2,
        },
    };
    const singleMediaUpload = shallow(
        <SingleMediaUpload
            dataPath=""
            error={undefined}
            fieldTypeOptions={{}}
            formInspector={formInspector}
            maxOccurs={undefined}
            minOccurs={undefined}
            onChange={jest.fn()}
            onFinish={jest.fn()}
            schemaOptions={schemaOptions}
            schemaPath=""
            showAllErrors={false}
            types={undefined}
            value={data}
        />
    );

    expect(singleMediaUpload.instance().mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(singleMediaUpload.instance().mediaUploadStore.media).toEqual(data);
});
