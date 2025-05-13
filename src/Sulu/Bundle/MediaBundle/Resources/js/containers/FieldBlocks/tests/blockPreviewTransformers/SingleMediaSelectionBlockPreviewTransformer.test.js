// @flow
import {render} from '@testing-library/react';
import {runInAction} from 'mobx';
import SingleMediaSelectionBlockPreviewTransformer
    from '../../blockPreviewTransformers/SingleMediaSelectionBlockPreviewTransformer';

const MEDIA_URL = '/admin/media/redirect/media/:id';

test('Render a single image if an id is given', () => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() =>
        Promise.resolve({
            json: () => Promise.resolve({}),
        })
    );
    expect(singleMediaSelectionBlockPreviewTransformer.transform({id: 5})).toMatchSnapshot();
});

test('Render nothing if no id is given', () => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() =>
        Promise.resolve({
            json: () => Promise.resolve({}),
        })
    );
    expect(singleMediaSelectionBlockPreviewTransformer.transform({})).toMatchSnapshot();
});

test('Render nothing if a wrong type of value is given', () => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() =>
        Promise.resolve({
            json: () => Promise.resolve({}),
        })
    );
    expect(singleMediaSelectionBlockPreviewTransformer.transform('')).toMatchSnapshot();
});

test('Render MimeTypeIndicator if image isn\'t available', async() => {
    const singleMediaSelectionBlockPreviewTransformer = new SingleMediaSelectionBlockPreviewTransformer(MEDIA_URL);
    // eslint-disable-next-line no-undef
    global.fetch = jest.fn(() =>
        Promise.resolve({
            ok: false,
            status: 404,
            json: () => Promise.resolve({}),
        })
    );

    const {container, rerender} = render(singleMediaSelectionBlockPreviewTransformer.transform({id: 123}));
    await new Promise((resolve) => setTimeout(resolve));
    runInAction(() => { });
    rerender(singleMediaSelectionBlockPreviewTransformer.transform({id: 123}));

    //eslint-disable-next-line testing-library/no-container
    expect(container.querySelector('.mimeTypeIndicator')).toMatchSnapshot();
});
