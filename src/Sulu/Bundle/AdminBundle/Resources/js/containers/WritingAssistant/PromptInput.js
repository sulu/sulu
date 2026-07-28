// @flow

import React, {Component} from 'react';
import {action, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Button, Checkbox, DropdownButton, Icon, Input, SingleSelect} from '../../components';
import Tooltip from '../../components/Tooltip';
import promptInputStyles from './prompt-input.scss';

type Props = {|
    canIncludeContentContext?: boolean,
    disabled?: boolean,
    experts: {
        name: string,
        text: string,
        type: 'text',
    } | {
        handleClick?: (string) => void,
        name: string,
        options: Array<{ id: string, name: string }>,
        selected: string,
        type: 'select',
    },
    expertsLabel: string,
    includeContentContext?: boolean,
    includeContentContextInfo?: string,
    includeContentContextLabel?: string,
    isLoading: boolean,
    messages: {
        addMessage: string,
        send: string,
    },
    onAddMessage: (text: string) => Promise<void>,
    onIncludeContentContextChange?: (checked: boolean) => void,
    predefinedPrompts: ?{
        handleClick: (index: number) => void,
        label: string,
        moreLabel: string,
        options: Array<{
            icon?: ?string,
            id: number,
            name: string,
        }>,
    },
|};

const DEFAULT_PREDEFINED_PROMPT_ICON = 'fa-terminal';
const VISIBLE_PREDEFINED_PROMPTS = 3;

/**
 * @internal
 */
@observer
class PromptInput extends Component<Props> {
    @observable messageInput: string = '';

    @action handleInputChange = (message: ?string) => {
        this.messageInput = message || '';
    };

    @action handleSendMessage = () => {
        const {disabled, onAddMessage} = this.props;

        if (disabled) {
            return;
        }

        const messageInput = this.messageInput.trim();
        this.messageInput = '';
        if (messageInput !== '') {
            void onAddMessage(messageInput);
        }
    };

    handleKeyPress = (key: ?string) => {
        if (key === 'Enter') {
            this.handleSendMessage();
        }
    };

    renderExperts = () => {
        const {disabled, experts} = this.props;

        if (experts.type !== 'select') {
            return <span className={promptInputStyles.singleExpert}>{experts.text}</span>;
        }

        return (
            <div className={promptInputStyles.expertSelect}>
                <SingleSelect disabled={disabled} onChange={experts.handleClick} value={experts.selected}>
                    {experts.options.map((option) => (
                        <SingleSelect.Option key={option.id} value={option.id}>
                            {option.name}
                        </SingleSelect.Option>
                    ))}
                </SingleSelect>
            </div>
        );
    };

    renderPredefinedPrompts = () => {
        const {disabled, isLoading, predefinedPrompts} = this.props;

        if (!predefinedPrompts || predefinedPrompts.options.length === 0) {
            return null;
        }

        // the most used prompts stay within reach, the rest would otherwise wrap into additional rows
        const buttons = predefinedPrompts.options.slice(0, VISIBLE_PREDEFINED_PROMPTS);
        const remaining = predefinedPrompts.options.slice(VISIBLE_PREDEFINED_PROMPTS);

        return (
            <div className={promptInputStyles.predefinedPrompts}>
                <span className={promptInputStyles.label}>{predefinedPrompts.label}:</span>
                {buttons.map((option) => (
                    <Button
                        disabled={disabled || isLoading}
                        icon={option.icon || DEFAULT_PREDEFINED_PROMPT_ICON}
                        key={option.id}
                        onClick={predefinedPrompts.handleClick}
                        size="small"
                        skin="secondary"
                        value={option.id}
                    >{option.name}</Button>
                ))}
                {remaining.length > 0 &&
                    <DropdownButton
                        icon={DEFAULT_PREDEFINED_PROMPT_ICON}
                        label={predefinedPrompts.moreLabel}
                        skin="secondary"
                    >
                        {remaining.map((option) => (
                            <DropdownButton.Item
                                disabled={disabled || isLoading}
                                key={option.id}
                                onClick={predefinedPrompts.handleClick}
                                value={option.id}
                            >
                                {option.name}
                            </DropdownButton.Item>
                        ))}
                    </DropdownButton>
                }
            </div>
        );
    };

    renderContentContextCheckbox = () => {
        const {
            canIncludeContentContext,
            disabled,
            includeContentContext,
            includeContentContextInfo,
            includeContentContextLabel,
            onIncludeContentContextChange,
        } = this.props;

        if (!canIncludeContentContext) {
            return null;
        }

        return (
            <div className={promptInputStyles.contentContextCheckbox}>
                <Checkbox
                    checked={includeContentContext}
                    disabled={disabled}
                    onChange={onIncludeContentContextChange}
                    size="small"
                >
                    {includeContentContextLabel}
                </Checkbox>
                {includeContentContextInfo &&
                    <Tooltip label={includeContentContextInfo}>
                        <Icon className={promptInputStyles.infoIcon} name="su-exclamation-circle" />
                    </Tooltip>
                }
            </div>
        );
    };

    render() {
        const {
            disabled,
            expertsLabel,
            messages: {
                addMessage: addMessageMessage,
                send: sendMessage,
            },
        } = this.props;

        return (
            <div className={promptInputStyles.inputContainer}>
                <div className={promptInputStyles.options}>
                    <span className={promptInputStyles.label}>{expertsLabel}:</span>
                    {this.renderExperts()}
                    {this.renderContentContextCheckbox()}
                </div>
                <div className={promptInputStyles.panel}>
                    {this.renderPredefinedPrompts()}
                    <div className={promptInputStyles.input}>
                        <Input
                            disabled={disabled}
                            onChange={this.handleInputChange}
                            onKeyPress={this.handleKeyPress}
                            placeholder={addMessageMessage}
                            type="text"
                            value={this.messageInput}
                        />
                        <Button
                            disabled={disabled || (this.messageInput?.trim() ?? '') === ''}
                            onClick={this.handleSendMessage}
                            skin="primary"
                        >{sendMessage}</Button>
                    </div>
                </div>
            </div>
        );
    }
}

export default PromptInput;
