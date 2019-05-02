// @flow
import React from 'react';
import {computed, observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import TeaserSelectionComponent from '../../TeaserSelection';
import type {TeaserSelectionValue} from '../../TeaserSelection/types';
import type {FieldTypeProps} from '../../../types';
import userStore from '../../../stores/UserStore';

@observer
export default class TeaserSelection extends React.Component<FieldTypeProps<TeaserSelectionValue>> {
    @computed get locale(): IObservableValue<string> {
        const {formInspector} = this.props;

        return formInspector.locale ? formInspector.locale : observable.box(userStore.contentLocale);
    }

    render() {
        const {onChange, value} = this.props;

        return (
            <TeaserSelectionComponent
                locale={this.locale}
                onChange={onChange}
                value={value === null ? undefined : value}
            />
        );
    }
}
