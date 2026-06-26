// @flow
import React from 'react';
import {render} from '@testing-library/react';
import {findElementByType, renderWithRef} from 'sulu-admin-bundle/utils/TestHelper';
import MediaLinkTypeOverlay from '../../overlays/MediaLinkTypeOverlay';

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../SingleMediaSelectionOverlay', () => jest.fn(function() {
    return <div>single media selection overlay</div>;
}));

test('Render overlay with minimal config', () => {
    const {instance: mediaLinkTypeOverlay} = renderWithRef(
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

    expect(render(findElementByType(mediaLinkTypeOverlay.render(), 'Form')).container).toMatchSnapshot();
});

test('Render overlay with invalid href type', () => {
    expect(() => renderWithRef(
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
    const {instance: mediaLinkTypeOverlay} = renderWithRef(
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

    expect(render(findElementByType(mediaLinkTypeOverlay.render(), 'Form')).container).toMatchSnapshot();
});

test('Render overlay with target enabled', () => {
    const {instance: mediaLinkTypeOverlay} = renderWithRef(
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

    expect(render(findElementByType(mediaLinkTypeOverlay.render(), 'Form')).container).toMatchSnapshot();
});

test('Render overlay with title enabled', () => {
    const {instance: mediaLinkTypeOverlay} = renderWithRef(
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

    expect(render(findElementByType(mediaLinkTypeOverlay.render(), 'Form')).container).toMatchSnapshot();
});

test('Delegate only id to onHrefChange method', () => {
    const hrefChangeSpy = jest.fn();

    const {instance: mediaLinkTypeOverlay} = renderWithRef(
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

    findElementByType(mediaLinkTypeOverlay.render(), 'SingleMediaSelection').props.onChange({id: 1}, undefined);
    expect(hrefChangeSpy).toHaveBeenCalledWith(1, undefined);
});
