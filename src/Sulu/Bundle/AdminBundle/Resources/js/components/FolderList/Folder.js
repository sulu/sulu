// @flow
import React from 'react';
import Icon from '../Icon';
import folderStyles from './folder.scss';

const FOLDER_ICON = 'su-folder';

type Props = {
    /** The id which will be passed as an argument inside the onClick handler */
    id: string | number,
    /** The subtext underneath the title */
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

    render() {
        const {
            info,
            title,
        } = this.props;

        return (
            <div
                className={folderStyles.folder}
                onClick={this.handleClick}
                role="button"
                tabIndex="0"
            >
                <div className={folderStyles.iconContainer}>
                    <Icon name={FOLDER_ICON} />
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
