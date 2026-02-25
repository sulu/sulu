// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import BlockToolbar from '../BlockToolbar';
import {setTranslations} from '../../../utils/Translator/Translator';

setTranslations({
    'sulu_admin.%count%_selected': '{count} selected',
    'sulu_admin.select_all': 'Select all',
    'sulu_admin.deselect_all': 'Deselect all',
    'sulu_admin.cancel': 'Cancel',
}, 'en');

const createActions = (overrides: any = {}) => ([
    {
        label: 'Copy',
        icon: 'su-copy',
        handleClick: jest.fn(),
        ...overrides.copy,
    },
    {
        label: 'Duplicate',
        icon: 'su-duplicate',
        handleClick: jest.fn(),
        ...overrides.duplicate,
    },
    {
        label: 'Cut',
        icon: 'su-cut',
        handleClick: jest.fn(),
        ...overrides.cut,
    },
    {
        label: 'Delete',
        icon: 'su-trash-alt',
        handleClick: jest.fn(),
        ...overrides.delete,
    },
]);

test('Render a BlockToolbar', () => {
    const {container} = render(
        <BlockToolbar
            actions={createActions()}
            allSelected={true}
            onCancel={jest.fn()}
            onSelectAll={jest.fn()}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    expect(container).toMatchSnapshot();
});

test('Click cancel button', async() => {
    const clickSpy = jest.fn();

    render(
        <BlockToolbar
            actions={createActions()}
            allSelected={true}
            onCancel={clickSpy}
            onSelectAll={jest.fn()}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    await userEvent.click(screen.getByRole('button', {name: /Cancel/}));

    expect(clickSpy).toHaveBeenCalled();
});

test('Click action button', async() => {
    const clickSpy = jest.fn();

    render(
        <BlockToolbar
            actions={createActions({copy: {handleClick: clickSpy}})}
            allSelected={true}
            onCancel={jest.fn()}
            onSelectAll={jest.fn()}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    await userEvent.click(screen.getByRole('button', {name: 'Copy'}));

    expect(clickSpy).toHaveBeenCalled();
});

test('Click select all button', async() => {
    const clickSpy = jest.fn();

    render(
        <BlockToolbar
            actions={createActions()}
            allSelected={false}
            onCancel={jest.fn()}
            onSelectAll={clickSpy}
            onUnselectAll={jest.fn()}
            selectedCount={2}
        />
    );

    await userEvent.click(screen.getByRole('checkbox'));

    expect(clickSpy).toHaveBeenCalled();
});

test('Click un select all button', async() => {
    const clickSpy = jest.fn();

    render(
        <BlockToolbar
            actions={createActions()}
            allSelected={true}
            onCancel={jest.fn()}
            onSelectAll={jest.fn()}
            onUnselectAll={clickSpy}
            selectedCount={2}
        />
    );

    await userEvent.click(screen.getByRole('checkbox'));

    expect(clickSpy).toHaveBeenCalled();
});
