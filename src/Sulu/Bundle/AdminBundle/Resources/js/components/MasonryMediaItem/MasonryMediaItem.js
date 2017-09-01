// @flow
import type {Node} from 'react';
import React from 'react';
import classNames from 'classnames';
import Checkbox from '../Checkbox';
import Icon from '../Icon';
import type {MasonryItem as MasonryItemProps} from '../Masonry/types';
import masonryMediaItemStyles from './masonryMediaItem.scss';

type Props = MasonryItemProps & {
    children: Node[],
    /** The title which will be displayed in the header besides the checkbox */
    mediaTitle: string,
    /** For setting meta information like the file size or extension  */
    metaInfo?: string,
    /** The icon used inside the media overlay */
    icon?: string,
};

export default class MasonryMediaItem extends React.PureComponent<Props> {
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
            masonryMediaItemStyles.container,
            {
                [masonryMediaItemStyles.selected]: selected,
            }
        );

        return (
            <div className={masonryClass}>
                <div className={masonryMediaItemStyles.header}>
                    <div
                        className={masonryMediaItemStyles.headerClickArea}
                        onClick={this.handleHeaderClick} />
                    <div className={masonryMediaItemStyles.mediaTitle}>
                        <Checkbox
                            value={id}
                            checked={selected}
                            onChange={this.handleSelectionChange}
                            className={masonryMediaItemStyles.checkbox}>
                            <div className={masonryMediaItemStyles.mediaTitleText}>
                                {mediaTitle}
                            </div>
                        </Checkbox>
                    </div>
                    <div className={masonryMediaItemStyles.metaInfo}>
                        {metaInfo}
                    </div>
                </div>
                <div
                    className={masonryMediaItemStyles.media}
                    onClick={this.handleClick}>
                    {children}
                    <div className={masonryMediaItemStyles.mediaOverlay}>
                        {!!icon &&
                            <Icon name={icon} className={masonryMediaItemStyles.mediaIcon} />
                        }
                    </div>
                </div>
            </div>
        );
    }
}
