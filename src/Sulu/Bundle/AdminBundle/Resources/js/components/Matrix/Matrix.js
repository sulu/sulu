// @flow
import React from 'react';
import classNames from 'classnames';
import Row from './Row';
import Item from './Item';
import matrixStyles from './matrix.scss';
import type {MatrixValues} from './types';
import type {ChildrenArray, Element} from 'react';

type Props = {|
    children: ChildrenArray<Element<typeof Row>>,
    className?: string,
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
                disabled,
                key: `matrix-row-${index}`,
                onChange: this.handleChange,
                values: values.hasOwnProperty(row.props.name) ? values[row.props.name] : {},
            }
        ));
    };

    render() {
        const {
            children,
            className,
            disabled,
        } = this.props;

        const matrixClass = classNames(
            matrixStyles.matrix,
            className,
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
