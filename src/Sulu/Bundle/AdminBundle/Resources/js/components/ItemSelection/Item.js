// @flow
import React from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import itemStyles from './item.scss';

const DRAG_ICON = 'ellipsis-v';
const REMOVE_ICON = 'times';

type Props = {
    id: string | number,
    children: Node,
    onRemove: (id: string | number) => void,
};

export default class Item extends React.PureComponent<Props> {
    handleRemove = () => {
        this.props.onRemove(this.props.id);
    };

    render() {
        const {children} = this.props;

        return (
            <div className={itemStyles.item}>
                <button className={itemStyles.dragButton}>
                    <Icon name={DRAG_ICON} />
                </button>
                <div className={itemStyles.content}>
                    {children}
                </div>
                <button
                    className={itemStyles.removeButton}
                    onClick={this.handleRemove}
                >
                    <Icon name={REMOVE_ICON} />
                </button>
            </div>
        );
    }
}
