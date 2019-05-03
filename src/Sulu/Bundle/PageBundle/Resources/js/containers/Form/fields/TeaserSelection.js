// @flow
import React from 'react';
import {computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';
import {userStore} from 'sulu-admin-bundle/stores';
import TeaserSelectionComponent from '../../TeaserSelection';
import type {TeaserSelectionValue} from '../../TeaserSelection/types';

@observer
export default class TeaserSelection extends React.Component<FieldTypeProps<TeaserSelectionValue>> {
    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }

    render() {
        const {disabled, onChange, value} = this.props;

        return (
            <TeaserSelectionComponent
                disabled={disabled === null ? undefined : disabled}
                locale={this.locale}
                onChange={onChange}
                value={value === null ? undefined : value}
            />
        );
    }
}
