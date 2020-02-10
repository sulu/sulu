// @flow
import React from 'react';
import {shallow} from 'enzyme';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
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

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleMediaUpload.prop('collectionId')).toEqual(3);
    expect(singleMediaUpload.prop('emptyIcon')).toEqual('su-icon');
    expect(singleMediaUpload.prop('imageSize')).toEqual('sulu-400x400-inset');
    expect(singleMediaUpload.prop('uploadText')).toEqual('Drag and drop');
    expect(singleMediaUpload.prop('disabled')).toEqual(true);
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

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleMediaUpload.prop('skin')).toEqual('round');
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
        () => shallow(
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
        () => shallow(
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
        () => shallow(
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
        () => shallow(
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

    const singleMediaUpload = shallow(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    singleMediaUpload.find(SingleMediaUploadComponent).simulate('uploadComplete', media);

    expect(changeSpy).toBeCalledWith(media);
    expect(finishSpy).toBeCalledWith();
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
    const singleMediaUpload = shallow(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleMediaUpload.instance().mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(singleMediaUpload.instance().mediaUploadStore.locale.get()).toEqual('en');
    expect(singleMediaUpload.instance().mediaUploadStore.media).toEqual(undefined);
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
    const singleMediaUpload = shallow(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(singleMediaUpload.instance().mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(singleMediaUpload.instance().mediaUploadStore.locale.get()).toEqual('userContentLocale');
});

test('Create a MediaUploadStore when constructed with data', () => {
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    const data = {
        id: 1,
        title: 'test title',
        mimeType: 'image/jpeg',
        thumbnails: {},
        url: '',
    };
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
    };
    const singleMediaUpload = shallow(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={data}
        />
    );

    expect(singleMediaUpload.instance().mediaUploadStore).toBeInstanceOf(MediaUploadStore);
    expect(singleMediaUpload.instance().mediaUploadStore.media).toEqual(data);
});
