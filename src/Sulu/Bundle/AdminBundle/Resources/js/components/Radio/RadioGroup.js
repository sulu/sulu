// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Radio from './Radio';

type Props = {|
    children: ChildrenArray<Element<typeof Radio>>,
    className?: string,
    disabled: boolean,
    onChange?: (value: ?string | number) => void,
    value: string,
|};

export default class RadioGroup extends React.PureComponent<Props> {
    static defaultProps = {
        disabled: false,
    };

    render() {
        return (
            <div className={this.props.className}>
                {React.Children.map(this.props.children, (child) => {
                    return React.cloneElement(child, {
                        checked: !!this.props.value && child.props.value === this.props.value,
                        disabled: this.props.disabled,
                        onChange: this.props.onChange,
                    });
                })}
            </div>
        );
    }
}
