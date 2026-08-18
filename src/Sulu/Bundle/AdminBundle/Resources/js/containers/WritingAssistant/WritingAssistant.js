// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable, computed, toJS} from 'mobx';
import {Requester} from '../../services';
import {Overlay} from '../../components';
import Snackbar from '../../components/Snackbar';
import {translate} from '../../utils';
import {ACCOUNT_LIMIT_MESSAGE_KEYS, readMessageKey} from '../AiApplication/accountLimits';
import writingAssistantStyles from './writingAssistant.scss';
import Messages from './Messages';
import PromptInput from './PromptInput';
import type {ExpertType, MessageType, RequestErrorType} from './types';

type Props = {|
    action?: React$ComponentType<Object>,
    actionProps?: Object,
    configuration: {
        experts: {
            [string]: ExpertType,
        },
    },
    contactEmail?: ?string,
    contentData?: ?Object,
    dataPath?: ?string,
    locale: string,
    messages: {
        addMessage: string,
        contactAdmin: string,
        copiedToClipboard: string,
        experts: string,
        includeContentContext: string,
        includeContentContextInfo: string,
        initialMessage: string,
        morePredefinedPrompts: string,
        predefinedPrompts: string,
        requestFailed: string,
        requestFailedDescription: string,
        send: string,
        tryAgain: string,
        writingAssistant: string,
    },
    onConfirm: (text: string) => void,
    onDialogClose: () => void,
    resourceId?: ?(string | number),
    resourceKey?: ?string,
    type: 'text_line' | 'text_area' | 'text_editor',
    url: string,
    value?: string,
    webspaceKey?: ?string,
|};

/**
 * @internal
 */
@observer
export default class WritingAssistant extends React.Component<Props> {
    @observable messages: Array<MessageType> = [];
    @observable loader: ?{
        commandTitle: string,
        expert: string,
    } = undefined;
    @observable selectedExpert: string;
    @observable snackbarMessage = undefined;
    @observable requestError: ?RequestErrorType = undefined;
    @observable lastResponse = undefined;
    @observable currentValue: string;
    @observable includeContentContext: boolean = false;

    constructor(props: Props) {
        super(props);

        this.selectedExpert = this.experts[0].uuid;
        this.includeContentContext =
            sessionStorage.getItem('sulu_admin.include_content_context') === 'true';
        // push initial message
        this.messages.push(
            {
                title: this.props.messages.initialMessage,
                text: props.value || '',
                type: props.type,
                collapsed: true,
                displayActions: false,
            }
        );

        this.currentValue = props.value || '';
    }

    @action handleAddMessage = async(prompt: string, title: ?string) => {
        const {type} = this.props;

        if (this.accountLimit) {
            return;
        }

        const result = await this.optimizeText(prompt, title);

        if (!result) {
            return;
        }

        this.currentValue = result.text;

        this.addMessage(
            {
                command: prompt,
                title: title || prompt,
                expert: this.props.configuration.experts[this.selectedExpert].name,
                text: result.text,
                type,
                collapsed: false,
                displayActions: true,
            }
        );
    };

    @action addMessage = (message: MessageType) => {
        // index 0 is the selected text, which never collapses
        this.messages.forEach((olderMessage, index) => {
            if (index > 0) {
                olderMessage.collapsed = true;
            }
        });

        this.messages = [...this.messages, message];
    };

    @action optimizeText = async(prompt: string, title: ?string) => {
        const {
            locale,
            url,
        } = this.props;

        this.loader = {
            commandTitle: title ?? prompt,
            expert: this.props.configuration.experts[this.selectedExpert].name,
        };
        this.requestError = undefined;

        const body: Object = {
            text: this.currentValue,
            message: prompt,
            expertUuid: this.selectedExpert,
            locale,
            resourceId: String(this.props.resourceId ?? ''),
            resourceKey: this.props.resourceKey ?? '',
            webspaceKey: this.props.webspaceKey,
        };

        if (this.includeContentContext && this.props.contentData) {
            body.data = toJS(this.props.contentData);
            body.dataPath = this.props.dataPath;
        }

        return Requester.post(url, body).then(action((data) => {
            this.loader = undefined;
            this.lastResponse = data;
            return data.response;
        })).catch((error) => this.handleRequestFailure(error, prompt, title));
    };

    handleRequestFailure = (error: Object, prompt: string, title: ?string) => {
        return readMessageKey(error).then(action((messageKey: ?string) => {
            this.loader = undefined;
            this.lastResponse = {error};
            this.requestError = {messageKey, prompt, title};

            return undefined;
        }));
    };

    @computed get accountLimit(): ?string {
        const messageKey = this.requestError?.messageKey;

        return messageKey && ACCOUNT_LIMIT_MESSAGE_KEYS.includes(messageKey) ? messageKey : undefined;
    }

    @action handleErrorRetry = () => {
        const requestError = this.requestError;

        if (!requestError) {
            return;
        }

        this.requestError = undefined;
        void this.handleAddMessage(requestError.prompt, requestError.title);
    };

    handleContactAdminClick = () => {
        const {contactEmail} = this.props;

        if (contactEmail) {
            window.location.href = 'mailto:' + contactEmail;
        }
    };

    handlePredefinedPromptButtonClick = (action: {name: string, prompt: string}) => {
        void this.handleAddMessage(action.prompt, action.name);
    };

    handlePredefinedPromptSelectClick = (index: number) => {
        const {configuration} = this.props;
        const predefinedPrompts = configuration.experts[this.selectedExpert].options.predefinedPrompts || [];

        this.handlePredefinedPromptButtonClick({
            name: predefinedPrompts[index].name,
            prompt: predefinedPrompts[index].prompt,
        });
    };

