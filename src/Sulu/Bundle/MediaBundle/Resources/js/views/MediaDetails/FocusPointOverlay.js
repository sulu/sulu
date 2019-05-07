// @flow
import React from 'react';
import {action, computed, observable} from 'mobx';
import {observer} from 'mobx-react';
import {Overlay} from 'sulu-admin-bundle/components';
import {ResourceStore} from 'sulu-admin-bundle/stores';
import {translate} from 'sulu-admin-bundle/utils';
import ImageFocusPoint from '../../components/ImageFocusPoint';
import type {Point} from '../../components/ImageFocusPoint';
import focusPointOverlayStyles from './focusPointOverlay.scss';

type Props = {|
    onClose: () => void,
    onConfirm: () => void,
    open: boolean,
    resourceStore: ResourceStore,
|};

export default @observer class FocusPointOverlay extends React.Component<Props> {
    @observable focusPointX: number;
    @observable focusPointY: number;
    @observable resourceStore: ?ResourceStore;

    @computed get confirmDisabled() {
        const {
            resourceStore: {
                data: {
                    focusPointX,
                    focusPointY,
                },
            },
        } = this.props;

        return this.focusPointX === focusPointX && this.focusPointY === focusPointY;
    }

    constructor(props: Props) {
        super(props);

        this.updateFocusPoint();
    }

    @action componentDidUpdate(prevProps: Props) {
        if (!prevProps.open && this.props.open) {
            this.resourceStore = this.props.resourceStore.clone();
            this.updateFocusPoint();
        }

        if (prevProps.open && !this.props.open && this.resourceStore) {
            this.resourceStore.destroy();
            this.resourceStore = undefined;
        }
    }

    @action updateFocusPoint = () => {
        const {resourceStore} = this.props;
        const {focusPointX = 1, focusPointY = 1} = resourceStore.data;

        this.focusPointX = focusPointX;
        this.focusPointY = focusPointY;
    };

    handleClose = () => {
        this.props.onClose();
    };

    handleConfirm = () => {
        const {resourceStore} = this;

        if (!resourceStore) {
            throw new Error('There is no resourceStore defined! This should not happen and is likely a bug.');
        }

        resourceStore.change('focusPointX', this.focusPointX);
        resourceStore.change('focusPointY', this.focusPointY);

        resourceStore.save().then(() => {
            this.props.resourceStore.set('focusPointX', this.focusPointX);
            this.props.resourceStore.set('focusPointY', this.focusPointY);
            this.props.onConfirm();
        });
    };

    @action handleFocusPointChange = (point: Point) => {
        this.focusPointX = point.x;
        this.focusPointY = point.y;
    };

    render() {
        const {open} = this.props;

        return (
            <Overlay
                confirmDisabled={this.confirmDisabled}
                confirmLoading={!!this.resourceStore && this.resourceStore.saving}
                confirmText={translate('sulu_admin.save')}
                onClose={this.handleClose}
                onConfirm={this.handleConfirm}
                open={open}
                size="large"
                title={translate('sulu_media.set_focus_point')}
            >
                <div className={focusPointOverlayStyles.focusPointContainer}>
                    {!!this.resourceStore &&
                        <ImageFocusPoint
                            image={this.resourceStore.data.url}
                            onChange={this.handleFocusPointChange}
                            value={{x: this.focusPointX, y: this.focusPointY}}
                        />
                    }
                </div>
            </Overlay>
        );
    }
}
