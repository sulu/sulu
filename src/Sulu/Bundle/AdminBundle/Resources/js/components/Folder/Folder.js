// @flow
import React from 'react';
import Icon from '../Icon';
import folderStyles from './folder.scss';

const FOLDER_ICON = 'folder-o';

type Props = {
    /** The id which will be passed as an argument inside the onClick handler */
    id: string | number,
    /** The subtext underneath the title */
    meta: string,
    title: string,
    onClick: (id: string | number) => void,
};

export default class Folder extends React.PureComponent<Props> {
    handleClick = () => {
        this.props.onClick(this.props.id);
    };

    render() {
        const {
            meta,
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
                    <div className={folderStyles.meta}>
                        {meta}
                    </div>
                </div>
            </div>
        );
    }
}
