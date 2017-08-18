// @flow
import type {ChildrenArray, Element} from 'react';
import React from 'react';
import Row from './Row';

type Props = {
    /** Child nodes of the table body */
    children: ChildrenArray<Element<typeof Row>>,
    /** CSS classes to apply custom styles */
    className?: string,
};

export default class Body extends React.PureComponent<Props> {
    render() {
        const {
            children,
            className,
        } = this.props;

        return (
            <tbody className={className}>
                {children}
            </tbody>
        );
    }
}
