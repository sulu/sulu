// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import LinkTypeOverlay from '../../overlays/LinkTypeOverlay';

const mockReact = require('react');

jest.mock('../../../../utils/Translator');

jest.mock('../../../SingleSelection', () => jest.fn((props) => mockReact.createElement(
    'button',
    {
        'aria-label': 'single-selection',
        onClick: () => props.onChange(12, {id: 12, title: 'Page'}),
        type: 'button',
    },
    props.value || props.emptyText
)));

const OPTIONS = {
    displayProperties: ['title'],
    emptyText: 'No page selected',
    icon: 'su-document',
    listAdapter: 'column_list',
    overlayTitle: 'Choose page',
    resourceKey: 'pages',
    targets: ['_blank', '_self', '_parent', '_top'],
};

function renderLinkTypeOverlay(props: Object = {}) {
    const defaultProps = {
        href: undefined,
        onCancel: jest.fn(),
        onConfirm: jest.fn(),
        onHrefChange: jest.fn(),
        open: true,
        options: OPTIONS,
    };
    const allProps = {...defaultProps, ...props};

    return {
        ...allProps,
        ...render(<LinkTypeOverlay {...allProps} />),
    };
}

function getInputForField(label: string): HTMLInputElement {
    const labelElement = screen.getByText(label);
    const input = labelElement.parentElement && labelElement.parentElement.querySelector('input');

    if (!(input instanceof HTMLInputElement)) {
        throw new Error('Input for "' + label + '" was not rendered.');
    }

    return input;
}

test('Render overlay with minimal config', async() => {
    const user = userEvent.setup();
    const hrefChangeSpy = jest.fn();

    renderLinkTypeOverlay({
        onHrefChange: hrefChangeSpy,
    });

    expect(screen.getByText('sulu_admin.link')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.link_url *')).toBeInTheDocument();
    expect(screen.getByRole('button', {name: 'single-selection'})).toHaveTextContent('No page selected');
    expect(screen.queryByText('sulu_admin.link_query')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.link_anchor')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.link_target *')).not.toBeInTheDocument();
    expect(screen.queryByText('sulu_admin.link_title')).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: 'single-selection'}));
    expect(hrefChangeSpy).toHaveBeenCalledWith(12, {id: 12, title: 'Page'});
});

test('Render overlay without options', () => {
    const consoleErrorSpy = jest.spyOn(console, 'error').mockImplementation(() => {});

    expect(() => render(
        <LinkTypeOverlay
            href={undefined}
            onCancel={jest.fn()}
            onConfirm={jest.fn()}
            onHrefChange={jest.fn()}
            open={true}
            options={undefined}
        />
    )).toThrow('The LinkTypeOverlay needs some options in order to work!');

    consoleErrorSpy.mockRestore();
});

test('Pass correct props to Dialog', async() => {
    const user = userEvent.setup();
    const cancelSpy = jest.fn();
    const confirmSpy = jest.fn();

    renderLinkTypeOverlay({
        href: 12,
        onCancel: cancelSpy,
        onConfirm: confirmSpy,
    });

    await user.click(screen.getByRole('button', {name: 'sulu_admin.confirm'}));
    await user.click(screen.getByRole('button', {name: 'sulu_admin.cancel'}));

    expect(confirmSpy).toHaveBeenCalledTimes(1);
    expect(cancelSpy).toHaveBeenCalledTimes(1);
});

test('Render overlay with query enabled', () => {
    renderLinkTypeOverlay({
        onQueryChange: jest.fn(),
        query: 'param=value',
    });

    expect(screen.getByText('sulu_admin.link_query')).toBeInTheDocument();
    expect(getInputForField('sulu_admin.link_query')).toHaveValue('param=value');
});

test('Render overlay with anchor enabled', () => {
    renderLinkTypeOverlay({
        anchor: 'section-1',
        onAnchorChange: jest.fn(),
    });

    expect(screen.getByText('sulu_admin.link_anchor')).toBeInTheDocument();
    expect(getInputForField('sulu_admin.link_anchor')).toHaveValue('section-1');
});

test('Render overlay with target enabled', async() => {
    const user = userEvent.setup();
    const targetChangeSpy = jest.fn();

    renderLinkTypeOverlay({
        onTargetChange: targetChangeSpy,
        target: '_self',
    });

    expect(screen.getByText('sulu_admin.link_target *')).toBeInTheDocument();
    expect(screen.getByText('sulu_admin.link_target_self')).toBeInTheDocument();

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('sulu_admin.link_target_parent'));

    expect(targetChangeSpy).toHaveBeenCalledWith('_parent');
});

test('Render overlay with title enabled', () => {
    renderLinkTypeOverlay({
        onTitleChange: jest.fn(),
        title: 'Page title',
    });

    expect(screen.getByText('sulu_admin.link_title')).toBeInTheDocument();
    expect(getInputForField('sulu_admin.link_title')).toHaveValue('Page title');
});
