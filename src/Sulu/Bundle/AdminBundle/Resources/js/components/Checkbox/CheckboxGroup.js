// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Checkbox from './Checkbox';

type Props<T> = {|
    children: ChildrenArray<Element<typeof Checkbox>>,
    className?: string,
    disabled: boolean,
    onChange: (values: Array<T>) => void,
    values: Array<T>,
|};

export default class CheckboxGroup<T: string | number> extends React.PureComponent<Props<T>> {
    static defaultProps = {
        disabled: false,
    };

    handleChange: (checked: boolean, changedValue: ?T) => void = (checked, changedValue) => {
        const {onChange, values} = this.props;

        if (checked && changedValue) {
            onChange([...values, changedValue]);
        } else {
            onChange(values.filter((value) => value !== changedValue));
        }
    };

    render() {
        const {className, disabled, values} = this.props;

        return (
            <div className={className}>
                {React.Children.map(this.props.children, (child) => {
                    return React.cloneElement(child, {
                        checked: values.includes(child.props.value),
                        disabled,
                        onChange: this.handleChange,
                    });
                })}
            </div>
        );
    }
}
