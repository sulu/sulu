// @flow
import React from 'react';
import {computed, isArrayLike, observable} from 'mobx';
import {observer} from 'mobx-react';
import jsonpointer from 'json-pointer';
import {userStore} from 'sulu-admin-bundle/stores';
import TeaserSelectionComponent, {teaserProviderRegistry} from '../../TeaserSelection';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {TeaserItem, TeaserSelectionValue} from '../../TeaserSelection/types';

@observer
class TeaserSelection extends React.Component<FieldTypeProps<TeaserSelectionValue>> {
    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
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

    handleTeaserSelectionChange = (value: TeaserSelectionValue) => {
        const {onChange, onFinish} = this.props;

        onChange(value);
        onFinish();
    };

    render() {
        const {disabled, schemaOptions = {}, value} = this.props;

        const {
            present_as: {
                value: presentAs = [],
            } = {},
        } = schemaOptions;

        if (!isArrayLike(presentAs)) {
            throw new Error(
                'The "present_as" schemaOption must be an array, but received ' + typeof presentAs + '!'
            );
        }

        // $FlowFixMe: flow does not recognize that isArrayLike(value) means that value is an array
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
                onChange={this.handleTeaserSelectionChange}
                onItemClick={this.handleItemClick}
                presentations={presentations.length > 0 ? presentations : undefined}
                value={value === null ? undefined : value}
            />
        );
    }
}

export default TeaserSelection;
