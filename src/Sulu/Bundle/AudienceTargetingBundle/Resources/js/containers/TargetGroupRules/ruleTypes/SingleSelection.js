// @flow
import React from 'react';
import {observable} from 'mobx';
import {SingleSelection as SingleSelectionComponent} from 'sulu-admin-bundle/containers';
import userStore from 'sulu-admin-bundle/stores/userStore';
import type {RuleTypeProps} from '../types';

export default class SingleSelection extends React.Component<RuleTypeProps> {
    handleChange = (id: ?string | number) => {
        const {
            onChange,
            options: {
                name,
            },
        } = this.props;

        onChange({[name]: id});
    };

    render() {
        const {
            options: {
                adapter,
                displayProperties,
                emptyText,
                icon,
                name,
                overlayTitle,
                resourceKey,
            },
            value,
        } = this.props;

        return (
            <SingleSelectionComponent
                adapter={adapter}
                displayProperties={displayProperties}
                emptyText={emptyText}
                icon={icon}
                listKey={resourceKey}
                locale={observable.box(userStore.contentLocale)}
                onChange={this.handleChange}
                overlayTitle={overlayTitle}
                resourceKey={resourceKey}
                value={value[name]}
            />
        );
    }
}
