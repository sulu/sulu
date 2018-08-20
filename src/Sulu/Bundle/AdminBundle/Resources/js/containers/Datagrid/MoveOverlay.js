// @flow
import React from 'react';
import Overlay from '../../components/Overlay';
import Datagrid, {DatagridStore} from '../Datagrid';
import {translate} from '../../utils/Translator';
import moveOverlayStyles from './moveOverlay.scss';

type Props = {
    adapters: Array<string>,
    disabledId: ?string | number,
    loading: boolean,
    onClose: () => void,
    onConfirm: (parentId: string | number) => void,
    open: boolean,
    store: DatagridStore,
};

export default class MoveOverlay extends React.Component<Props> {
    componentDidUpdate(prevProps: Props) {
        const {open, store} = this.props;

        if (open === false && prevProps.open === true) {
            store.clearSelection();
        }
    }

    handleConfirm = () => {
        const {onConfirm, store} = this.props;

        onConfirm(store.selectionIds[0]);
    };

    render() {
        const {adapters, disabledId, loading, onClose, open, store} = this.props;

        return (
            <Overlay
                confirmLoading={loading}
                confirmText="Ok"
                onClose={onClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="large"
                title={translate('sulu_admin.move_overlay_title')}
            >
                <div className={moveOverlayStyles.moveOverlayContent}>
                    <Datagrid
                        adapters={adapters}
                        deletable={false}
                        disabledIds={disabledId ? [disabledId] : undefined}
                        movable={false}
                        searchable={false}
                        store={store}
                    />
                </div>
            </Overlay>
        );
    }
}
