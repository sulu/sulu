// @flow
import {render} from '@testing-library/react';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import MediaSelectionBlockPreviewTransformer
    from '../../blockPreviewTransformers/MediaSelectionBlockPreviewTransformer';

jest.mock('sulu-admin-bundle/services', () => ({
    ResourceRequester: {
        get: jest.fn(),
    },
}));

const MEDIA_URL = '/admin/media/redirect/media/:id';

const mockFetch = (status: number) => {
    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() =>
        Promise.resolve({
            status,
            json: () => Promise.resolve(),
        })
    );
};

beforeEach(() => {
    ResourceRequester.get.mockReset();
});

test('Render a single image if an id is given', () => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    mockFetch(200);

    expect(mediaSelectionBlockPreviewTransformer.transform({ids: [3, 7]})).toMatchSnapshot();
});

test('Render only eight images if more are given if an id is given', () => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    mockFetch(200);

    expect(mediaSelectionBlockPreviewTransformer.transform({ids: [3, 7, 9, 11, 13, 2, 1, 4, 5]})).toMatchSnapshot();
});

test('Render nothing if no id is given', () => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    expect(mediaSelectionBlockPreviewTransformer.transform({ids: []})).toMatchSnapshot();
});

test('Render nothing if a wrong type of value is given', () => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    expect(mediaSelectionBlockPreviewTransformer.transform('')).toMatchSnapshot();
});

test('Render MimeTypeIndicator if image isn\'t available', async() => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    mockFetch(404);
    ResourceRequester.get.mockImplementation((resourceKey, options) => {
        return Promise.resolve({id: options.id, mimeType: 'application/vnd.ms-excel'});
    });

    const {container, rerender} = render(mediaSelectionBlockPreviewTransformer.transform({ids: [1, 2, 3]}));
    await new Promise((resolve) => setTimeout(resolve));
    await new Promise((resolve) => setTimeout(resolve));
    rerender(mediaSelectionBlockPreviewTransformer.transform({ids: [1, 2, 3]}));

    //eslint-disable-next-line testing-library/no-container
    expect(container.querySelectorAll('.mimeTypeIndicator .mimeTypeIndicator')).toHaveLength(3);
    expect(ResourceRequester.get).toHaveBeenCalledWith('media', {id: 1, locale: 'en'});
    expect(ResourceRequester.get).toHaveBeenCalledWith('media', {id: 2, locale: 'en'});
    expect(ResourceRequester.get).toHaveBeenCalledWith('media', {id: 3, locale: 'en'});
});
