// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import listAdapterDefaultProps from '../../../../utils/TestHelper/listAdapterDefaultProps';
import FolderAdapter from '../../adapters/FolderAdapter';

jest.mock('../../../../utils/Translator', () => ({
    translate(key) {
        switch (key) {
            case 'sulu_admin.object':
                return 'Object';
            case 'sulu_admin.objects':
                return 'Objects';
            default:
                return key;
        }
    },
}));

const DATA = [
    {
        id: 1,
        title: 'Title 1',
        objectCount: 1,
        description: 'Description 1',
    },
    {
        id: 2,
        title: 'Title 2',
        objectCount: 7,
        description: 'Description 2',
    },
    {
        id: 3,
        title: 'Title 3',
        objectCount: 0,
        description: 'Description 3',
    },
];

function getButtonByIcon(icon: string): HTMLButtonElement {
    const iconElement = screen.getByLabelText(icon);
    const button = iconElement.parentElement;

    if (!(button instanceof HTMLButtonElement)) {
        throw new Error('Button with icon "' + icon + '" was not rendered.');
    }

    return button;
}

function getFolderButton(title: string): HTMLElement {
    const titleElement = screen.getByText(title);
    const button = titleElement.closest('[role="button"]');

    if (!(button instanceof HTMLElement)) {
        throw new Error('Folder with title "' + title + '" was not rendered.');
    }

    return button;
}

test('Render a basic Folder list with data', () => {
    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            data={DATA.slice(0, 2)}
            page={1}
            pageCount={2}
        />
    );

    expect(screen.getByText('Title 1')).toBeInTheDocument();
    expect(screen.getByText('1 Object')).toBeInTheDocument();
    expect(screen.getByText('Title 2')).toBeInTheDocument();
    expect(screen.getByText('7 Objects')).toBeInTheDocument();
    expect(screen.getByText(/sulu_admin.per_page/)).toBeInTheDocument();
});

test('Click on a Folder should call the onItemEdit callback', async() => {
    const user = userEvent.setup();
    const itemClickSpy = jest.fn();

    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            data={DATA}
            onItemClick={itemClickSpy}
        />
    );

    await user.click(getFolderButton('Title 2'));

    expect(itemClickSpy).toHaveBeenCalledWith(2);
});

test('Pagination should not be rendered if no data is available', () => {
    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            page={1}
        />
    );

    expect(screen.queryByText(/sulu_admin.per_page/)).not.toBeInTheDocument();
});

test('Pagination should be passed correct props', async() => {
    const user = userEvent.setup();
    const pageChangeSpy = jest.fn();
    const limitChangeSpy = jest.fn();

    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            data={DATA}
            limit={10}
            onLimitChange={limitChangeSpy}
            onPageChange={pageChangeSpy}
            page={2}
            pageCount={7}
        />
    );

    expect(screen.getByText(/sulu_admin.per_page/)).toBeInTheDocument();
    expect(screen.getByDisplayValue('2')).toBeInTheDocument();
    expect(screen.getByText(/sulu_admin.of/)).toHaveTextContent('sulu_admin.of 7');

    await user.click(getButtonByIcon('su-angle-right'));
    expect(pageChangeSpy).toHaveBeenCalledWith(3);

    await user.click(getButtonByIcon('su-angle-left'));
    expect(pageChangeSpy).toHaveBeenCalledWith(1);

    await user.click(screen.getByLabelText('su-angle-down'));
    await user.click(screen.getByText('20'));
    expect(limitChangeSpy).toHaveBeenCalledWith(20);
});

test('Pagination should not be rendered if pagination is false', () => {
    render(
        <FolderAdapter
            {...listAdapterDefaultProps}
            limit={10}
            page={2}
            pageCount={7}
            paginated={false}
        />
    );

    expect(screen.queryByText(/sulu_admin.per_page/)).not.toBeInTheDocument();
});
