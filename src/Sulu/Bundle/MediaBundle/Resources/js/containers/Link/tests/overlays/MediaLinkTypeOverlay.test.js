// @flow
import React from 'react';
import {render} from '@testing-library/react';
import getLatestMockProps from 'sulu-admin-bundle/utils/TestHelper/getLatestMockProps';
import MediaLinkTypeOverlay from '../../overlays/MediaLinkTypeOverlay';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

jest.mock('../../../SingleMediaSelection', () => jest.fn(() => null));

const singleMediaSelectionMock = ((jest.requireMock('../../../SingleMediaSelection'): any): {
    mock: {calls: Array<[Object]>},
    ...
});

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render overlay with minimal config', () => {
    const {baseElement} = render(
        <MediaLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={
                {
                    resourceKey: 'media',
                    displayProperties: ['title'],
                }
            }
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render overlay with invalid href type', () => {
    expect(() => render(
        <MediaLinkTypeOverlay
            href="1234"
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={
                {
                    resourceKey: 'media',
                    displayProperties: ['title'],
                }
            }
        />
    )).toThrow('The id of a media should always be a number!');
});

test('Render overlay with anchor enabled', () => {
    const {baseElement} = render(
        <MediaLinkTypeOverlay
            href={undefined}
            onAnchorChange={jest.fn()}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={
                {
                    resourceKey: 'media',
                    displayProperties: ['title'],
                }
            }
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render overlay with target enabled', () => {
    const {baseElement} = render(
        <MediaLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onTargetChange={jest.fn()}
            open={true}
            options={
                {
                    resourceKey: 'media',
                    displayProperties: ['title'],
                }
            }
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render overlay with title enabled', () => {
    const {baseElement} = render(
        <MediaLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    resourceKey: 'media',
                    displayProperties: ['title'],
                }
            }
        />
    );

    expect(baseElement).toMatchSnapshot();
});

test('Delegate only id to onHrefChange method', () => {
    const hrefChangeSpy = jest.fn();

    render(
        <MediaLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={hrefChangeSpy}
            onTitleChange={jest.fn()}
            open={true}
            options={
                {
                    resourceKey: 'media',
                    displayProperties: ['title'],
                }
            }
        />
    );

    getLatestMockProps(singleMediaSelectionMock).onChange({id: 1}, undefined);
    expect(hrefChangeSpy).toBeCalledWith(1, undefined);
});
