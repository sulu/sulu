// @flow
import React from 'react';
import {observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import Overlay from '../../components/Overlay';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import {translate} from '../../utils';

type Props = {
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
    resourceKey: string,
    title: string,
};

export default class DatagridOverlay extends React.Component<Props> {
    datagridStore: DatagridStore;
    page: IObservableValue<number> = observable(1);

    componentWillMount() {
        const {resourceKey} = this.props;
        this.datagridStore = new DatagridStore(resourceKey, {page: this.page}, {});
    }

    render() {
        const {onClose, onConfirm, open, title} = this.props;

        return (
            <Overlay
                confirmText={translate('sulu_admin.confirm')}
                onClose={onClose}
                onConfirm={onConfirm}
                open={open}
                title={title}
            >
                <Datagrid adapters={['table']} store={this.datagridStore} />
            </Overlay>
        );
    }
}
