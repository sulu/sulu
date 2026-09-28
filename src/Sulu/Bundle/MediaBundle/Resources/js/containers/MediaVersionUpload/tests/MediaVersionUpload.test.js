// @flow
import {observable} from 'mobx';
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import MediaVersionUpload from '../MediaVersionUpload';

let mockMediaUploadStoreInstances: Array<Object> = [];

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('sulu-admin-bundle/services/ResourceRequester', () => ({
    get: jest.fn().mockReturnValue(Promise.resolve({})),
    put: jest.fn().mockReturnValue(Promise.resolve({})),
}));

jest.mock('../../../stores/MediaUploadStore', () => jest.fn(function() {
    this.deletePreviewImage = jest.fn();
    this.id = 1;
    this.media = {};
    this.update = jest.fn().mockReturnValue(Promise.resolve({name: 'test.jpg'}));
    this.updatePreviewImage = jest.fn();
    this.upload = jest.fn();
    this.getThumbnail = jest.fn((size) => size);
    mockMediaUploadStoreInstances.push(this);
}));

jest.mock('../../../stores/formatStore', () => ({
    loadFormats: jest.fn().mockReturnValue(Promise.resolve([{key: 'test', scale: {}}])),
}));

jest.mock('../../../stores/MediaFormatStore', () => jest.fn(function() {
    this.getFormatOptions = jest.fn();
    this.updateFormatOptions = jest.fn();
    this.loading = false;
}));

jest.mock('../CropOverlay', () => jest.fn((props) => props.open
    ? (
        <div
            aria-label="crop-overlay"
            data-id={props.id}
            data-image={props.image}
            data-locale={props.locale}
            role="dialog"
        >
            <button onClick={props.onClose} type="button">close-crop-overlay</button>
            <button onClick={props.onConfirm} type="button">confirm-crop-overlay</button>
        </div>
    )
    : null
));

function getMediaUploadStore() {
    return mockMediaUploadStoreInstances[0];
}

beforeEach(() => {
    mockMediaUploadStoreInstances = [];
});

function getFileInput(container, index: number = 0): HTMLInputElement {
    const input = container.querySelectorAll('input[type="file"]')[index];

    if (!(input instanceof HTMLInputElement)) {
        throw new Error('Expected file input');
    }

    return input;
}

function getOverlay(title: string): HTMLElement {
    const titleElement = screen.getAllByText(title)
        .find((element) => element.tagName.toLowerCase() === 'h2');

    if (!titleElement) {
        throw new Error('Expected overlay title');
    }

    const overlay = titleElement.closest('.container');

    if (!(overlay instanceof HTMLElement)) {
        throw new Error('Expected overlay');
    }

    return overlay;
}

function queryOverlay(title: string): ?HTMLElement {
    const titleElement = screen.queryAllByText(title)
        .find((element) => element.tagName.toLowerCase() === 'h2');

    if (!titleElement) {
        return null;
    }

    const overlay = titleElement.closest('.container');

    return overlay instanceof HTMLElement ? overlay : null;
}

function expectOverlayClosed(title: string) {
    const overlay = queryOverlay(title);

    if (!overlay) {
        expect(overlay).toBeNull();
        return;
    }

    expect(overlay).not.toHaveClass('isDown');
}

test('Render a MediaVersionUpload field for images', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isImage = true;

    const {container} = render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={resourceStore}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MediaVersionUpload field for videos without assigned preview image', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isVideo = true;

    const {container} = render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={resourceStore}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Render a MediaVersionUpload field for videos', () => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;
    resourceStore.data.isVideo = true;
    resourceStore.data.previewImageId = 5;

    const {container} = render(
        <MediaVersionUpload
            onSuccess={jest.fn()}
            resourceStore={resourceStore}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Should update resourceStore and call onSuccess after SingleMediaUpload has completed upload', async() => {
    const successSpy = jest.fn();
    const testFile = new File(['test'], 'test.jpg', {type: 'image/jpeg'});
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    resourceStore.loading = false;

    const {container} = render(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    await user.upload(getFileInput(container), testFile);

    await waitFor(() => expect(resourceStore.data).toEqual({name: 'test.jpg'}));
    expect(successSpy).toHaveBeenCalled();
});

test('Should open and close crop overlay', async() => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const user = userEvent.setup();
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.isImage = true;

    render(<MediaVersionUpload
        onSuccess={undefined}
        resourceStore={resourceStore}
    />);

    expect(screen.queryByRole('dialog', {name: 'crop-overlay'})).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /sulu_media.define_crops/}));
    expect(screen.getByRole('dialog', {name: 'crop-overlay'})).toHaveAttribute('data-id', '4');
    expect(screen.getByRole('dialog', {name: 'crop-overlay'})).toHaveAttribute('data-image', 'image.jpg');
    expect(screen.getByRole('dialog', {name: 'crop-overlay'})).toHaveAttribute('data-locale', 'de');

    await user.click(screen.getByRole('button', {name: 'close-crop-overlay'}));
    expect(screen.queryByRole('dialog', {name: 'crop-overlay'})).not.toBeInTheDocument();
});

