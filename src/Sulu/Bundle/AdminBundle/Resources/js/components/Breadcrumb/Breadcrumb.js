// @flow
import React from 'react';
import type {ChildrenArray, Element} from 'react';
import Icon from '../Icon';
import Crumb from './Crumb';
import breadcrumbStyles from './breadcrumb.scss';

const ARROW_RIGHT = 'chevron-right';

type Props = {
    children: ChildrenArray<Element<typeof Crumb>>,
};

export default class Breadcrumb extends React.PureComponent<Props> {
    static Crumb = Crumb;

    render() {
        const {
            children,
        } = this.props;

        return (
            <ul className={breadcrumbStyles.breadcrumb}>
                {React.Children.map(children, (crumb, index) => (
                    <li>
                        {crumb}
                        {(index !== React.Children.count(children) - 1) &&
                            <Icon name={ARROW_RIGHT} className={breadcrumbStyles.arrow} />
                        }
                    </li>
                ))}
            </ul>
        );
    }
}
