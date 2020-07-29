// @flow
import React, {Fragment} from 'react';
import type {Node} from 'react';
import Icon from '../Icon';
import headingStyles from './heading.scss';

type Props = {|
    children?: Node,
    description?: ?string,
    icon?: ?string,
    label?: ?string,
|};

export default class Heading extends React.Component<Props> {
    render() {
        const {children, description, icon, label} = this.props;
        return (
            <Fragment>
                <div className={headingStyles.line}>
                    {icon && <Icon className={headingStyles.icon} name={icon} />}
                    {label && <div className={headingStyles.label}>{label}</div>}
                    {children}
                </div>
                {description &&
                    <div className={headingStyles.description}>
                        {description}
                    </div>
                }
            </Fragment>
        );
    }
}
