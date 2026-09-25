// @flow
import React from 'react';
import {act, render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import formatStore from '../../../stores/formatStore';
import MediaFormatStore from '../../../stores/MediaFormatStore';
import CropOverlay from '../CropOverlay';

let mockSelection = {};
let mockMediaFormatStoreInstances: Array<Object> = [];

jest.mock('sulu-admin-bundle/components', () => {
    const components = jest.requireActual('sulu-admin-bundle/components');
    const React = require('react');

    return {
        ...components,
        ImageRectangleSelection: jest.fn((props) => React.createElement(
            'div',
            {
                'data-min-height': props.minHeight,
                'data-min-width': props.minWidth,
                'data-testid': 'image-rectangle-selection',
            },
            React.createElement('span', {'data-testid': 'crop-value'}, JSON.stringify(props.value)),
            React.createElement(
                'button',
                {onClick: () => props.onChange(mockSelection), type: 'button'},
                'change crop'
            )
        )),
    };
});

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../stores/formatStore', () => ({
    loadFormats: jest.fn(),
}));

jest.mock('../../../stores/MediaFormatStore', () => jest.fn(function() {
    this.getFormatOptions = jest.fn();
    this.updateFormatOptions = jest.fn().mockReturnValue(Promise.resolve());
    this.loading = false;
    mockMediaFormatStoreInstances.push(this);
}));

const formats = [
    {
        key: 'test1',
        internal: true,
        title: 'Test 1',
    },
    {
        key: 'test2',
        scale: {x: 400, y: 500},
        title: 'Test 2',
    },
    {
        key: 'test3',
        scale: {x: 700, y: 300},
        title: 'Test 3',
    },
    {
        key: 'test4',
        scale: {x: 500, y: 300},
        title: 'Test 4',
    },
];

beforeEach(() => {
    jest.clearAllMocks();
    formatStore.loadFormats.mockReturnValue(Promise.resolve(formats));
    mockSelection = {};
    mockMediaFormatStoreInstances = [];
});

function renderCropOverlay(props: Object = {}) {
    return render(
        <CropOverlay
            id={7}
            image="test.jpg"
            locale="en"
            onClose={jest.fn()}
            onConfirm={jest.fn()}
            open={true}
            {...props}
        />
    );
}

function getMediaFormatStore() {
    return mockMediaFormatStoreInstances[mockMediaFormatStoreInstances.length - 1];
}

function getCropSelection() {
    return screen.getByTestId('image-rectangle-selection');
}

async function selectFormat(user, title: string) {
    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByRole('button', {name: title}));
}

test('Closing the overlay should call the onClose callback', async() => {
    const user = userEvent.setup();
    const closeSpy = jest.fn();

    renderCropOverlay({id: 4, locale: 'de', onClose: closeSpy});

    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(MediaFormatStore).toHaveBeenCalledWith(4, 'de');
    expect(closeSpy).toHaveBeenCalledWith();
});