    @action handleExpertSelect = (expert: string) => {
        this.selectedExpert = expert;
    };

    @action handleIncludeContentContextChange = (checked: boolean) => {
        this.includeContentContext = checked;
        sessionStorage.setItem('sulu_admin.include_content_context', String(checked));
    };

    @computed get canIncludeContentContext(): boolean {
        return !!this.props.contentData;
    }

    @action handleOnRetry = (prompt: string, title: string) => {
        void this.handleAddMessage(prompt, title);
    };

    @action handleOnMessageClicked = (index: number) => {
        this.messages[index].collapsed = !this.messages[index].collapsed;
    };

    @computed get experts(): Array<ExpertType> {
        // $FlowFixMe
        return (Object.values(this.props.configuration.experts): Array<ExpertType>) || [];
    }

    @computed get expertsButton() {
        if (this.experts.length === 1) {
            return {
                name: 'experts',
                type: 'text',
                text: this.experts[0].name,
            };
        }

        return {
            name: 'experts',
            type: 'select',
            selected: this.selectedExpert,
            options: this.experts.map((expert: ExpertType): { id: string, name: string } => {
                return {
                    id: expert.uuid,
                    name: expert.name,
                };
            }),
            handleClick: this.handleExpertSelect,
        };
    }

    @computed get predefinedPrompts() {
        const {
            configuration,
            messages: {
                morePredefinedPrompts: morePredefinedPromptsMessage,
                predefinedPrompts: predefinedPromptsMessage,
            },
        } = this.props;

        const predefinedPrompts = configuration.experts[this.selectedExpert].options.predefinedPrompts || [];

        return predefinedPrompts.length > 0 ? {
            label: predefinedPromptsMessage,
            moreLabel: morePredefinedPromptsMessage,
            options: predefinedPrompts.map((predefinedPrompt, index) => ({
                icon: predefinedPrompt.icon,
                id: index,
                name: predefinedPrompt.name,
            })),
            handleClick: this.handlePredefinedPromptSelectClick,
        } : undefined;
    }

    @action handleDialogClose = () => {
        const {onDialogClose} = this.props;
        onDialogClose();
    };

    @action handleOnInsert = (text: string) => {
        const {
            onConfirm,
        } = this.props;

        // We have to stop the propagation of the event to prevent the focus lose of the input / editor field
        // $FlowFixMe
        event.stopPropagation();
        onConfirm(text);
    };

    @action handleOnCopy = (text: string) => {
        const {
            messages: {
                copiedToClipboard: copiedToClipboardMessage,
            },
        } = this.props;

        void navigator.clipboard.writeText(text);
        this.snackbarMessage = copiedToClipboardMessage;
    };

    @action handleSnackbarCloseClick = () => {
        this.snackbarMessage = undefined;
    };

    render() {
        const {accountLimit} = this;
        const {
            action: Action,
            locale,
            contactEmail,
            messages: {
                writingAssistant: writingAssistantMessage,
                addMessage: addMessageMessage,
                contactAdmin: contactAdminMessage,
                experts: expertsMessage,
                includeContentContext: includeContentContextMessage,
                includeContentContextInfo: includeContentContextInfoMessage,
                requestFailed: requestFailedMessage,
                requestFailedDescription: requestFailedDescriptionMessage,
                send: sendMessage,
                tryAgain: tryAgainMessage,
            },
        } = this.props;

        const actionNode = Action ? (
            <Action
                {...(this.props.actionProps || {})}
                context={toJS(this.lastResponse)}
                source="writing_assistant"
            />
        ) : <React.Fragment />;

        return (
            <Overlay
                onClose={this.handleDialogClose}
                onSnackbarCloseClick={this.handleSnackbarCloseClick}
                open={true}
                size="small"
                snackbarMessage={this.snackbarMessage}
                snackbarType="success"
                title={writingAssistantMessage}
            >
                {actionNode}

                <div className={writingAssistantStyles.content}>
                    <div className={writingAssistantStyles.chat}>
                        <Messages
                            error={this.requestError && !this.accountLimit
                                ? {
                                    actionLabel: tryAgainMessage,
                                    message: requestFailedDescriptionMessage,
                                    title: requestFailedMessage,
                                }
                                : undefined
                            }
                            isLoading={!!this.loader}
                            loader={this.loader}
                            locale={locale}
                            messages={toJS(this.messages)}
                            onCopy={this.handleOnCopy}
                            onErrorActionClick={this.handleErrorRetry}
                            onInsert={this.handleOnInsert}
                            onMessageClicked={this.handleOnMessageClicked}
                            onRetry={this.handleOnRetry}
                        />
                        {accountLimit &&
                            <div className={writingAssistantStyles.accountLimit}>
                                <Snackbar
                                    actions={contactEmail
                                        ? [{label: contactAdminMessage, onClick: this.handleContactAdminClick}]
                                        : undefined
                                    }
                                    message={translate(accountLimit + '_description')}
                                    title={translate(accountLimit)}
                                    type="error"
                                />
                            </div>
                        }
                        <PromptInput
                            canIncludeContentContext={this.canIncludeContentContext}
                            disabled={!!this.accountLimit}
                            experts={this.expertsButton}
                            expertsLabel={expertsMessage}
                            includeContentContext={this.includeContentContext}
                            includeContentContextInfo={includeContentContextInfoMessage}
                            includeContentContextLabel={includeContentContextMessage}
                            isLoading={!!this.loader}
                            messages={{
                                addMessage: addMessageMessage,
                                send: sendMessage,
                            }}
                            onAddMessage={this.handleAddMessage}
                            onIncludeContentContextChange={this.handleIncludeContentContextChange}
                            predefinedPrompts={this.predefinedPrompts}
                        />
                    </div>
                </div>
            </Overlay>
        );
    }
}
