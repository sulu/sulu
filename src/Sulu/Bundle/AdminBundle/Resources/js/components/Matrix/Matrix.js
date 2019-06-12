// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import classNames from 'classnames';
import Row from './Row';
import Item from './Item';
import type {MatrixValues} from './types';
import matrixStyles from './matrix.scss';

type Props = {|
    children: ChildrenArray<Element<typeof Row>>,
    disabled: boolean,
    onChange: (value: MatrixValues) => void,
    values: MatrixValues,
|};

export default class Matrix extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
        values: {},
    };

    static Row = Row;

    static Item = Item;

    handleChange = (rowName: string, rowValues: {[string]: boolean}) => {
        const {
            onChange,
            values,
        } = this.props;

        const newValues = {...values};
        newValues[rowName] = rowValues;

        onChange(newValues);
    };

    cloneRows = (originalRows: ChildrenArray<Element<typeof Row>>) => {
        const {disabled, values} = this.props;
        return React.Children.map(originalRows, (row, index) => React.cloneElement(
            row,
            {
                ...row.props,
                disabled: disabled,
                key: `matrix-row-${index}`,
                onChange: this.handleChange,
                values: values[row.props.name],
            }
        ));
    };

    render() {
        const {
            children,
            disabled,
        } = this.props;

        const matrixClass = classNames(
            matrixStyles.matrix,
            {
                [matrixStyles.disabled]: disabled,
            }
        );

        return (
            <table className={matrixClass}>
                <tbody>
                    {this.cloneRows(children)}
                </tbody>
            </table>
        );
    }
}
