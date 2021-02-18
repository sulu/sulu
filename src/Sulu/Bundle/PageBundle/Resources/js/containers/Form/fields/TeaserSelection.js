// @flow
import React from 'react';
import {computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import jsonpointer from 'json-pointer';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {userStore} from 'sulu-admin-bundle/stores';
import TeaserSelectionComponent, {teaserProviderRegistry} from '../../TeaserSelection';
import type {TeaserItem, TeaserSelectionValue} from '../../TeaserSelection/types';

@observer
class TeaserSelection extends React.Component<FieldTypeProps<TeaserSelectionValue>> {
    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }

    @computed get webspaceKey(): IObservableValue<?string> {
        const {formInspector} = this.props;

        return observable.box(formInspector.metadataOptions?.webspace);
    }

    handleItemClick = (itemId: string | number, item: ?TeaserItem) => {
        if (!item) {
            return;
        }

        const {router} = this.props;

        const {resultToView, view} = teaserProviderRegistry.get(item.type);

        if (!router || !resultToView || !view) {
            return;
        }

        router.navigate(
            view,
            Object.keys(resultToView).reduce((parameters, resultPath) => {
                parameters[resultToView[resultPath]] = jsonpointer.get(item, '/' + resultPath);
                return parameters;
            }, {})
        );
    };

    render() {
        const {disabled, onChange, schemaOptions = {}, value} = this.props;

        const {
            present_as: {
                value: presentAs = [],
            } = {},
        } = schemaOptions;

        if (!Array.isArray(presentAs)) {
            throw new Error(
                'The "present_as" schemaOption must be an array, but received ' + typeof presentAs + '!'
            );
        }

        const presentations = presentAs.map((presentation) => {
            const {name, title} = presentation;

            if (!name) {
                throw new Error('Every presentation in the "present_as" schema Option must contain a name');
            }

            if (!title) {
                throw new Error('Every presentation in the "present_as" schema Option must contain a title');
            }

            return {
                label: title.toString(),
                value: name.toString(),
            };
        });

        return (
            <TeaserSelectionComponent
                disabled={disabled === null ? undefined : disabled}
                locale={this.locale}
                onChange={onChange}
                onItemClick={this.handleItemClick}
                presentations={presentations.length > 0 ? presentations : undefined}
                value={value === null ? undefined : value}
                webspaceKey={this.webspaceKey}
            />
        );
    }
}

export default TeaserSelection;
