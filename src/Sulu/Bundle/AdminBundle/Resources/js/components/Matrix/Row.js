// @flow
import React from 'react';
import {observer} from 'mobx-react';
import {computed} from 'mobx';
import type {ChildrenArray, Element} from 'react';
import {translate} from '../../utils/index';
import Item from './Item';
import matrixStyles from './matrix.scss';
import type {MatrixRowValue} from './types';

type Props = {|
    children: ChildrenArray<Element<typeof Item>>,
    disabled: boolean,
    name: string,
    onChange?: (name: string, value: {[string]: boolean}) => void,
    title?: string,
    values: MatrixRowValue,
|};

@observer
export default class Row extends React.Component<Props> {
    static defaultProps = {
        disabled: false,
        values: {},
    };

    @computed get allItemsDeactivated(): boolean {
        const {values} = this.props;
        for (const value in values) {
            if (values[value] === true) {
                return false;
            }
        }

        return true;
    }

    handleChange = (itemName: string, value: boolean) => {
        const {
            name,
            onChange,
            values,
        } = this.props;

        if (!onChange) {
            return;
        }

        const newValues = {...values};
        newValues[itemName] = value;

        onChange(name, newValues);
    };

    cloneItems = (originalItems: ChildrenArray<Element<typeof Item>>) => {
        const {disabled, values} = this.props;
        return React.Children.map(originalItems, (item, index) => React.cloneElement(
            item,
            {
                ...item.props,
                disabled: disabled,
                key: `matrix-item-${index}`,
                onChange: this.handleChange,
                value: values[item.props.name],
            }
        ));
    };

    handleAllButtonClick = () => {
        const {
            children,
            name,
            onChange,
        } = this.props;

        if (!onChange) {
            return;
        }

        const newValues = {};
        React.Children.map(children, (child) => {
            newValues[child.props.name] = this.allItemsDeactivated;
        });

        onChange(name, newValues);
    };

    renderAllButton() {
        return (
            <button className={matrixStyles.rowButton} onClick={this.handleAllButtonClick}>
                {translate(this.allItemsDeactivated ? 'sulu_admin.activate_all' : 'sulu_admin.deactivate_all')}
            </button>
        );
    }

    render() {
        const {
            disabled,
            children,
            name,
            title,
        } = this.props;

        return (
            <div className={matrixStyles.row}>
                <div>{title ? title : name}</div>
                <div>
                    {this.cloneItems(children)}
                    {!disabled && this.renderAllButton()}
                </div>
            </div>
        );
    }
}
