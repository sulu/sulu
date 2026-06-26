// @flow

import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import {Requester} from '../../../services';
import WritingAssistant from '../WritingAssistant';

// Mock the Requester and translate functions
jest.mock('../../../services', () => ({
    Requester: {
        post: jest.fn().mockResolvedValue({response: {text: 'Optimized text'}}),
    },
}));

jest.mock('../../../utils/Translator');

const writeText = jest.fn();
Object.assign(navigator, {
    // $FlowFixMe
    clipboard: {
        writeText,
    },
});

describe('WritingAssistant Component', () => {
    const defaultProps = {
        configuration: {
            experts: {
                '1': {uuid: '1', name: 'Expert 1', options: {predefinedPrompts: []}},
                '2': {uuid: '2', name: 'Expert 2', options: {predefinedPrompts: []}},
            },
        },
        locale: 'en',
        messages: {
            addMessage: 'Add Message',
            copiedToClipboard: 'Copied to Clipboard',
            includeContentContext: 'Add whole content as context',
            initialMessage: 'Initial Message',
            predefinedPrompts: 'Predefined Prompts',
            send: 'Send',
            writingAssistant: 'Writing Assistant',
        },
        onConfirm: jest.fn(),
        onDialogClose: jest.fn(),
        type: 'text_line',
        url: 'https://example.com/api',
        value: 'Initial value',
    };

    test('renders the WritingAssistant component with initial message', () => {
        render(<WritingAssistant {...defaultProps} />);

        expect(screen.getByText(defaultProps.messages.writingAssistant)).toBeInTheDocument();
        expect(screen.getByText(defaultProps.messages.initialMessage)).toBeInTheDocument();
    });

    test('renders the expert select dropdown when multiple experts are available', async() => {
        render(<WritingAssistant {...defaultProps} />);

        await userEvent.click(screen.getByText('Expert 1'));

        expect(screen.getAllByText('Expert 1')[1]).toBeInTheDocument();
        expect(screen.getAllByText('Expert 2')[0]).toBeInTheDocument();
    });

    test('renders the predefined prompts dropdown when predefinedPrompts are available', async() => {
        const predefinedPrompts = [
            {id: 1, name: 'Prompt 1', prompt: 'Prompt 1 text'},
            {id: 2, name: 'Prompt 2', prompt: 'Prompt 2 text'},
        ];
        const configuration = {
            ...defaultProps.configuration,
            experts: {
                '1': {
                    ...defaultProps.configuration.experts['1'],
                    options: {predefinedPrompts},
                },
            },
        };

        render(<WritingAssistant {...defaultProps} configuration={configuration} />);

        await userEvent.click(screen.getByText(defaultProps.messages.predefinedPrompts));
        expect(screen.getByText('Prompt 1')).toBeInTheDocument();
        expect(screen.getByText('Prompt 2')).toBeInTheDocument();
    });

    test('calls onAddMessage when a message is added', async() => {
        render(<WritingAssistant {...defaultProps} />);

        const input = screen.getByPlaceholderText(defaultProps.messages.addMessage);
        await userEvent.type(input, 'Test message{enter}');

        await waitFor(() => {
            expect(screen.getByText('Optimized text')).toBeInTheDocument();
        });

        expect(Requester.post).toHaveBeenCalledWith('https://example.com/api', {
            expertUuid: '1',
            locale: 'en',
            message: 'Test message',
            resourceId: '',
            resourceKey: '',
            text: 'Initial value',
            webspaceKey: undefined,
        });
    });

    test('calls onConfirm when the insert button is clicked', async() => {
        render(<WritingAssistant {...defaultProps} />);

        const input = screen.getByPlaceholderText(defaultProps.messages.addMessage);
        await userEvent.type(input, 'Test message{enter}');

        await waitFor(() => {
            expect(screen.getByText('Optimized text')).toBeInTheDocument();
        });

        const insertButton = screen.getByText('sulu_admin.insert');
        await userEvent.click(insertButton);

        expect(defaultProps.onConfirm).toHaveBeenCalledWith('Optimized text');
    });

    test('calls onDialogClose when the close button is clicked', async() => {
        render(<WritingAssistant {...defaultProps} />);

        const closeButton = screen.getAllByRole('button', {name: /su-times/i})[0];
        await userEvent.click(closeButton);

        expect(defaultProps.onDialogClose).toHaveBeenCalled();
    });

    test('navigator clipboard writeText should be called when text is copied to clipboard', async() => {
        render(<WritingAssistant {...defaultProps} />);

        const input = screen.getByPlaceholderText(defaultProps.messages.addMessage);
        await userEvent.type(input, 'Test message{enter}');

        await waitFor(() => {
            expect(screen.getByText('Optimized text')).toBeInTheDocument();
        });

        const copyButton = screen.getAllByRole('button', {name: /su-copy/i})[1];
        await userEvent.click(copyButton);

        expect(writeText).toHaveBeenCalledWith('Optimized text');
    });

    test('sends resourceId and resourceKey even without content context', async() => {
        render(
            <WritingAssistant
                {...defaultProps}
                resourceId="page-123"
                resourceKey="pages"
            />
        );

        const input = screen.getByPlaceholderText(defaultProps.messages.addMessage);
        await userEvent.type(input, 'Test message{enter}');

        await waitFor(() => {
            expect(screen.getByText('Optimized text')).toBeInTheDocument();
        });

        expect(Requester.post).toHaveBeenCalledWith('https://example.com/api', {
            expertUuid: '1',
            locale: 'en',
            message: 'Test message',
            resourceId: 'page-123',
            resourceKey: 'pages',
            text: 'Initial value',
            webspaceKey: undefined,
        });
    });

    test('sends content data when includeContentContext is enabled', async() => {
        sessionStorage.setItem('sulu_admin.include_content_context', 'true');

        const contentData = {title: 'Page Title', description: 'Page Description'};

        render(
            <WritingAssistant
                {...defaultProps}
                contentData={contentData}
                dataPath="/block/0/text"
                resourceId="page-123"
                resourceKey="pages"
            />
        );

        const input = screen.getByPlaceholderText(defaultProps.messages.addMessage);
        await userEvent.type(input, 'Improve this{enter}');

        await waitFor(() => {
            expect(screen.getByText('Optimized text')).toBeInTheDocument();
        });

        expect(Requester.post).toHaveBeenCalledWith('https://example.com/api', {
            data: contentData,
            dataPath: '/block/0/text',
            expertUuid: '1',
            locale: 'en',
            message: 'Improve this',
            resourceId: 'page-123',
            resourceKey: 'pages',
            text: 'Initial value',
            webspaceKey: undefined,
        });

        sessionStorage.removeItem('sulu_admin.include_content_context');
    });

    test('does not send content data when includeContentContext is disabled', async() => {
        sessionStorage.setItem('sulu_admin.include_content_context', 'false');

        const contentData = {title: 'Page Title'};

        render(
            <WritingAssistant
                {...defaultProps}
                contentData={contentData}
                dataPath="/block/0/text"
                resourceId="page-123"
                resourceKey="pages"
            />
        );

        const input = screen.getByPlaceholderText(defaultProps.messages.addMessage);
        await userEvent.type(input, 'Improve this{enter}');

        await waitFor(() => {
            expect(screen.getByText('Optimized text')).toBeInTheDocument();
        });

        expect(Requester.post).toHaveBeenCalledWith('https://example.com/api', {
            expertUuid: '1',
            locale: 'en',
            message: 'Improve this',
            resourceId: 'page-123',
            resourceKey: 'pages',
            text: 'Initial value',
            webspaceKey: undefined,
        });

        sessionStorage.removeItem('sulu_admin.include_content_context');
    });

    test('sends webspaceKey when provided', async() => {
        render(
            <WritingAssistant
                {...defaultProps}
                resourceId="page-123"
                resourceKey="pages"
                webspaceKey="sulu_io"
            />
        );

        const input = screen.getByPlaceholderText(defaultProps.messages.addMessage);
        await userEvent.type(input, 'Test message{enter}');

        await waitFor(() => {
            expect(screen.getByText('Optimized text')).toBeInTheDocument();
        });

        expect(Requester.post).toHaveBeenCalledWith('https://example.com/api', {
            expertUuid: '1',
            locale: 'en',
            message: 'Test message',
            resourceId: 'page-123',
            resourceKey: 'pages',
            text: 'Initial value',
            webspaceKey: 'sulu_io',
        });
    });

    test('does not show content context checkbox when no contentData is provided', () => {
        render(<WritingAssistant {...defaultProps} />);

        expect(screen.queryByText('Add whole content as context')).not.toBeInTheDocument();
    });

    test('shows content context checkbox when contentData is provided', () => {
        const predefinedPrompts = [
            {id: 1, name: 'Prompt 1', prompt: 'Prompt 1 text'},
            {id: 2, name: 'Prompt 2', prompt: 'Prompt 2 text'},
        ];
        const configuration = {
            ...defaultProps.configuration,
            experts: {
                '1': {
                    ...defaultProps.configuration.experts['1'],
                    options: {predefinedPrompts},
                },
            },
        };

        render(
            <WritingAssistant
                {...defaultProps}
                configuration={configuration}
                contentData={{title: 'Test'}}
                resourceId="page-123"
                resourceKey="pages"
            />
        );

        expect(screen.getByText('Add whole content as context')).toBeInTheDocument();
    });
});
