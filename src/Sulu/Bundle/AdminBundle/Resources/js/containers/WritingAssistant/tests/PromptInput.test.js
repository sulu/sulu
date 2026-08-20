// @flow

import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import '@testing-library/jest-dom/extend-expect';
import PromptInput from '../PromptInput';

jest.mock('../../../containers', () => ({
    TextEditor: jest.fn(({value}) => <div data-testid="text-editor">{value}</div>),
}));

jest.mock('../../../utils', () => ({
    translate: (key) => key,
}));

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

describe('PromptInput Component', () => {
    const defaultProps = {
        experts: {
            name: 'Expert Name',
            text: 'Expert Text',
            type: 'text',
        },
        expertsLabel: 'Experts',
        isLoading: false,
        onAddMessage: jest.fn().mockResolvedValue(undefined),
        predefinedPrompts: null,
        messages: {
            send: 'Send',
            addMessage: 'Add Message',
        },
    };

    test('renders the expert text when type is text', () => {
        render(<PromptInput {...defaultProps} />);

        expect(screen.getAllByText(defaultProps.experts.text)[0]).toBeInTheDocument();
    });

    test('renders the SingleSelect when type is select', async() => {
        const selectExperts = {
            name: 'Expert Name',
            options: [
                {id: '1', name: 'Option 1'},
                {id: '2', name: 'Option 2'},
            ],
            selected: '1',
            type: 'select',
            handleClick: jest.fn(),
        };

        render(<PromptInput {...defaultProps} experts={selectExperts} />);

        await userEvent.click(screen.getAllByText('Option 1')[0]);

        expect(screen.getAllByText('Option 1')[0]).toBeInTheDocument();
        expect(screen.getAllByText('Option 2')[0]).toBeInTheDocument();
    });

    test('renders a button for every predefined prompt', () => {
        const predefinedPrompts = {
            handleClick: jest.fn(),
            label: 'Predefined Prompts',
            moreLabel: 'More',
            options: [
                {icon: undefined, id: 1, name: 'Prompt 1'},
                {icon: undefined, id: 2, name: 'Prompt 2'},
            ],
        };

        render(<PromptInput {...defaultProps} predefinedPrompts={predefinedPrompts} />);

        expect(screen.getByText('Predefined Prompts:')).toBeInTheDocument();
        expect(screen.getByText('Prompt 1')).toBeInTheDocument();
        expect(screen.getByText('Prompt 2')).toBeInTheDocument();
        expect(screen.queryByText('More')).not.toBeInTheDocument();
    });

    test('moves every prompt beyond the third one into a dropdown', async() => {
        const predefinedPrompts = {
            handleClick: jest.fn(),
            label: 'Predefined Prompts',
            moreLabel: 'More',
            options: [1, 2, 3, 4, 5].map((id) => ({icon: undefined, id, name: 'Prompt ' + id})),
        };

        render(<PromptInput {...defaultProps} predefinedPrompts={predefinedPrompts} />);

        expect(screen.getByText('Prompt 3')).toBeInTheDocument();
        expect(screen.queryByText('Prompt 4')).not.toBeInTheDocument();

        await userEvent.click(screen.getByText('More'));

        expect(screen.getByText('Prompt 4')).toBeInTheDocument();
        expect(screen.getByText('Prompt 5')).toBeInTheDocument();
    });

    test('calls handleClick with the prompt index when a quick action is clicked', async() => {
        const predefinedPrompts = {
            handleClick: jest.fn(),
            label: 'Predefined Prompts',
            moreLabel: 'More',
            options: [
                {icon: undefined, id: 0, name: 'Prompt 1'},
                {icon: undefined, id: 1, name: 'Prompt 2'},
            ],
        };

        render(<PromptInput {...defaultProps} predefinedPrompts={predefinedPrompts} />);

        await userEvent.click(screen.getByText('Prompt 2'));

        expect(predefinedPrompts.handleClick).toHaveBeenCalledWith(1);
    });

    test('disables the quick actions and the input when disabled', () => {
        const predefinedPrompts = {
            handleClick: jest.fn(),
            label: 'Predefined Prompts',
            moreLabel: 'More',
            options: [
                {icon: undefined, id: 0, name: 'Prompt 1'},
            ],
        };

        render(<PromptInput {...defaultProps} disabled={true} predefinedPrompts={predefinedPrompts} />);

        expect(screen.getByText('Prompt 1').closest('button')).toBeDisabled();
        expect(screen.getByPlaceholderText('Add Message')).toBeDisabled();
    });

    test('calls onAddMessage when the send button is clicked', async() => {
        render(<PromptInput {...defaultProps} />);

        const input = screen.getByPlaceholderText('Add Message');
        await userEvent.type(input, 'Test message');
        await userEvent.click(screen.getByText('Send'));

        expect(defaultProps.onAddMessage).toHaveBeenCalledWith('Test message');
    });

    test('calls onAddMessage when Enter key is pressed', async() => {
        render(<PromptInput {...defaultProps} />);

        const input = screen.getByPlaceholderText('Add Message');
        await userEvent.type(input, 'Test message{enter}');

        expect(defaultProps.onAddMessage).toHaveBeenCalledWith('Test message');
    });

    test('does not call onAddMessage when input is empty', async() => {
        render(<PromptInput {...defaultProps} />);

        await userEvent.click(screen.getByRole('button', 'Send'));

        expect(defaultProps.onAddMessage).not.toHaveBeenCalled();
    });

    test('disables the send button when input is empty', () => {
        render(<PromptInput {...defaultProps} />);

        const button = screen.getByRole('button', 'Send');
        expect(button).toBeDisabled();
    });

    test('enables the send button when input is not empty', async() => {
        render(<PromptInput {...defaultProps} />);

        const input = screen.getByPlaceholderText('Add Message');
        await userEvent.type(input, 'Test message');

        const button = screen.getByText('Send');
        expect(button).toBeEnabled();
    });

    test('does not render content context checkbox when canIncludeContentContext is false', () => {
        render(<PromptInput {...defaultProps} canIncludeContentContext={false} />);

        expect(screen.queryByText('Add content context')).not.toBeInTheDocument();
    });

    test('does not render content context checkbox when canIncludeContentContext is undefined', () => {
        render(<PromptInput {...defaultProps} />);

        expect(screen.queryByRole('checkbox')).not.toBeInTheDocument();
    });

    test('renders content context checkbox when canIncludeContentContext is true', () => {
        render(
            <PromptInput
                {...defaultProps}
                canIncludeContentContext={true}
                includeContentContext={false}
                includeContentContextLabel="Add whole content as context"
                predefinedPrompts={{
                    handleClick: jest.fn(),
                    label: 'Predefined Prompts',
                    moreLabel: 'More',
                    options: [{id: 1, name: 'Prompt 1'}],
                }}
            />
        );

        expect(screen.getByText('Add whole content as context')).toBeInTheDocument();
    });

    test('calls onIncludeContentContextChange when checkbox is toggled', async() => {
        const onIncludeContentContextChange = jest.fn();

        render(
            <PromptInput
                {...defaultProps}
                canIncludeContentContext={true}
                includeContentContext={false}
                includeContentContextLabel="Add whole content as context"
                onIncludeContentContextChange={onIncludeContentContextChange}
                predefinedPrompts={{
                    handleClick: jest.fn(),
                    label: 'Predefined Prompts',
                    moreLabel: 'More',
                    options: [{id: 1, name: 'Prompt 1'}],
                }}
            />
        );

        const checkbox = screen.getByRole('checkbox');
        await userEvent.click(checkbox);

        expect(onIncludeContentContextChange).toHaveBeenCalledWith(true, undefined);
    });

    test('renders checkbox as checked when includeContentContext is true', () => {
        render(
            <PromptInput
                {...defaultProps}
                canIncludeContentContext={true}
                includeContentContext={true}
                includeContentContextLabel="Add whole content as context"
                predefinedPrompts={{
                    handleClick: jest.fn(),
                    label: 'Predefined Prompts',
                    moreLabel: 'More',
                    options: [{id: 1, name: 'Prompt 1'}],
                }}
            />
        );

        const checkbox = screen.getByRole('checkbox');
        expect(checkbox).toBeChecked();
    });
});
