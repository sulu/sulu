// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Folder from './Folder';
import folderListStyles from './folderList.scss';

type Props = {
    children: ChildrenArray<Element<typeof Folder>>,
    onFolderClick?: ?(folderId: string | number) => void,
};

export default class FolderList extends React.PureComponent<Props> {
    static Folder = Folder;

    cloneFolders(originalFolders: ChildrenArray<Element<typeof Folder>>) {
        return React.Children.map(originalFolders, (folder) => (
            <li>
                {
                    React.cloneElement(
                        folder,
                        {
                            ...folder.props,
                            onClick: this.handleFolderClick,
                        }
                    )
                }
            </li>
        ));
    }

    handleFolderClick = (folderId: string | number) => {
        if (this.props.onFolderClick) {
            this.props.onFolderClick(folderId);
        }
    };

    render() {
        const {children} = this.props;
        const clonedFolders = this.cloneFolders(children);

        return (
            <ul className={folderListStyles.folderList}>
                {clonedFolders}
            </ul>
        );
    }
}
