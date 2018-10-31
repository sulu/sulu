// @flow
import React from 'react';
import {autorun, computed, toJS} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import equals from 'fast-deep-equal';
import Dialog from '../../components/Dialog';
import Overlay from '../../components/Overlay';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import {translate} from '../../utils';
import datagridOverlayStyles from './datagridOverlay.scss';
import type {OverlayType} from './types';

type Props = {|
    adapter: string,
    allowActivateForDisabledItems: boolean,
    confirmLoading?: boolean,
    clearSelectionOnClose: boolean,
    datagridStore: DatagridStore,
    disabledIds: Array<string | number>,
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
    overlayType: OverlayType,
    preSelectedItems: Array<Object>,
    reloadOnOpen: boolean,
    title: string,
|};

@observer
export default class DatagridOverlay extends React.Component<Props> {
    static defaultProps = {
        allowActivateForDisabledItems: true,
        clearSelectionOnClose: false,
        disabledIds: [],
        overlayType: 'overlay',
        preSelectedItems: [],
        reloadOnOpen: false,
    };

    updateSelectionDisposer: () => void;

    @computed get preSelectedItems() {
        return this.props.preSelectedItems;
    }

    @computed get datagridStore() {
        return this.props.datagridStore;
    }

    constructor(props: Props) {
        super(props);

        this.updateSelectionDisposer = autorun(this.updateSelection);
    }

    componentDidUpdate(prevProps: Props) {
        const {clearSelectionOnClose, open, reloadOnOpen} = this.props;

        if (!this.datagridStore.loading && reloadOnOpen && prevProps.open === false && open === true) {
            this.datagridStore.reload();
        }

        if (clearSelectionOnClose && prevProps.open === true && open === false) {
            this.datagridStore.clearSelection();
        }
    }

    componentWillUnmount() {
        this.updateSelectionDisposer();
    }

    updateSelection = () => {
        this.datagridStore.clearSelection();
        this.preSelectedItems.forEach((preSelectedItem) => {
            this.datagridStore.select(preSelectedItem);
        });
    };

    handleConfirm = () => {
        this.props.onConfirm();
    };

    render() {
        const {
            adapter,
            allowActivateForDisabledItems,
            confirmLoading,
            disabledIds,
            onClose,
            open,
            overlayType,
            preSelectedItems,
            title,
        } = this.props;

        const datagridContainerClass = classNames(
            datagridOverlayStyles.adapterContainer,
            datagridOverlayStyles[overlayType],
            datagridOverlayStyles[adapter]
        );

        const datagridClass = classNames(
            datagridOverlayStyles.datagrid,
            datagridOverlayStyles['adapter'],
            datagridOverlayStyles[adapter]
        );

        const datagrid = (
            <div className={datagridContainerClass}>
                <div className={datagridClass}>
                    <Datagrid
                        adapters={[adapter]}
                        allowActivateForDisabledItems={allowActivateForDisabledItems}
                        copyable={false}
                        deletable={false}
                        disabledIds={disabledIds}
                        movable={false}
                        orderable={false}
                        searchable={false}
                        store={this.datagridStore}
                    />
                </div>
            </div>
        );

        if (overlayType === 'overlay') {
            return (
                <Overlay
                    confirmDisabled={equals(toJS(preSelectedItems), toJS(this.datagridStore.selections))}
                    confirmLoading={confirmLoading}
                    confirmText={translate('sulu_admin.confirm')}
                    onClose={onClose}
                    onConfirm={this.handleConfirm}
                    open={open}
                    size="large"
                    title={title}
                >
                    {datagrid}
                </Overlay>
            );
        }

        if (overlayType === 'dialog') {
            return (
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmDisabled={equals(toJS(preSelectedItems), toJS(this.datagridStore.selections))}
                    confirmLoading={confirmLoading}
                    confirmText={translate('sulu_admin.confirm')}
                    onCancel={onClose}
                    onConfirm={this.handleConfirm}
                    open={open}
                    size="large"
                    title={title}
                >
                    {datagrid}
                </Dialog>
            );
        }
    }
}
