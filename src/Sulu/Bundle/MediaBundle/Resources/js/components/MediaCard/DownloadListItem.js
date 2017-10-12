// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import classNames from 'classnames';
import ClipboardButton from 'react-clipboard.js';
import downloadListItemStyles from './downloadListItem.scss';

type Props = {
    url: string,
    onCopy: () => void,
    copyText: string,
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
        this.props.onCopy();
    };

    render() {
        const {
            url,
            children,
            copyText,
        } = this.props;
        const itemClass = classNames(
            downloadListItemStyles.item,
            {
                [downloadListItemStyles.copying]: this.copying,
            }
        );

        return (
            <li
                className={itemClass}
                onAnimationEnd={this.handleCopyAnimationEnd}
            >
                <ClipboardButton
                    onSuccess={this.handleCopySuccess}
                    data-clipboard-text={url}
                >
                    <span className={downloadListItemStyles.itemContent}>
                        {children}
                        <span className={downloadListItemStyles.copyText}>
                            {copyText}
                        </span>
                    </span>
                </ClipboardButton>
            </li>
        );
    }
}
