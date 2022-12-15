// @flow
import React from 'react';
import Icon from '../Icon';
import folderStyles from './folder.scss';

const FOLDER_ICON = 'su-folder';
const FOLDER_PERMISSION_ICON = 'su-folder-permission';

type Props = {
    hasPermissions: boolean,
    id: string | number,
    info: string,
    onClick?: (id: string | number) => void,
    title: string,
};

export default class Folder extends React.PureComponent<Props> {
    handleClick = () => {
        if (this.props.onClick) {
            this.props.onClick(this.props.id);
        }
    };

    handleKeypress = (event: SyntheticKeyboardEvent<HTMLElement>) => {
        const {onClick, id} = this.props;

        if (!onClick) {
            return;
        }

        if (event.key === 'Enter' || event.key === ' ') {
            event.stopPropagation();
            onClick(id);
        }
    };

    render() {
        const {
            hasPermissions,
            info,
            title,
        } = this.props;

        return (
            <div
                className={folderStyles.folder}
                onClick={this.handleClick}
                onKeyPress={this.handleKeypress}
                role="button"
                tabIndex="0"
            >
                <div className={folderStyles.iconContainer}>
                    <Icon name={hasPermissions ? FOLDER_PERMISSION_ICON : FOLDER_ICON} />
                </div>
                <div className={folderStyles.description}>
                    <h5 className={folderStyles.title}>
                        {title}
                    </h5>
                    <div className={folderStyles.info}>
                        {info}
                    </div>
                </div>
            </div>
        );
    }
}
