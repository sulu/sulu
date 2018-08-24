// @flow
import React from 'react';
import {toJS} from 'mobx';
import {observer} from 'mobx-react';
import classNames from 'classnames';
import equals from 'fast-deep-equal';
import Overlay from '../../components/Overlay';
import Datagrid from '../../containers/Datagrid';
import DatagridStore from '../../containers/Datagrid/stores/DatagridStore';
import {translate} from '../../utils';
import datagridOverlayStyles from './datagridOverlay.scss';

type Props = {|
    adapter: string,
    allowDisabledActivation: boolean,
    confirmLoading?: boolean,
    datagridStore: DatagridStore,
    disabledIds: Array<string | number>,
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
    preSelectedItems: Array<Object>,
    title: string,
|};

@observer
export default class DatagridOverlay extends React.Component<Props> {
    static defaultProps = {
        allowDisabledActivation: true,
        disabledIds: [],
        preSelectedItems: [],
    };

    componentDidUpdate(prevProps: Props) {
        const {datagridStore, open, preSelectedItems} = this.props;
        if (prevProps.open === true && open === false) {
            datagridStore.clearSelection();
        }

        if (!equals(toJS(prevProps.preSelectedItems), toJS(preSelectedItems))) {
            datagridStore.clearSelection();
            preSelectedItems.forEach((preSelectedItem) => {
                datagridStore.select(preSelectedItem);
            });
        }
    }

    handleConfirm = () => {
        this.props.onConfirm();
    };

    render() {
        const {
            adapter,
            allowDisabledActivation,
            confirmLoading,
            disabledIds,
            onClose,
            open,
            preSelectedItems,
            datagridStore,
            title,
        } = this.props;

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
                confirmDisabled={equals(toJS(preSelectedItems), toJS(datagridStore.selections))}
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
                            store={datagridStore}
                        />
                    </div>
                </div>
            </Overlay>
        );
    }
}
