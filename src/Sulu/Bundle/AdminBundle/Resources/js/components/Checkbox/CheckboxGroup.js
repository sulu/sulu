// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Checkbox from './Checkbox';

type Props = {|
    children: ChildrenArray<Element<typeof Checkbox>>,
    className?: string,
    disabled: boolean,
    onChange: (values: Array<string | number>) => void,
    values: Array<string | number>,
|};

export default class CheckboxGroup extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
    };

    handleChange = (checked: boolean, changedValue: ?string | number) => {
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
                        checked: !!values && values.includes(child.props.value),
                        disabled,
                        onChange: this.handleChange,
                    });
                })}
            </div>
        );
    }
}
