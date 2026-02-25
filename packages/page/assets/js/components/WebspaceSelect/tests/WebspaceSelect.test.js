// @flow
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import WebspaceSelect from '../WebspaceSelect';

jest.mock('sulu-admin-bundle/utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

const createChildren = () => [
    <WebspaceSelect.Item key="sulu" value="sulu">Sulu</WebspaceSelect.Item>,
    <WebspaceSelect.Item key="sulu_blog" value="sulu_blog">Sulu Blog</WebspaceSelect.Item>,
    <WebspaceSelect.Item key="sulu_doc" value="sulu_doc">Sulu Doc</WebspaceSelect.Item>,
];

beforeEach(() => {
    jest.clearAllMocks();
});

test('Render WebspaceSelect closed', () => {
    const {asFragment} = render(<WebspaceSelect onChange={jest.fn()} value="sulu">{createChildren()}</WebspaceSelect>);

    expect(screen.getByText('Sulu')).toBeInTheDocument();
    expect(screen.queryByText('Sulu Blog')).not.toBeInTheDocument();
    expect(asFragment()).toMatchSnapshot();
});

test('Render WebspaceSelect opened', async() => {
    const user = userEvent.setup();
    render(<WebspaceSelect onChange={jest.fn()} value="sulu">{createChildren()}</WebspaceSelect>);
    expect(screen.queryByText('Sulu Blog')).not.toBeInTheDocument();

    await user.click(screen.getByRole('button', {name: /Sulu/}));
    expect(screen.getByText('Sulu Blog')).toBeInTheDocument();
});

test('Change event should be called correctly', async() => {
    const user = userEvent.setup();
    const handleChange = jest.fn();

    render(<WebspaceSelect onChange={handleChange} value="sulu">{createChildren()}</WebspaceSelect>);
    await user.click(screen.getByRole('button', {name: /Sulu/}));
    await user.click(screen.getByText('Sulu Blog'));

    expect(handleChange).toBeCalledWith('sulu_blog');
    expect(screen.queryByText('sulu_page.webspaces')).not.toBeInTheDocument();
});
