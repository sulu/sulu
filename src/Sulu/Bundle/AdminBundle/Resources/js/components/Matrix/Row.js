// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Item from './Item';
import matrixStyles from './matrix.scss';

type Props = {
    children: ChildrenArray<Element<typeof Item>>,
    name: string,
    onChange: (name: string, value: {[string]: boolean}) => void,
    values: {[string]: boolean},
};

export default class Row extends React.PureComponent<Props> {
    handleChange = (itemName: string, value: boolean) => {
        const {
            name,
            onChange,
            values,
        } = this.props;

        values[itemName] = value;

        onChange(name, values);
    };

    cloneItems = (originalItems: ChildrenArray<Element<typeof Item>>) => {
        const values = this.props.values;
        return React.Children.map(originalItems, (item, index) => React.cloneElement(
            item,
            {
                ...item.props,
                key: `matrix-item-${index}`,
                onChange: this.handleChange,
                value: values[item.props.name],
            }
        ));
    };

    render() {
        const {
            children,
            name,
        } = this.props;

        return (
            <div className={matrixStyles.row}>
                <div>{name}</div>
                <div>{this.cloneItems(children)}</div>
            </div>
        );
    }
}
