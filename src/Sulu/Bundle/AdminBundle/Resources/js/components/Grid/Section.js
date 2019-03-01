// @flow
import React from 'react';
import type {Element} from 'react';
import classNames from 'classnames';
import type {BaseItemProps} from './types';
import BaseItem from './BaseItem';
import Item from './Item';
import sectionStyles from './section.scss';

type Props = BaseItemProps & {
    children: Element<typeof Item | typeof Section>,
    className?: string,
};

export default class Section extends React.PureComponent<Props> {
    static defaultProps = {
        colSpan: 12,
        spaceAfter: 0,
        spaceBefore: 0,
    };

    render() {
        const {
            children,
            className,
            ...others
        } = this.props;

        const sectionClass = classNames([
            sectionStyles.section,
            className,
        ]);

        return (
            <BaseItem {...others} className={sectionClass}>
                {children}
            </BaseItem>
        );
    }
}
