// @flow
import React from 'react';
import type {ChildrenArray} from 'react';
import Radio from './Radio';

type Props = {
    value: string,
    onChange?: () => void,
    className?: string,
    children?: ChildrenArray<*>,
};

export default class RadioGroup extends React.PureComponent<Props> {
    addPropsToRadioChildren(children: ChildrenArray<*>) {
        return React.Children.map(children, (child) => {
            if (child.type === Radio) {
                return React.cloneElement(child, {
                    checked: this.props.value && child.props.value === this.props.value,
                    onChange: this.props.onChange,
                });
            }
            return React.cloneElement(child, {
                children: child.props.children ? this.addPropsToRadioChildren(child.props.children) : null,
            });
        });
    }

    render() {
        return (
            <div className={this.props.className}>
                {this.addPropsToRadioChildren(this.props.children)}
            </div>
        );
    }
}
