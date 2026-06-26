// @flow
import React from 'react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps, findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import {observable} from 'mobx';
import SingleMediaUpload from '../../fields/SingleMediaUpload';
import SingleMediaUploadComponent from '../../../SingleMediaUpload';
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

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'userContentLocale',
}));

test('Pass correct props', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
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

    const {instance: singleMediaUpload} = renderWithRef(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );
    const singleMediaUploadProps = findElementByType(singleMediaUpload.render(), SingleMediaUploadComponent).props;

    expect(singleMediaUploadProps.collectionId).toEqual(3);
    expect(singleMediaUploadProps.emptyIcon).toEqual('su-icon');
    expect(singleMediaUploadProps.imageSize).toEqual('sulu-400x400-inset');
    expect(singleMediaUploadProps.uploadText).toEqual('Drag and drop');
    expect(singleMediaUploadProps.disabled).toEqual(true);
});

test('Pass correct skin to props', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
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

    const {instance: singleMediaUpload} = renderWithRef(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(findElementByType(singleMediaUpload.render(), SingleMediaUploadComponent).props.skin).toEqual('round');
});

test('Throw if emptyIcon is set but not a valid value', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
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

    expect(
        () => renderWithRef(
            <SingleMediaUpload
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
            />
        )
    ).toThrow('"empty_icon"');
});

test('Throw if skin is set but not a valid value', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
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

    expect(
        () => renderWithRef(
            <SingleMediaUpload
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
            />
        )
    ).toThrow('"default" or "round"');
});

test('Throw if image_size is set but not a valid value', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
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

    expect(
        () => renderWithRef(
            <SingleMediaUpload
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
            />
        )
    ).toThrow('"image_size"');
});

test('Throw if collectionId is not set', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    const schemaOptions = {};

    expect(
        () => renderWithRef(
            <SingleMediaUpload
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
            />
        )
    ).toThrow('"collection_id"');
});

test('Call onChange and onFinish when upload has completed', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const media = {name: 'test.jpg'};
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
    };

    const {instance: singleMediaUpload} = renderWithRef(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    findElementByType(singleMediaUpload.render(), SingleMediaUploadComponent).props.onUploadComplete(media);

    expect(changeSpy).toHaveBeenCalledWith(media);
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Create a MediaUploadStore when constructed', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
    };
    const {instance: singleMediaUpload} = renderWithRef(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleMediaUpload.mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(singleMediaUpload.mediaUploadStore.locale.get()).toEqual('en');
    expect(singleMediaUpload.mediaUploadStore.media).toEqual(undefined);
});

test('Create MediaUploadStore with content-locale of user if locale is not present in form-inspector', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {}),
            'test'
        )
    );
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
    };
    const {instance: singleMediaUpload} = renderWithRef(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleMediaUpload.mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(singleMediaUpload.mediaUploadStore.locale.get()).toEqual('userContentLocale');
});

test('Create a MediaUploadStore when constructed with data', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    const data = {
        adminUrl: '',
        id: 1,
        locale: 'en',
        mimeType: 'image/jpeg',
        title: 'test',
        thumbnails: {},
        url: '',
    };
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
    };
    const {instance: singleMediaUpload} = renderWithRef(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={data}
        />
    );

    expect(singleMediaUpload.mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(singleMediaUpload.mediaUploadStore.media).toEqual(data);
});