test('Should open and close focus point overlay', async() => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const user = userEvent.setup();
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.isImage = true;

    render(<MediaVersionUpload
        onSuccess={undefined}
        resourceStore={resourceStore}
    />);

    expectOverlayClosed('sulu_media.set_focus_point');

    await user.click(screen.getByRole('button', {name: /sulu_media.set_focus_point/}));
    expect(getOverlay('sulu_media.set_focus_point')).toHaveClass('isDown');

    await user.click(screen.getByRole('button', {name: 'su-times'}));
    expectOverlayClosed('sulu_media.set_focus_point');
});

test('Should close focus point overlay and call onSuccess when confirmed', async() => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    const user = userEvent.setup();
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.url = 'image.jpg';
    resourceStore.data.isImage = true;

    render(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    await user.click(screen.getByRole('button', {name: /sulu_media.set_focus_point/}));
    expect(getOverlay('sulu_media.set_focus_point')).toHaveClass('isDown');

    await user.click(screen.getByRole('button', {name: 'sulu_admin.save'}));

    await waitFor(() => expectOverlayClosed('sulu_media.set_focus_point'));
    expect(successSpy).toHaveBeenCalledWith();
});

test('Should close crop overlay and call onSuccess when confirmed', async() => {
    const resourceStore = new ResourceStore('media', 4, {locale: observable.box('de')});
    const successSpy = jest.fn();
    const user = userEvent.setup();
    resourceStore.loading = false;
    resourceStore.data.adminUrl = 'image.jpg';
    resourceStore.data.isImage = true;

    render(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    await user.click(screen.getByRole('button', {name: /sulu_media.define_crops/}));
    expect(screen.getByRole('dialog', {name: 'crop-overlay'})).toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'confirm-crop-overlay'}));

    expect(screen.queryByRole('dialog', {name: 'crop-overlay'})).not.toBeInTheDocument();
    expect(successSpy).toHaveBeenCalledWith();
});

test('Should call update method of MediaUploadStore if a file was dropped', async() => {
    const testId = 1;
    const testFile = new File(['test'], 'test.jpg', {type: 'image/jpeg'});
    const user = userEvent.setup();
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const {container} = render(<MediaVersionUpload
        onSuccess={undefined}
        resourceStore={resourceStore}
    />);

    await user.upload(getFileInput(container), testFile);

    expect(getMediaUploadStore().update).toHaveBeenCalledWith(testFile);
});

test('Should call updatePreviewImage method of MediaUploadStore if a new preview image is uploaded', async() => {
    const testId = 1;
    const testFile = new File(['test'], 'test.jpg', {type: 'image/jpeg'});
    const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
    const successSpy = jest.fn();
    const user = userEvent.setup();

    resourceStore.set('id', testId);
    resourceStore.loading = false;

    const {container} = render(<MediaVersionUpload
        onSuccess={successSpy}
        resourceStore={resourceStore}
    />);

    const updatePreviewPromise = Promise.resolve({name: 'test.jpg'});
    getMediaUploadStore().updatePreviewImage.mockReturnValue(updatePreviewPromise);
    await user.upload(getFileInput(container, 1), testFile);

    expect(getMediaUploadStore().updatePreviewImage).toHaveBeenCalledWith(testFile);

    await updatePreviewPromise;
    expect(successSpy).toHaveBeenCalledWith();
});

test(
    'Should call deletePreviewImage method of MediaUploadStore if the button to delete a preview is clicked',
    async() => {
        const testId = 1;
        const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
        const successSpy = jest.fn();
        const user = userEvent.setup();

        resourceStore.set('id', testId);
        resourceStore.loading = false;
        resourceStore.data.previewImageId = 5;

        render(<MediaVersionUpload
            onSuccess={successSpy}
            resourceStore={resourceStore}
        />);

        const deletePreviewPromise = Promise.resolve({name: 'test.jpg'});
        getMediaUploadStore().deletePreviewImage.mockReturnValue(deletePreviewPromise);
        await user.click(screen.getByRole('button', {name: /sulu_media.delete_preview_image/}));

        await user.click(screen.getByRole('button', {name: 'sulu_admin.ok'}));

        expect(getMediaUploadStore().deletePreviewImage).toHaveBeenCalledWith();

        await deletePreviewPromise;
        expect(successSpy).toHaveBeenCalledWith();
    }
);

test(
    'Should not call deletePreviewImage method of MediaUploadStore if the delete preview dialog is cancelled',
    async() => {
        const testId = 1;
        const resourceStore = new ResourceStore('test', testId, {locale: observable.box()});
        const successSpy = jest.fn();
        const user = userEvent.setup();

        resourceStore.set('id', testId);
        resourceStore.loading = false;
        resourceStore.data.previewImageId = 5;

        render(<MediaVersionUpload
            onSuccess={successSpy}
            resourceStore={resourceStore}
        />);

        await user.click(screen.getByRole('button', {name: /sulu_media.delete_preview_image/}));
        await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

        expect(getMediaUploadStore().deletePreviewImage).not.toHaveBeenCalled();
        expect(successSpy).not.toHaveBeenCalled();
    }
);
