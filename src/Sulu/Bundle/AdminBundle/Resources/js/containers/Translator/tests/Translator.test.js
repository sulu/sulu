// @flow
import React from 'react';
import {render, screen, waitFor} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Requester from '../../../services/Requester';
import Translator from '../Translator';

jest.mock('../../../services/Requester');
jest.mock('debounce', () => jest.fn((fn) => fn));
jest.mock('../../../utils/Translator', () => ({
    translate: (key) => key,
}));

jest.mock('../../../containers', () => ({
    TextEditor: jest.fn(() => <div data-testid="text-editor" />),
}));

const mockProps = {
    locale: 'en',
    value: 'Hallo',
    onConfirm: jest.fn(),
    onDialogClose: jest.fn(),
    type: 'text_line',
    url: '/api/translate',
    messages: {
        title: 'Translate',
        insert: 'Insert',
        allLanguages: 'All languages',
        contactAdmin: 'Contact Admin',
        detected: 'Detected',
        outOfCredits: 'Out of Credits',
        outOfCreditsDescription: 'Your AI credits have been used up.',
        platformUnauthorized: 'Sulu.ai not available',
        platformUnauthorizedDescription: 'Sulu.ai rejected the credentials.',
        searchLanguages: 'Search languages',
        sourceLanguage: 'Source language',
        subscriptionInactive: 'Subscription Inactive',
        subscriptionInactiveDescription: 'Your AI subscription is not active.',
        targetLanguage: 'Target language',
        suggestedLanguages: 'Suggested languages',
        errorTranslatingText: 'Error translating text',
    },
    sourceLanguages: [
        {locale: 'en', label: 'English'},
        {locale: 'de', label: 'German'},
    ],
    suggestedLocales: ['de', 'en'],
    targetLanguages: [
        {locale: 'fr', label: 'French'},
        {locale: 'es', label: 'Spanish'},
    ],
};