test('Convert selection to format options with edge-based integer coordinates', async() => {
    const user = userEvent.setup();
    mockSelection = {
        left: 100.6,
        top: 74.63,
        width: 152.4,
        height: 125.37,
    };

    renderCropOverlay();
    const mediaFormatStore = getMediaFormatStore();

    expect(await screen.findByText('Test 2')).toBeInTheDocument();
    await user.click(screen.getByRole('button', {name: 'change crop'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.save'}));

    expect(mediaFormatStore.updateFormatOptions).toHaveBeenCalledWith({
        test2: {
            cropX: 100,
            cropY: 74,
            cropWidth: 153,
            cropHeight: 126,
        },
    });
});

test('Reset format croppings when closing overlay', async() => {
    const user = userEvent.setup();
    renderCropOverlay();
    const mediaFormatStore = getMediaFormatStore();
    mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => ({
        test2: {cropHeight: 30, cropWidth: 60, cropX: 100, cropY: 10},
        test3: {cropHeight: 20, cropWidth: 70, cropX: 10, cropY: 100},
    })[formatKey]);

    expect(await screen.findByText('Test 2 (sulu_media.cropped)')).toBeInTheDocument();
    const saveButton = screen.getByRole('button', {name: 'sulu_admin.save'});
    expect(saveButton).toBeDisabled();

    mockSelection = {height: 60, left: 200, top: 20, width: 20};
    await user.click(screen.getByRole('button', {name: 'change crop'}));
    expect(saveButton).toBeEnabled();

    await selectFormat(user, 'Test 3 (sulu_media.cropped)');
    expect(saveButton).toBeEnabled();

    await selectFormat(user, 'Test 4');
    await user.click(screen.getByRole('button', {name: 'su-times'}));

    expect(saveButton).toBeDisabled();
});

test('Select first non-internal image format and update crop dimensions for selected format', async() => {
    const user = userEvent.setup();
    renderCropOverlay();
    const mediaFormatStore = getMediaFormatStore();
    mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => ({
        test2: {cropHeight: 30, cropWidth: 60, cropX: 100, cropY: 10},
        test3: {cropHeight: 20, cropWidth: 70, cropX: 10, cropY: 100},
    })[formatKey]);

    expect(await screen.findByText('Test 2 (sulu_media.cropped)')).toBeInTheDocument();
    expect(getCropSelection()).toHaveAttribute('data-min-height', '500');
    expect(getCropSelection()).toHaveAttribute('data-min-width', '400');
    expect(screen.getByTestId('crop-value')).toHaveTextContent(
        JSON.stringify({left: 100, top: 10, width: 60, height: 30})
    );

    mockSelection = {height: 60, left: 200, top: 20, width: 20};
    await user.click(screen.getByRole('button', {name: 'change crop'}));
    expect(screen.getByRole('button', {name: 'sulu_admin.save'})).toBeEnabled();

    await selectFormat(user, 'Test 3 (sulu_media.cropped)');
    expect(getCropSelection()).toHaveAttribute('data-min-height', '300');
    expect(getCropSelection()).toHaveAttribute('data-min-width', '700');
    expect(screen.getByTestId('crop-value')).toHaveTextContent(
        JSON.stringify({left: 10, top: 100, width: 70, height: 20})
    );

    await selectFormat(user, 'Test 4');
    expect(getCropSelection()).toHaveAttribute('data-min-height', '300');
    expect(getCropSelection()).toHaveAttribute('data-min-width', '500');
    expect(screen.getByTestId('crop-value')).toBeEmptyDOMElement();
});

test('Save changes of formats', async() => {
    const user = userEvent.setup();
    const confirmSpy = jest.fn();
    renderCropOverlay({onConfirm: confirmSpy});
    const mediaFormatStore = getMediaFormatStore();
    mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => ({
        test2: {cropHeight: 30, cropWidth: 60, cropX: 100, cropY: 10},
    })[formatKey]);

    let resolveUpdate: () => void = () => {};
    const updatePromise = new Promise((resolve) => {
        resolveUpdate = resolve;
    });
    mediaFormatStore.updateFormatOptions.mockReturnValue(updatePromise);

    expect(await screen.findByText('Test 2 (sulu_media.cropped)')).toBeInTheDocument();
    mockSelection = {height: 60, left: 200, top: 20, width: 20};
    await user.click(screen.getByRole('button', {name: 'change crop'}));

    await selectFormat(user, 'Test 3');
    mockSelection = {height: 120, left: 100, top: 70, width: 30};
    await user.click(screen.getByRole('button', {name: 'change crop'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.save'}));

    expect(mediaFormatStore.updateFormatOptions).toHaveBeenCalledWith({
        test2: {cropHeight: 60, cropWidth: 20, cropX: 200, cropY: 20},
        test3: {cropHeight: 120, cropWidth: 30, cropX: 100, cropY: 70},
    });
    expect(confirmSpy).not.toHaveBeenCalled();

    await act(async() => {
        resolveUpdate();
        await updatePromise;
    });

    expect(confirmSpy).toHaveBeenCalledWith();
    expect(screen.getByRole('button', {name: 'sulu_admin.save'})).toBeDisabled();
});

test('Show which formats have already been cropped', async() => {
    const user = userEvent.setup();
    renderCropOverlay();
    const mediaFormatStore = getMediaFormatStore();
    mediaFormatStore.getFormatOptions.mockImplementation((formatKey) => (
        formatKey === 'test2' ? {cropHeight: 30, cropWidth: 60, cropX: 100, cropY: 10} : undefined
    ));

    expect(await screen.findByText('Test 2 (sulu_media.cropped)')).toBeInTheDocument();

    await user.click(screen.getByLabelText('su-angle-down'));
    expect(screen.getByRole('button', {name: /Test 2 \(sulu_media\.cropped\)$/})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Test 3'})).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'Test 4'})).toBeInTheDocument();
});
