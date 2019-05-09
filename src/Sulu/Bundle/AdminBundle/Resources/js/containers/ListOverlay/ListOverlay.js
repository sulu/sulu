// @flow
import React from 'react';
import {action, autorun, computed, toJS} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import equals from 'fast-deep-equal';
import Dialog from '../../components/Dialog';
import Overlay from '../../components/Overlay';
import List from '../../containers/List';
import ListStore from '../../containers/List/stores/ListStore';
import {translate} from '../../utils';
import listOverlayStyles from './listOverlay.scss';
import type {OverlayType} from './types';

type Props = {|
    adapter: string,
    allowActivateForDisabledItems: boolean,
    confirmLoading?: boolean,
    clearSelectionOnClose: boolean,
    listStore: ListStore,
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
class ListOverlay extends React.Component<Props> {
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

    @computed get listStore() {
        return this.props.listStore;
    }

    constructor(props: Props) {
        super(props);

        this.updateSelectionDisposer = autorun(this.updateSelection);
    }

    @action componentDidUpdate(prevProps: Props) {
        const {clearSelectionOnClose, open, reloadOnOpen} = this.props;

        if (!this.listStore.loading && reloadOnOpen && prevProps.open === false && open === true) {
            this.listStore.reset();
            this.listStore.reload();
        }

        if (clearSelectionOnClose && prevProps.open === true && open === false) {
            this.listStore.clearSelection();
        }
    }

    componentWillUnmount() {
        this.updateSelectionDisposer();
    }

    updateSelection = () => {
        this.listStore.clearSelection();
        this.preSelectedItems.forEach((preSelectedItem) => {
            this.listStore.select(preSelectedItem);
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

        const listContainerClass = classNames(
            listOverlayStyles.adapterContainer,
            listOverlayStyles[overlayType],
            listOverlayStyles[adapter]
        );

        const listClass = classNames(
            listOverlayStyles.list,
            listOverlayStyles['adapter'],
            listOverlayStyles[adapter]
        );

        const list = (
            <div className={listContainerClass}>
                <div className={listClass}>
                    <List
                        adapters={[adapter]}
                        allowActivateForDisabledItems={allowActivateForDisabledItems}
                        copyable={false}
                        deletable={false}
                        disabledIds={disabledIds}
                        movable={false}
                        orderable={false}
                        searchable={true}
                        store={this.listStore}
                    />
                </div>
            </div>
        );

        if (overlayType === 'overlay') {
            return (
                <Overlay
                    confirmDisabled={equals(toJS(preSelectedItems), toJS(this.listStore.selections))}
                    confirmLoading={confirmLoading}
                    confirmText={translate('sulu_admin.confirm')}
                    onClose={onClose}
                    onConfirm={this.handleConfirm}
                    open={open}
                    size="large"
                    title={title}
                >
                    {list}
                </Overlay>
            );
        }

        if (overlayType === 'dialog') {
            return (
                <Dialog
                    cancelText={translate('sulu_admin.cancel')}
                    confirmDisabled={equals(toJS(preSelectedItems), toJS(this.listStore.selections))}
                    confirmLoading={confirmLoading}
                    confirmText={translate('sulu_admin.confirm')}
                    onCancel={onClose}
                    onConfirm={this.handleConfirm}
                    open={open}
                    size="large"
                    title={title}
                >
                    {list}
                </Dialog>
            );
        }

        throw new Error('The "' + overlayType + '" overlayType does not exist in the ListOverlay.');
    }
}

export default ListOverlay;
