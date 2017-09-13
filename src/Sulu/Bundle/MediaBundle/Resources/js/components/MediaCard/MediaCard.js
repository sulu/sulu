// @flow
import React from 'react';
import type {Node} from 'react';
import {Icon, Checkbox} from 'sulu-admin-bundle';
import mediaCardStyles from './mediaCard.scss';

type Props = {
    children: Node,
    id: string | number,
    selected: boolean,
    onClick?: (id: string | number) => void,
    onSelectionChange?: (id: string | number, selected: boolean) => void,
    /** The title which will be displayed in the header besides the checkbox */
    mediaTitle: string,
    /** For setting meta information like the file size or extension  */
    metaInfo?: string,
    /** The icon used inside the media overlay */
    icon?: string,
};

export default class MediaCard extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
    };

    handleClick = () => {
        const {id} = this.props;

        if (this.props.onClick) {
            this.props.onClick(id);
        }
    };

    handleSelectionChange = (checked: boolean, id?: string | number) => {
        if (this.props.onSelectionChange && id) {
            this.props.onSelectionChange(id, checked);
        }
    };

    handleHeaderClick = () => {
        const {id, selected} = this.props;

        this.handleSelectionChange(!selected, id);
    };

    render() {
        const {
            id,
            icon,
            selected,
            metaInfo,
            children,
            mediaTitle,
        } = this.props;
        const masonryClass = classNames(
            mediaCardStyles.container,
            {
                [mediaCardStyles.selected]: selected,
            }
        );

        return (
            <div className={masonryClass}>
                <div className={mediaCardStyles.header}>
                    <div
                        className={mediaCardStyles.headerClickArea}
                        onClick={this.handleHeaderClick} />
                    <div className={mediaCardStyles.mediaTitle}>
                        <Checkbox
                            value={id}
                            checked={selected}
                            onChange={this.handleSelectionChange}
                            className={mediaCardStyles.checkbox}>
                            <div className={mediaCardStyles.mediaTitleText}>
                                {mediaTitle}
                            </div>
                        </Checkbox>
                    </div>
                    <div className={mediaCardStyles.metaInfo}>
                        {metaInfo}
                    </div>
                </div>
                <div
                    className={mediaCardStyles.media}
                    onClick={this.handleClick}
                >
                    {children}
                    <div className={mediaCardStyles.mediaOverlay}>
                        {!!icon &&
                            <Icon name={icon} className={mediaCardStyles.mediaIcon} />
                        }
                    </div>
                </div>
            </div>
        );
    }
}
