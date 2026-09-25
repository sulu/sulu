// @flow
import React from 'react';
import {FormInspector, ResourceFormStore} from 'sulu-admin-bundle/containers';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {fieldTypeDefaultProps} from 'sulu-admin-bundle/utils/TestHelper';
import {observable} from 'mobx';
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

jest.mock('sulu-admin-bundle/stores/userStore', () => ({
    contentLocale: 'userContentLocale',
}));

afterEach(() => {
    jest.restoreAllMocks();
});

function getFileInput(container): HTMLInputElement {
    const input = container.querySelector('input[type="file"]');

    if (!(input instanceof HTMLInputElement)) {
        throw new Error('Expected file input');
    }

    return input;
}

function getMediaContainer(container) {
    const mediaContainer = container.querySelector('.mediaContainer');

    if (!mediaContainer) {
        throw new Error('Expected media container');
    }

    return mediaContainer;
}

function getImage(container): HTMLImageElement {
    const image = container.querySelector('img');

    if (!(image instanceof HTMLImageElement)) {
        throw new Error('Expected image');
    }

    return image;
}

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

    const {container} = render(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            disabled={true}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(screen.getByLabelText('su-icon')).toBeInTheDocument();
    expect(screen.getByText('Drag and drop')).toBeInTheDocument();
    expect(getMediaContainer(container)).toHaveClass('disabled');
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

    const {container} = render(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    expect(getMediaContainer(container)).toHaveClass('round');
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
        () => render(
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
        () => render(
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
        () => render(
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
        () => render(
            <SingleMediaUpload
                {...fieldTypeDefaultProps}
                formInspector={formInspector}
                schemaOptions={schemaOptions}
            />
        )
    ).toThrow('"collection_id"');
});

test('Call onChange and onFinish when upload has completed', async() => {
    const user = userEvent.setup();
    const formInspector = new FormInspector(
        new ResourceFormStore(
            new ResourceStore('test', undefined, {locale: observable.box('en')}),
            'test'
        )
    );
    const changeSpy = jest.fn();
    const finishSpy = jest.fn();
    const media = {name: 'test.jpg'};
    let uploadLocale;
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
    };
    const createSpy = jest.spyOn(MediaUploadStore.prototype, 'create').mockImplementation(function() {
        uploadLocale = this.locale.get();

        return Promise.resolve(media);
    });

    const {container} = render(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            onChange={changeSpy}
            onFinish={finishSpy}
            schemaOptions={schemaOptions}
        />
    );

    const file = new File(['test'], 'test.jpg', {type: 'image/jpeg'});
    await user.upload(getFileInput(container), file);

    expect(createSpy).toHaveBeenCalledWith(2, file);
    expect(uploadLocale).toEqual('en');
    await waitFor(() => expect(changeSpy).toHaveBeenCalledWith(media));
    expect(finishSpy).toHaveBeenCalledWith();
});

test('Create a MediaUploadStore with form locale when constructed', async() => {
    const user = userEvent.setup();
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
    let uploadLocale;
    jest.spyOn(MediaUploadStore.prototype, 'create').mockImplementation(function() {
        uploadLocale = this.locale.get();

        return Promise.resolve({});
    });

    const {container} = render(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    const file = new File(['test'], 'test.jpg', {type: 'image/jpeg'});
    await user.upload(getFileInput(container), file);

    expect(uploadLocale).toEqual('en');
});

test('Create MediaUploadStore with content-locale of user if locale is not present in form-inspector', async() => {
    const user = userEvent.setup();
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
    let uploadLocale;
    jest.spyOn(MediaUploadStore.prototype, 'create').mockImplementation(function() {
        uploadLocale = this.locale.get();

        return Promise.resolve({});
    });

    const {container} = render(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
        />
    );

    const file = new File(['test'], 'test.jpg', {type: 'image/jpeg'});
    await user.upload(getFileInput(container), file);

    expect(uploadLocale).toEqual('userContentLocale');
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
        thumbnails: {
            'sulu-400x400-inset': 'test-400.jpg',
        },
        url: '',
    };
    const schemaOptions = {
        collection_id: {
            name: 'collection_id',
            value: 2,
        },
        image_size: {
            name: 'image_size',
            value: 'sulu-400x400-inset',
        },
    };
    const {container} = render(
        <SingleMediaUpload
            {...fieldTypeDefaultProps}
            formInspector={formInspector}
            schemaOptions={schemaOptions}
            value={data}
        />
    );

    expect(getImage(container)).toHaveAttribute('src', 'test-400.jpg');
});
