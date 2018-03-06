// @flow
import React from 'react';
import {observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import Overlay from '../../components/Overlay';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import {translate} from '../../utils';
import datagridOverlayStyles from './datagridOverlay.scss';

type Props = {
    onClose: () => void,
    onConfirm: (selectedIds: Array<string | number>) => void,
    open: boolean,
    resourceKey: string,
    preSelectedIds: Array<string | number>,
    title: string,
};

export default class DatagridOverlay extends React.Component<Props> {
    datagridStore: DatagridStore;
    page: IObservableValue<number> = observable(1);

    static defaultProps = {
        preSelectedIds: [],
    };

    componentWillMount() {
        const {resourceKey, preSelectedIds} = this.props;
        this.datagridStore = new DatagridStore(resourceKey, {page: this.page}, {});

        preSelectedIds.forEach((preSelectedId) => {
            this.datagridStore.select(preSelectedId);
        });
    }

    componentWillReceiveProps(nextProps: Props) {
        this.datagridStore.clearSelection();

        nextProps.preSelectedIds.forEach((preSelectedId) => {
            this.datagridStore.select(preSelectedId);
        });
    }

    componentWillUnmount() {
        this.datagridStore.destroy();
    }

    handleConfirm = () => {
        this.props.onConfirm(this.datagridStore.selections);
    };

    render() {
        const {onClose, open, title} = this.props;

        return (
            <Overlay
                confirmText={translate('sulu_admin.confirm')}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                title={title}
            >
                <div className={datagridOverlayStyles.datagrid}>
                    <Datagrid adapters={['table']} store={this.datagridStore} />
                </div>
            </Overlay>
        );
    }
}
