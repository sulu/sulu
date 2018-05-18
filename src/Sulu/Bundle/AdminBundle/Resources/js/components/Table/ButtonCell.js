// @flow
import React from 'react';
import Icon from '../Icon';
import Cell from './Cell';
import tableStyles from './table.scss';

type Props = {
    /** A ButtonCell is always associated with a row */
    icon: string,
    onClick?: (rowId: string | number) => void,
    rowId: string | number,
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
            <Cell className={tableStyles.buttonCell}>
                <button onClick={this.handleClick}>
                    <Icon name={icon} />
                </button>
            </Cell>
        );
    }
}
