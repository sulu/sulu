// @flow
import type {ElementRef} from 'react';
import React from 'react';
import Checkbox from '../Checkbox';
import type {MasonryItem as MasonryItemProps} from '../Masonry/types';
import masonryMediaItemStyles from './masonryMediaItem.scss';

type Props = MasonryItemProps & {
    children: ElementRef<'img'>,
    /** The title which will be displayed in the header besides the checkbox */
    mediaTitle: string,
    /** For setting meta information like the file size or extension  */
    metaInfo?: string,
};

export default class MasonryMediaItem extends React.PureComponent<Props> {
    static defaultProps = {
        selected: false,
    };

    handleSelectionChange = (checked: boolean, id?: string | number) => {
        if (this.props.onSelectionChange && id) {
            this.props.onSelectionChange(id, checked);
        }
    };

    render() {
        const {
            id,
            selected,
            metaInfo,
            children,
            mediaTitle,
        } = this.props;

        return (
            <div className={masonryMediaItemStyles.container}>
                <div className={masonryMediaItemStyles.header}>
                    <div className={masonryMediaItemStyles.title}>
                        <Checkbox
                            value={id}
                            checked={selected}
                            onChange={this.handleSelectionChange}>
                            {mediaTitle}
                        </Checkbox>
                        <div className={masonryMediaItemStyles.meta}>
                            {metaInfo}
                        </div>
                    </div>
                </div>
                <div className={masonryMediaItemStyles.image}>
                    {children}
                </div>
            </div>
        );
    }
}
