// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import BlockToolbar from '../BlockToolbar';
import {setTranslations} from '../../../utils/Translator/Translator';

setTranslations({
    'sulu_admin.%count%_selected': '{count} selected',
    'sulu_admin.select_all': 'Select all',
    'sulu_admin.deselect_all': 'Deselect all',
    'sulu_admin.cancel': 'Cancel',
}, 'en');

const getActions = (copyHandler: () => void = jest.fn()) => [
    {
        label: 'Copy',
        icon: 'su-copy',
        handleClick: copyHandler,
    },
    {
        label: 'Duplicate',
        icon: 'su-duplicate',
        handleClick: jest.fn(),
    },
    {
        label: 'Cut',
        icon: 'su-cut',
        handleClick: jest.fn(),
    },
    {
        label: 'Delete',
        icon: 'su-trash-alt',
        handleClick: jest.fn(),
    },
];

test('Render a Breadcrumb', () => {
    const {asFragment} = render(
        <BlockToolbar
            actions={getActions()}
            allSelected={true}
            onCancel={jest.fn()}
            onSelectAll={jest.fn()}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    expect(asFragment()).toMatchSnapshot();
});

test('Click cancel button', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();

    render(
        <BlockToolbar
            actions={getActions()}
            allSelected={true}
            onCancel={clickSpy}
            onSelectAll={jest.fn()}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    await user.click(screen.getByText('Cancel'));

    expect(clickSpy).toHaveBeenCalled();
});

test('Click action button', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();

    render(
        <BlockToolbar
            actions={getActions(clickSpy)}
            allSelected={true}
            onCancel={jest.fn()}
            onSelectAll={jest.fn()}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    await user.click(screen.getByRole('button', {name: 'Copy'}));

    expect(clickSpy).toHaveBeenCalled();
});

test('Click select all button', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();

    render(
        <BlockToolbar
            actions={getActions()}
            allSelected={false}
            onCancel={jest.fn()}
            onSelectAll={clickSpy}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    await user.click(screen.getByRole('checkbox'));

    expect(clickSpy).toHaveBeenCalled();
});

test('Click un select all button', async() => {
    const user = userEvent.setup();
    const clickSpy = jest.fn();

    render(
        <BlockToolbar
            actions={getActions()}
            allSelected={true}
            onCancel={jest.fn()}
            onSelectAll={jest.fn()}
            onUnselectAll={clickSpy}
            selectedCount={2}
        />
    );

    await user.click(screen.getByRole('checkbox'));

    expect(clickSpy).toHaveBeenCalled();
});
