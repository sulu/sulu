// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import ClipboardButton from 'react-clipboard.js';
import downloadListItemStyles from './downloadListItem.scss';

type Props = {
    url: string,
    onClick: (url?: string) => void,
    copyText?: string,
    children: string,
    copyUrlOnClick: boolean,
};

export default @observer class DownloadListItem extends React.Component<Props> {
    static defaultProps = {
        copyUrlOnClick: false,
    };

    @observable copying = false;

    @action copyUrl() {
        this.copying = true;
    }

    handleCopySuccess = () => {
        this.copyUrl();
    };

    handleClick = () => {
        const {
            url,
            onClick,
        } = this.props;

        if (onClick) {
            onClick(url);
        }
    };

    render() {
        const {
            url,
            children,
            copyText,
            copyUrlOnClick,
        } = this.props;
        const itemClass = classNames(
            downloadListItemStyles.item,
            {
                [downloadListItemStyles.copying]: this.copying,
            }
        );
        const content = (
            <span className={downloadListItemStyles.content}>
                {children}
                <span className={downloadListItemStyles.copyText}>
                    {copyText}
                </span>
            </span>
        );

        return (
            <li
                className={itemClass}
                onAnimationEnd={this.handleClick}
            >
                {(copyUrlOnClick)
                    ? <ClipboardButton
                        data-clipboard-text={url}
                        onSuccess={this.handleCopySuccess}
                    >
                        {content}
                    </ClipboardButton>
                    : <button onClick={this.handleClick}>
                        {content}
                    </button>
                }
            </li>
        );
    }
}
