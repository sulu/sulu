// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import ClipboardButton from 'react-clipboard.js';
import downloadListItemStyles from './downloadListItem.scss';

type Props = {
    url: string,
    onCopy?: () => void,
    onClick?: (url: string) => void,
    copyText?: string,
    children: string,
};

@observer
export default class DownloadListItem extends React.PureComponent<Props> {
    @observable copying = false;

    @action copyUrl() {
        this.copying = true;
    }

    handleCopySuccess = () => {
        this.copyUrl();
    };

    handleCopyAnimationEnd = () => {
        const {onCopy} = this.props;

        if (onCopy) {
            onCopy();
        }
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
            onClick,
            children,
            copyText,
        } = this.props;
        const itemClass = classNames(
            downloadListItemStyles.item,
            {
                [downloadListItemStyles.copying]: this.copying,
            }
        );
        const itemContent = (
            <span className={downloadListItemStyles.itemContent}>
                {children}
                <span className={downloadListItemStyles.copyText}>
                    {copyText}
                </span>
            </span>
        );

        return (
            <li
                className={itemClass}
                onAnimationEnd={this.handleCopyAnimationEnd}
            >
                {(!onClick)
                    ? <ClipboardButton
                        onSuccess={this.handleCopySuccess}
                        data-clipboard-text={url}
                    >
                        {itemContent}
                    </ClipboardButton>
                    : <button onClick={this.handleClick}>
                        {itemContent}
                    </button>
                }
            </li>
        );
    }
}
