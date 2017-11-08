// @flow
import React from 'react';
import type {BaseItemProps} from './types';
import BaseItem from './BaseItem';
import sectionStyles from './section.scss';

type Props = BaseItemProps;

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