describe('Translator', () => {
    beforeEach(() => {
        jest.clearAllMocks();
    });

    test('renders correctly with initial props', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(<Translator {...mockProps} />);

        expect(screen.getByText('Translate')).toBeInTheDocument();
        expect(screen.getByText('Insert')).toBeInTheDocument();
        expect(screen.getByDisplayValue('Hallo')).toBeInTheDocument();

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: 'Hallo',
                sourceLanguage: undefined,
                targetLanguage: 'en',
                type: 'text_line',
                resourceId: undefined,
                resourceKey: undefined,
                webspaceKey: undefined,
            });
        });

        expect(screen.getByDisplayValue('Hello')).toBeInTheDocument();
    });

    test('renders correctly with initial props and text_editor', async() => {
        Requester.post.mockResolvedValue({
            response: {text: '<h1>Hello</h1>', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(<Translator {...mockProps} type="text_editor" value="<h1>Hallo</h1>" />);

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: '<h1>Hallo</h1>',
                sourceLanguage: undefined,
                targetLanguage: 'en',
                type: 'text_editor',
                resourceId: undefined,
                resourceKey: undefined,
                webspaceKey: undefined,
            });
        });
    });

    test('translates text when source text changes', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(<Translator {...mockProps} />);

        Requester.post.mockResolvedValue({
            response: {text: 'Bye', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        const sourceInput = screen.getByDisplayValue('Hallo');
        await userEvent.clear(sourceInput);
        await userEvent.type(sourceInput, 'Auf wiedersehen');

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: 'Auf wiedersehen',
                sourceLanguage: undefined,
                targetLanguage: 'en',
                type: 'text_line',
                resourceId: undefined,
                resourceKey: undefined,
                webspaceKey: undefined,
            });
        });

        expect(screen.getByDisplayValue('Bye')).toBeInTheDocument();
    });

    test('changes source language', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(<Translator {...mockProps} />);
        await waitFor(() => {
            expect(screen.getByText('Hello')).toBeInTheDocument();
        });

        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: 'de', targetLanguage: 'en'},
        });

        await userEvent.click(screen.getByRole('button', {name: 'Source language'}));
        await waitFor(() => {
            expect(screen.getAllByText('German').length).toBeGreaterThan(0);
        });

        await userEvent.click(screen.getAllByText('German')[0]);

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: 'Hallo',
                sourceLanguage: 'de',
                targetLanguage: 'en',
                type: 'text_line',
                resourceId: undefined,
                resourceKey: undefined,
                webspaceKey: undefined,
            });
        });
    });

    test('changes target language', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(<Translator {...mockProps} />);
        await waitFor(() => {
            expect(screen.getByText('Hello')).toBeInTheDocument();
        });

        Requester.post.mockResolvedValue({
            response: {text: 'Hola', sourceLanguage: undefined, targetLanguage: 'fr'},
        });

        await userEvent.click(screen.getByRole('button', {name: 'Target language'}));
        await waitFor(() => {
            expect(screen.getAllByText('French').length).toBeGreaterThan(0);
        });

        await userEvent.click(screen.getAllByText('French')[0]);

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: 'Hallo',
                sourceLanguage: undefined,
                targetLanguage: 'fr',
                type: 'text_line',
                resourceId: undefined,
                resourceKey: undefined,
                webspaceKey: undefined,
            });
        });

        expect(screen.getByDisplayValue('Hola')).toBeInTheDocument();
    });

    test('handles translation error', async() => {
        Requester.post.mockRejectedValue(new Error('Error translating text'));

        render(<Translator {...mockProps} />);

        await waitFor(() => {
            const errorElement = screen.getAllByText((content, element) => {
                return element.textContent.includes('Error translating text');
            })[0];

            expect(errorElement).toBeInTheDocument();
        });
    });

    test('names the account condition the platform reported and disables inserting', async() => {
        Requester.post.mockRejectedValue({
            json: () => Promise.resolve({messageKey: 'sulu_ai.out_of_credits'}),
        });

        render(<Translator {...mockProps} contactEmail="admin@example.com" />);

        await waitFor(() => {
            expect(screen.getByText('Out of Credits')).toBeInTheDocument();
        });

        expect(screen.getAllByText((content, element) => {
            return element.textContent.includes('Your AI credits have been used up.');
        })[0]).toBeInTheDocument();
        expect(screen.getByText('Contact Admin')).toBeInTheDocument();
        expect(screen.getByText('Insert').closest('button')).toBeDisabled();
    });

    test('leaves out the contact action when no contact email is configured', async() => {
        Requester.post.mockRejectedValue({
            json: () => Promise.resolve({messageKey: 'sulu_ai.platform_unauthorized'}),
        });

        render(<Translator {...mockProps} />);

        await waitFor(() => {
            expect(screen.getByText('Sulu.ai not available')).toBeInTheDocument();
        });

        expect(screen.queryByText('Contact Admin')).not.toBeInTheDocument();
    });

    test('calls onConfirm with translated text', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Bonjour', sourceLanguage: 'EN', targetLanguage: 'FR'},
        });

        render(<Translator {...mockProps} />);

        await waitFor(() => {
            expect(screen.getByDisplayValue('Bonjour')).toBeInTheDocument();
        });

        await userEvent.click(screen.getByText('Insert'));

        expect(mockProps.onConfirm).toHaveBeenCalledWith('Bonjour');
    });

    test('calls onDialogClose when closing', async() => {
        render(<Translator {...mockProps} />);

        const closeButton = screen.getAllByRole('button', {name: /su-times/i})[0];
        await userEvent.click(closeButton);

        expect(mockProps.onDialogClose).toHaveBeenCalled();
    });

    test('sends webspaceKey, resourceId and resourceKey when provided', async() => {
        Requester.post.mockResolvedValue({
            response: {text: 'Hello', sourceLanguage: undefined, targetLanguage: 'en'},
        });

        render(
            <Translator
                {...mockProps}
                resourceId="page-123"
                resourceKey="pages"
                webspaceKey="sulu_io"
            />
        );

        await waitFor(() => {
            expect(Requester.post).toHaveBeenCalledWith('/api/translate', {
                text: 'Hallo',
                sourceLanguage: undefined,
                targetLanguage: 'en',
                type: 'text_line',
                resourceId: 'page-123',
                resourceKey: 'pages',
                webspaceKey: 'sulu_io',
            });
        });
    });

    test('calls action prop with correct parameters when translation occurs', async() => {
        const mockAction = jest.fn(() => <div>Test Action</div>);
        const props = {
            ...mockProps,
            // $FlowFixMe
            action: mockAction,
        };

        Requester.post.mockResolvedValue({
            response: {
                text: 'Bonjour',
                sourceLanguage: 'EN',
                targetLanguage: 'FR',
                type: 'text_line',
            },
        });

        render(<Translator {...props} />);

        await waitFor(() => {
            expect(screen.getByText('Test Action')).toBeInTheDocument();
        });

        expect(mockAction).toHaveBeenCalledTimes(3);
        expect(mockAction).toHaveBeenNthCalledWith(3, {
            source: 'translator',
            context: {
                response: {
                    text: 'Bonjour',
                    sourceLanguage: 'EN',
                    targetLanguage: 'FR',
                    type: 'text_line',
                },
            },
        }, {});
    });
});
