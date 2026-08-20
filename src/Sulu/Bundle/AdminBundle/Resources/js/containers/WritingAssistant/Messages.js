// @flow
import React, {Component} from 'react';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import Snackbar from '../../components/Snackbar';
import Message from './Message';
import Loader from './Loader';
import messagesStyles from './messages.scss';
import type {MessageType} from './types';

type Props = {|
    error: ?{|
        actionLabel: string,
        message: string,
        title: string,
    |},
    isLoading: boolean,
    loader: ?{
        commandTitle: string,
        expert: string,
    },
    locale: string,
    messages: Array<MessageType>,
    onCopy: (text: string) => void,
    onErrorActionClick: () => void,
    onInsert: (text: string) => void,
    onMessageClicked: (index: number) => void,
    onRetry: (prompt: string, title: string) => void,
|};

type State = {|
    scrolled: boolean,
|};

/**
 * @internal
 */
@observer
class Messages extends Component<Props, State> {
    state: State = {scrolled: false};

    bottomRef: ?HTMLElement;
    scrollRef: ?HTMLElement;

    setBottomRef = (ref: ?HTMLElement) => {
        this.bottomRef = ref;
    };

    setScrollRef = (ref: ?HTMLElement) => {
        this.scrollRef = ref;
    };

    // the fade below the selected text is only meaningful once an answer actually moves out of view
    handleScroll = () => {
        const scrolled = (this.scrollRef?.scrollTop ?? 0) > 0;

        if (scrolled !== this.state.scrolled) {
            this.setState({scrolled});
        }
    };

    componentDidUpdate(prevProps: Props) {
        const {error, isLoading, messages} = this.props;

        const appeared = messages.length !== prevProps.messages.length
            || isLoading !== prevProps.isLoading
            || !!error !== !!prevProps.error;

        if (appeared) {
            this.scrollToBottom();
        }
    }

    /**
     * The prompt sticks to the bottom of the scroll container, which is why the container is scrolled
     * instead of the last element.
     */
    scrollToBottom = () => {
        let scroller = this.bottomRef?.parentElement;

        while (scroller && scroller.scrollHeight <= scroller.clientHeight) {
            scroller = scroller.parentElement;
        }

        scroller?.scrollTo({top: scroller.scrollHeight, behavior: 'smooth'});
    };

    handleMessageCopyClicked = (text: string) => {
        const {onCopy} = this.props;
        onCopy(text);
    };

    handleMessageRetryClicked = (index: number) => {
        const {onRetry, messages} = this.props;

        onRetry(messages[index].command || '', messages[index].title || '');
    };

    handleMessageInsertClicked = (text: string) => {
        const {onInsert} = this.props;
        onInsert(text);
    };

    handleMessageClicked = (index: number) => {
        const {onMessageClicked} = this.props;
        onMessageClicked(index);
    };

    renderMessages = (messages: Array<MessageType>, offset: number = 0) => {
        // $FlowFixMe
        return messages.map((message: MessageType, index: number) => (
            <Message
                collapsed={index + offset > 0 ? message.collapsed : false}
                command={message.command || ''}
                displayActions={message.displayActions || false}
                expert={message.expert}
                index={index + offset}
                isLoading={this.props.isLoading}
                key={index + offset}
                locale={this.props.locale}
                onClick={index + offset > 0 ? this.handleMessageClicked : undefined}
                onCopy={this.handleMessageCopyClicked}
                onInsert={this.handleMessageInsertClicked}
                onRetry={this.handleMessageRetryClicked}
                text={message.text}
                title={message.title || message.command}
                type={message.type}
            />
        ));
    };

    render() {
        const {
            error,
            messages,
            loader,
            onErrorActionClick,
        } = this.props;

        // $FlowFixMe
        const loaderNode = loader ? <Loader {...loader} /> : undefined;

        return (
            <div className={messagesStyles.messages} onScroll={this.handleScroll} ref={this.setScrollRef}>
                <div
                    className={classNames(
                        messagesStyles.selectedText,
                        {[messagesStyles.scrolled]: this.state.scrolled}
                    )}
                >
                    {this.renderMessages([messages[0]])}
                </div>
                {this.renderMessages(messages.slice(1), 1)}
                {loaderNode}
                {error &&
                    <div className={messagesStyles.error}>
                        <Snackbar
                            actions={[{label: error.actionLabel, onClick: onErrorActionClick}]}
                            icon="su-exclamation-triangle"
                            message={error.message}
                            title={error.title}
                            type="warning"
                        />
                    </div>
                }
                <div ref={this.setBottomRef} />
            </div>
        );
    }
}

export default Messages;
