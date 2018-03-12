// @flow
import React from 'react';
import {observable} from 'mobx';
import type {IObservableValue} from 'mobx'; // eslint-disable-line import/named
import Overlay from '../../components/Overlay';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import {translate} from '../../utils';
import datagridOverlayStyles from './datagridOverlay.scss';

type Props = {|
    locale?: ?IObservableValue<string>,
    onClose: () => void,
    onConfirm: (selectedItems: Array<Object>) => void,
    open: boolean,
    resourceKey: string,
    preSelectedItems: Array<Object>,
    title: string,
|};

export default class DatagridOverlay extends React.Component<Props> {
    datagridStore: DatagridStore;
    page: IObservableValue<number> = observable(1);

    static defaultProps = {
        preSelectedItems: [],
    };

    componentWillMount() {
        const {locale, preSelectedItems, resourceKey} = this.props;
        const observableOptions = {};
        observableOptions.page = this.page;

        if (locale) {
            observableOptions.locale = locale;
        }

        this.datagridStore = new DatagridStore(resourceKey, observableOptions, {});

        preSelectedItems.forEach((preSelectedItem) => {
            this.datagridStore.select(preSelectedItem);
        });
    }

    componentWillReceiveProps(nextProps: Props) {
        this.datagridStore.clearSelection();

        nextProps.preSelectedItems.forEach((preSelectedItem) => {
            this.datagridStore.select(preSelectedItem);
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
