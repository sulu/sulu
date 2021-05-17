// @flow
import React from 'react';
import {computed} from 'mobx';
import {observer} from 'mobx-react';
import HeadingComponent from '../../../components/Heading';
import type {Node} from 'react';
import type {FieldTypeProps} from '../../../types';

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
            <HeadingComponent
                description={this.description}
                icon={this.icon}
                label={this.label}
            >
                {children}
            </HeadingComponent>
        );
    }
}

export default Heading;
