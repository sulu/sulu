// @flow
import React from 'react';
import Icon from '../Icon';
import Cell from './Cell';
import treeStyles from './tree.scss';

type Props = {
    /** A ButtonCell is always associated with a row */
    rowId: string | number,
    icon: string,
    onClick?: (rowId: string | number) => void,
};

export default class ButtonCell extends React.PureComponent<Props> {
    handleClick = () => {
        const {rowId} = this.props;

        if (this.props.onClick) {
            this.props.onClick(rowId);
        }
    };

    render() {
        const {
            icon,
        } = this.props;

        return (
            <Cell className={treeStyles.buttonCell}>
                <button onClick={this.handleClick}>
                    <Icon name={icon} />
                </button>
            </Cell>
        );
    }
}
