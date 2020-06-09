// @flow
import React, {Fragment} from 'react';
import type {Node} from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import Icon from '../../../components/Icon';
import type {FieldTypeProps} from '../../../types';
import headingStyles from './heading.scss';

type Props<T> = {
    ...FieldTypeProps<T>,
    children?: Node,
}

@observer
class Heading extends React.Component<Props<typeof undefined>> {
    @computed get schemaOptions() {
        return this.props.schemaOptions;
    }

    @computed get description() {
        return this.schemaOptions.description?.title;
    }

    @computed get icon() {
        const icon = this.schemaOptions.icon?.value;

        if (icon !== undefined && typeof icon !== 'string') {
            throw new Error('The "icon" schemaOption of the Heading must be a string or undefined!');
        }

        return icon;
    }

    @computed get label() {
        return this.schemaOptions.label?.title;
    }

    render() {
        const {children} = this.props;

        return (
            <Fragment>
                <div className={headingStyles.line}>
                    {this.icon && <Icon className={headingStyles.icon} name={this.icon} />}
                    {this.label && <div className={headingStyles.label}>{this.label}</div>}
                    {children}
                </div>
                {this.description &&
                    <div className={headingStyles.description}>
                        {this.description}
                    </div>
                }
            </Fragment>
        );
    }
}

export default Heading;
