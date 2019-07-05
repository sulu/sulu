// @flow
import React from 'react';
import Icon from '../Icon';
import Cell from './Cell';
import tableStyles from './table.scss';

type Props = {|
    icon: string,
    onClick: ?(rowId: string | number, rowIndex: number) => void,
    rowId: string | number,
    rowIndex: number,
|};

export default class ButtonCell extends React.PureComponent<Props> {
    handleClick = () => {
        const {rowIndex, onClick, rowId} = this.props;

        if (onClick) {
            onClick(rowId, rowIndex);
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
