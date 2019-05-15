// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Item from './Item';
import Section from './Section';

type Props = {
    children: ChildrenArray<Element<typeof Item>>,
    icon: string,
    onChange: (value: *) => void,
    title?: string,
    value: ?*,
};

export default class SingleItemSection extends React.PureComponent<Props> {
    static defaultProps = {
        icon: 'su-check',
    };

    handleItemClick = (value: *) => {
        this.props.onChange(value);
    };

    cloneChildren = (items: ChildrenArray<Element<typeof Item>>) => {
        const {value, icon} = this.props;

        return React.Children.map(items, (item) => {
            return React.cloneElement(
                item,
                {
                    active: (!!value && value === item.props.value),
                    onClick: this.handleItemClick,
                    icon: icon,
                }
            );
        });
    };

    render() {
        const {
            title,
            children,
        } = this.props;

        return (
            <Section title={title}>
                {this.cloneChildren(children)}
            </Section>
        );
    }
}
