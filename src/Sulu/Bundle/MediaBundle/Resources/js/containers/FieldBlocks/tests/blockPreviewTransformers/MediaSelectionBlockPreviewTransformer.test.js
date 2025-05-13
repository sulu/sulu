// @flow
import {render} from '@testing-library/react';
import {runInAction} from 'mobx';
import MediaSelectionBlockPreviewTransformer
    from '../../blockPreviewTransformers/MediaSelectionBlockPreviewTransformer';

const MEDIA_URL = '/admin/media/redirect/media/:id';

test('Render a single image if an id is given', () => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() =>
        Promise.resolve({
            json: () => Promise.resolve(),
        })
    );
    expect(mediaSelectionBlockPreviewTransformer.transform({ids: [3, 7]})).toMatchSnapshot();
});

test('Render only eight images if more are given if an id is given', () => {
    const mediaSelectionBlockPreviewTransformer = new MediaSelectionBlockPreviewTransformer(MEDIA_URL);
    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() =>
        Promise.resolve({
            json: () => Promise.resolve({status: 200}),
        })
    );
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
    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() =>
        Promise.resolve({
            ok: false,
            status: 404,
            json: () => Promise.resolve({}),
        })
    );

    const {container, rerender} = render(mediaSelectionBlockPreviewTransformer.transform({ids: [1, 2, 3]}));
    await new Promise((resolve) => setTimeout(resolve));
    runInAction(() => { });
    rerender(mediaSelectionBlockPreviewTransformer.transform({ids: [1, 2, 3]}));

    //eslint-disable-next-line testing-library/no-container
    expect(container.querySelectorAll('span')).toHaveLength(3);
});
