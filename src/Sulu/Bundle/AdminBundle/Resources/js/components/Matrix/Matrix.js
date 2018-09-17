// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import Row from './Row';
import Item from './Item';
import type {MatrixValues} from './types';
import matrixStyles from './matrix.scss';

type Props = {
    children: ChildrenArray<Element<typeof Row>>,
    title: string,
    onChange: (value: MatrixValues) => void,
    values: MatrixValues,
};

export default class Matrix extends React.PureComponent<Props> {
    static defaultProps = {
        values: {},
    };

    static Row = Row;

    static Item = Item;

    handleChange = (rowName: string, rowValues: {[string]: boolean}) => {
        const {
            onChange,
            values,
        } = this.props;

        const newValues = Object.assign({}, values);
        newValues[rowName] = rowValues;

        onChange(newValues);
    };

    cloneRows = (originalRows: ChildrenArray<Element<typeof Row>>) => {
        const values = this.props.values;
        return React.Children.map(originalRows, (row, index) => React.cloneElement(
            row,
            {
                ...row.props,
                key: `matrix-row-${index}`,
                onChange: this.handleChange,
                values: values[row.props.name],
            }
        ));
    };

    render() {
        const {
            children,
            title,
        } = this.props;

        return (
            <div>
                <div className={matrixStyles.title}>{title}</div>
                <div className={matrixStyles.matrix}>
                    {this.cloneRows(children)}
                </div>
            </div>
        );
    }
}
