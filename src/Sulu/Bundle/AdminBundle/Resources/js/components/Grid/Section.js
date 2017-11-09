// @flow
import React from 'react';
import type {Element} from 'react';
import type {BaseItemProps} from './types';
import BaseItem from './BaseItem';
import Item from './Item';
import sectionStyles from './section.scss';

type Props = BaseItemProps & {
    children: Element<typeof Item | typeof Section>,
};

export default class Section extends React.PureComponent<Props> {
    static defaultProps = {
        size: 12,
        spaceBefore: 0,
        spaceAfter: 0,
    };

    render() {
        const {
            children,
            ...others
        } = this.props;

        return (
            <BaseItem {...others} className={sectionStyles.section}>
                {children}
            </BaseItem>
        );
    }
}
