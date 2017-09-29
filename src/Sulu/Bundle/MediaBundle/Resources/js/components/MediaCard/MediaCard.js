// @flow
import React from 'react';
import classNames from 'classnames';
import {Icon, Checkbox, CroppedText} from 'sulu-admin-bundle/components';
import mediaCardStyles from './mediaCard.scss';

type Props = {
    id: string | number,
    selected: boolean,
    /** Called when the image at the bottom part of this element was clicked */
    onClick?: (id: string | number) => void,
    /** Called when the header or the checkbox was clicked to select/deselect this item */    
    onSelectionChange?: (id: string | number, selected: boolean) => void,
    /** The title which will be displayed in the header besides the checkbox */
    title: string,
    /** For setting meta information like the file size or extension  */
    meta?: string,
    /** The icon used inside the media overlay */
    icon?: string,
    /** The URL of the presented image */
    imageURL: string,
};

export default class MediaCard extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
    };

    handleClick = () => {
        const {
            id,
            onClick,
        } = this.props;

        if (onClick) {
            onClick(id);
        }
    };

    handleHeaderClick = () => {
        const {
            id,
            selected,
        } = this.props;

        if (this.props.onSelectionChange && id) {
            this.props.onSelectionChange(id, !selected);
        }
    };

    render() {
        const {
            id,
            icon,
            meta,
            title,
            selected,
            imageURL,
        } = this.props;
        const masonryClass = classNames(
            mediaCardStyles.mediaCard,
            {
                [mediaCardStyles.selected]: selected,
            }
        );

        return (
            <div className={masonryClass}>
                <div
                    className={mediaCardStyles.header}
                    onClick={this.handleHeaderClick}
                >
                    <div className={mediaCardStyles.title}>
                        <Checkbox
                            value={id}
                            checked={!!selected}
                            className={mediaCardStyles.checkbox}
                        >
                            <div className={mediaCardStyles.titleText}>
                                <CroppedText>{title}</CroppedText>
                            </div>
                        </Checkbox>
                    </div>
                    <div className={mediaCardStyles.meta}>
                        {meta}
                    </div>
                </div>
                <div
                    className={mediaCardStyles.media}
                    onClick={this.handleClick}
                >
                    <img alt={title} src={imageURL} />
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
