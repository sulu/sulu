// @flow
import {render} from '@testing-library/react';
import {ResourceRequester} from 'sulu-admin-bundle/services';
import SingleMediaSelectionBlockPreviewTransformer
    from '../../blockPreviewTransformers/SingleMediaSelectionBlockPreviewTransformer';

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
            json: () => Promise.resolve({}),
        })
    );
};

beforeEach(() => {
    ResourceRequester.get.mockReset();
});

test('Render a single image if an id is given', () => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    mockFetch(200);

    expect(singleMediaSelectionBlockPreviewTransformer.transform({id: 5})).toMatchSnapshot();
});

test('Render nothing if no id is given', () => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    mockFetch(200);

    expect(singleMediaSelectionBlockPreviewTransformer.transform({})).toMatchSnapshot();
});

test('Render nothing if a wrong type of value is given', () => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    mockFetch(200);

    expect(singleMediaSelectionBlockPreviewTransformer.transform('')).toMatchSnapshot();
});

test('Render MimeTypeIndicator if image isn\'t available', async() => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    mockFetch(404);
    ResourceRequester.get.mockResolvedValue({id: 123, mimeType: 'application/vnd.ms-excel'});

    const {container, rerender} = render(singleMediaSelectionBlockPreviewTransformer.transform({id: 123}));
    await new Promise((resolve) => setTimeout(resolve));
    await new Promise((resolve) => setTimeout(resolve));
    rerender(singleMediaSelectionBlockPreviewTransformer.transform({id: 123}));

    //eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.mimeTypeIndicator')).toMatchSnapshot();
    expect(ResourceRequester.get).toHaveBeenCalledWith('media', {id: 123, locale: 'en'});
});
