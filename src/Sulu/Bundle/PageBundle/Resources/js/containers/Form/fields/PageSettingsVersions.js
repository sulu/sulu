// @flow
import React from 'react';
import {observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import {observer} from 'mobx-react';
import {List, ListStore} from 'sulu-admin-bundle/containers';
import type {FieldTypeProps} from 'sulu-admin-bundle/types';

type Props = FieldTypeProps<void>;

@observer
export default class PageSettingsVersions extends React.Component<Props> {
    listStore: ListStore;
    @observable page: IObservableValue<number> = observable.box(1);

    constructor(props: Props) {
        super(props);

        const {formInspector} = this.props;

        this.listStore = new ListStore(
            'page_versions',
            'page_versions',
            'page_versions',
            {locale: formInspector.locale, page: this.page},
            {id: formInspector.id, webspace: formInspector.options.webspace}
        );
    }

    render() {
        return (
            <List
                adapters={['table']}
                searchable={false}
                selectable={false}
                store={this.listStore}
            />
        );
    }
}
