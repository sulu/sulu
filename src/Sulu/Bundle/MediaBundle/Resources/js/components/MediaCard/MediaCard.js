// @flow
import React from 'react';
import type {Node} from 'react';
import classNames from 'classnames';
import {Icon, Checkbox} from 'sulu-admin-bundle/components';
import mediaCardStyles from './mediaCard.scss';

type Props = {
    children: Node,
    id: string | number,
    selected: boolean,
    onClick?: (id: string | number) => void,
    onSelectionChange?: (id: string | number, selected: boolean) => void,
    /** The title which will be displayed in the header besides the checkbox */
    title: string,
    /** For setting meta information like the file size or extension  */
    meta?: string,
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
            meta,
            children,
            title,
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
                            checked={selected}
                            useLabel={false}
                            className={mediaCardStyles.checkbox}
                        >
                            <div className={mediaCardStyles.titleText}>
                                {title}
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
