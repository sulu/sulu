// @flow
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import React from 'react';
import WebspaceSelect from '../WebspaceSelect';

test('Render WebspaceSelect closed', () => {
    const {baseElement} = render(
        <WebspaceSelect onChange={jest.fn()} value="sulu">
            <WebspaceSelect.Item value="sulu">Sulu</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_blog">Sulu Blog</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_doc">Sulu Doc</WebspaceSelect.Item>
        </WebspaceSelect>
    );

    expect(baseElement).toMatchSnapshot();
});

test('Render WebspaceSelect opened', async() => {
    const user = userEvent.setup();

    render(
        <WebspaceSelect onChange={jest.fn()} value="sulu">
            <WebspaceSelect.Item value="sulu">Sulu</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_blog">Sulu Blog</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_doc">Sulu Doc</WebspaceSelect.Item>
        </WebspaceSelect>
    );

    expect(screen.queryByText('Sulu Blog')).not.toBeInTheDocument();

    // click button to open webspace select
    await user.click(screen.getByRole('button'));
    expect(screen.getByText('Sulu Blog')).toBeInTheDocument();
});

test('Change event should be called correctly', async() => {
    const handleChange = jest.fn();
    const value = 'sulu';
    const user = userEvent.setup();

    render(
        <WebspaceSelect onChange={handleChange} value={value}>
            <WebspaceSelect.Item value="sulu">Sulu</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_blog">Sulu Blog</WebspaceSelect.Item>
            <WebspaceSelect.Item value="sulu_doc">Sulu Doc</WebspaceSelect.Item>
        </WebspaceSelect>
    );

    // click second item to fire change event
    await user.click(screen.getByRole('button'));
    await user.click(screen.getByText('Sulu Blog'));

    expect(handleChange).toHaveBeenCalledWith('sulu_blog');
});
