// @flow
import React from 'react';
import {observable} from 'mobx';
import type {IObservableValue} from 'mobx';
import classNames from 'classnames';
import Overlay from '../../components/Overlay';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import {translate} from '../../utils';
import datagridOverlayStyles from './datagridOverlay.scss';

type Props = {|
    adapter: string,
    allowDisabledActivation: boolean,
    confirmLoading?: boolean,
    disabledIds: Array<string | number>,
    locale?: ?IObservableValue<string>,
    onClose: () => void,
    onConfirm: (selectedItems: Array<Object>) => void,
    open: boolean,
    options?: Object,
    resourceKey: string,
    preSelectedItems: Array<Object>,
    title: string,
|};

export default class DatagridOverlay extends React.Component<Props> {
    datagridStore: DatagridStore;
    page: IObservableValue<number> = observable.box(1);

    static defaultProps = {
        allowDisabledActivation: true,
        disabledIds: [],
        preSelectedItems: [],
    };

    constructor(props: Props) {
        super(props);

        const {locale, options, preSelectedItems, resourceKey} = this.props;
        const observableOptions = {};
        observableOptions.page = this.page;

        if (locale) {
            observableOptions.locale = locale;
        }

        this.datagridStore = new DatagridStore(resourceKey, observableOptions, options);

        preSelectedItems.forEach((preSelectedItem) => {
            this.datagridStore.select(preSelectedItem);
        });
    }

    componentDidUpdate() {
        this.datagridStore.clearSelection();

        this.props.preSelectedItems.forEach((preSelectedItem) => {
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
        const {adapter, allowDisabledActivation, confirmLoading, disabledIds, onClose, open, title} = this.props;

        const datagridContainerClass = classNames(
            datagridOverlayStyles['adapter-container'],
            datagridOverlayStyles[adapter]
        );

        const datagridClass = classNames(
            datagridOverlayStyles.datagrid,
            datagridOverlayStyles['adapter'],
            datagridOverlayStyles[adapter]
        );

        return (
            <Overlay
                confirmLoading={confirmLoading}
                confirmText={translate('sulu_admin.confirm')}
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="large"
                title={title}
            >
                <div className={datagridContainerClass}>
                    <div className={datagridClass}>
                        <Datagrid
                            adapters={[adapter]}
                            allowDisabledActivation={allowDisabledActivation}
                            copyable={false}
                            deletable={false}
                            disabledIds={disabledIds}
                            movable={false}
                            searchable={false}
                            store={this.datagridStore}
                        />
                    </div>
                </div>
            </Overlay>
        );
    }
}
