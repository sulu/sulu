// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import MediaLinkTypeOverlay from '../../overlays/MediaLinkTypeOverlay';

type Props = {|
    href?: ?string | number,
    onAnchorChange?: ?(anchor: ?string) => void,
    onTargetChange?: ?(target: string) => void,
    onTitleChange?: ?(title: ?string) => void,
|};

const mockReact = require('react');

jest.mock('sulu-admin-bundle/utils/Translator');

jest.mock('../../../SingleMediaSelectionOverlay', () => jest.fn((props) => {
    if (!props.open) {
        return null;
    }

    return mockReact.createElement(
        'button',
        {
            onClick: () => props.onConfirm({id: 1, mimeType: 'image/jpeg', thumbnails: {}, title: 'test media'}),
            type: 'button',
        },
        'select media'
    );
}));

function renderMediaLinkTypeOverlay(props?: Props) {
    return render(
        <MediaLinkTypeOverlay
            href={props ? props.href : undefined}
            onAnchorChange={props ? props.onAnchorChange : undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            onTargetChange={props ? props.onTargetChange : undefined}
            onTitleChange={props ? props.onTitleChange : undefined}
            open={true}
            options={
                {
                    resourceKey: 'media',
                    displayProperties: ['title'],
                }
            }
        />
    );
}

test('Render overlay with minimal config', () => {
    renderMediaLinkTypeOverlay();

    expect(screen.getByText('sulu_admin.link')).toBeInTheDocument();
    expect(screen.getByText(/sulu_admin\.link_url/)).toBeInTheDocument();
    expect(screen.queryByText(/sulu_admin\.link_anchor/)).not.toBeInTheDocument();
    expect(screen.queryByText(/sulu_admin\.link_target/)).not.toBeInTheDocument();
    expect(screen.queryByText(/sulu_admin\.link_title/)).not.toBeInTheDocument();
});

test('Render overlay with invalid href type', () => {
    expect(() => renderMediaLinkTypeOverlay({href: '1234'}))
        .toThrow('The id of a media should always be a number!');
});

test('Render overlay with anchor enabled', () => {
    renderMediaLinkTypeOverlay({onAnchorChange: jest.fn()});

    expect(screen.getByText(/sulu_admin\.link_url/)).toBeInTheDocument();
    expect(screen.getByText(/sulu_admin\.link_anchor/)).toBeInTheDocument();
});

test('Render overlay with target enabled', () => {
    renderMediaLinkTypeOverlay({onTargetChange: jest.fn()});

    expect(screen.getByText(/sulu_admin\.link_url/)).toBeInTheDocument();
    expect(screen.getByText(/sulu_admin\.link_target/)).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.please_choose')).toBeInTheDocument();
});

test('Render overlay with title enabled', () => {
    renderMediaLinkTypeOverlay({onTitleChange: jest.fn()});

    expect(screen.getByText(/sulu_admin\.link_url/)).toBeInTheDocument();
    expect(screen.getByText(/sulu_admin\.link_title/)).toBeInTheDocument();
    expect(screen.getByRole('textbox')).toBeInTheDocument();
});

test('Delegate only id to onHrefChange method', async() => {
    const user = userEvent.setup();
    const hrefChangeSpy = jest.fn();

    render(
        <MediaLinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={hrefChangeSpy}
            open={true}
            options={
                {
                    resourceKey: 'media',
                    displayProperties: ['title'],
                }
            }
        />
    );

    await user.click(screen.getByRole('button', {name: 'su-image'}));
    await user.click(screen.getByRole('button', {name: 'select media'}));

    expect(hrefChangeSpy).toHaveBeenCalledWith(1, expect.objectContaining({id: 1}));
});
