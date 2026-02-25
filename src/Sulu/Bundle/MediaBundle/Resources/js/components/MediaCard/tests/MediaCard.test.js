// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MediaCard from '../MediaCard';

let originalImage: any;
let mockImageInstances: Array<any> = [];

beforeAll(() => {
    originalImage = window.Image;
});

beforeEach(() => {
    jest.clearAllMocks();
    mockImageInstances = [];

    window.Image = class {
        onload: Function;
        onerror: Function;
        _src: string;

        constructor() {
            this.onload = () => undefined;
            this.onerror = () => undefined;
            this._src = '';
            mockImageInstances.push((this: any));
        }

        set src(value: string) {
            this._src = value;
        }

        get src() {
            return this._src;
        }
    };
});

afterAll(() => {
    window.Image = originalImage;
});

test('Render a MediaCard component', () => {
    const {asFragment} = render(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id="test"
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
            title="Test"
        />
    );

    mockImageInstances[0].onload();

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard component with ghostLocale', () => {
    const {asFragment} = render(
        <MediaCard
            downloadText=""
            downloadUrl=""
            ghostLocale="en"
            id="test"
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
            title="Test"
        />
    );

    mockImageInstances[0].onload();

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard component with loader if image has not been loaded yet', () => {
    const {asFragment} = render(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id="test"
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
            title="Test"
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard component with MimeTypeIndicator if an error appeared while loading the image', () => {
    const {asFragment} = render(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id="test"
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
            title="Test"
        />
    );

    mockImageInstances[0].onerror();

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard component with a checkbox for selection', () => {
    const {asFragment} = render(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id="test"
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
            onSelectionChange={jest.fn()}
            title="Test"
        />
    );

    mockImageInstances[0].onload();

    expect(asFragment()).toMatchSnapshot();
});

test('Render a MediaCard with download list', async() => {
    const imageSizes = [
        {
            url: 'http://lorempixel.com/300/200',
            label: '300/200',
        },
        {
            url: 'http://lorempixel.com/600/300',
            label: '600/300',
        },
        {
            url: 'http://lorempixel.com/150/200',
            label: '150/200',
        },
    ];

    render(
        <MediaCard
            downloadCopyText="Copy URL"
            downloadText="Direct download"
            downloadUrl="http://lorempixel.com/300/200"
            id="test"
            image="http://lorempixel.com/300/200"
            imageSizes={imageSizes}
            meta="Test/Test"
            mimeType="image/jpeg"
            title="Test"
        />
    );

    await userEvent.click(screen.getByRole('button', {name: 'su-download'}));

    expect(screen.getByRole('list')).toBeInTheDocument();
    expect(screen.getByText('Direct download')).toBeInTheDocument();
    expect(screen.getByText('300/200')).toBeInTheDocument();
    expect(screen.getByText('600/300')).toBeInTheDocument();
    expect(screen.getByText('150/200')).toBeInTheDocument();
});

test('Clicking on an item should call the responsible handler on the MediaCard component', async() => {
    const clickSpy = jest.fn();
    const selectionSpy = jest.fn();
    const itemId = 'test';

    render(
        <MediaCard
            downloadText=""
            downloadUrl=""
            id={itemId}
            image="http://lorempixel.com/300/200"
            meta="Test/Test"
            mimeType="image/jpeg"
            onClick={clickSpy}
            onSelectionChange={selectionSpy}
            title="Test"
        />
    );

    const actionButtons = screen.getAllByRole('button').filter(
        (button) => button.getAttribute('tabindex') === '0'
    );
    const mediaButton = actionButtons.find((button) => button.className.includes('media'));
    const descriptionButton = actionButtons.find((button) => button.className.includes('description'));

    if (!mediaButton || !descriptionButton) {
        throw new Error('Could not find expected media and description buttons');
    }

    await userEvent.click(mediaButton);
    expect(clickSpy).toHaveBeenCalledWith(itemId, true);

    await userEvent.click(descriptionButton);
    expect(selectionSpy).toHaveBeenCalledWith(itemId, true);
});
