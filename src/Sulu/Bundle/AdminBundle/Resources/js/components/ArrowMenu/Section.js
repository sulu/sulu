// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import sectionStyles from './section.scss';

type Props = {
    children?: ChildrenArray<Element<*>>,
    title?: string,
};

export default class Section extends React.PureComponent<Props> {
    render() {
        const {
            children,
            title,
        } = this.props;

        return (
            <div className={sectionStyles.section}>
                {title &&
                    <div className={sectionStyles.title}>{title}</div>
                }
                <div className={sectionStyles.children}>
                    {children}
                </div>
            </div>
        );
    }
}
