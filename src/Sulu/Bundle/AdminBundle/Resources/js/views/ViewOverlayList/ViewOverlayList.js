// @flow
import React, {Fragment} from 'react';
import {observer} from 'mobx-react';
import {action, observable} from 'mobx';
import {translate} from '../../utils/Translator';
import ViewOverlay from '../../containers/ViewOverlay';
import List from '../List';
import type {ViewProps} from '../../containers/ViewRenderer';
import type {AttributeMap} from '../../services/Router';
import type {IObservableValue} from 'mobx/lib/mobx';
import type {ElementRef} from 'react';

type OpenedOverlay = {|
    attributes: AttributeMap,
    title: string,
    view: string,
|};

@observer
class ViewOverlayList extends React.Component<ViewProps> {
    static getDerivedRouteAttributes = List.getDerivedRouteAttributes;

    locale: IObservableValue<string> = observable.box();
    listRef: ?ElementRef<typeof List>;

    @observable openedOverlay: ?OpenedOverlay;

    @action handleItemAdd = (parentId: ?string | number) => {
        const {addOverlayTitle, addOverlayView} = this.props.route.options;

        this.openedOverlay = {
            attributes: {locale: this.locale.get(), parentId},
            title: translate(addOverlayTitle || 'sulu_admin.create'),
            view: addOverlayView,
        };
    };

    @action handleItemClick = (itemId: string | number) => {
        const {editOverlayTitle, editOverlayView} = this.props.route.options;

        this.openedOverlay = {
            attributes: {id: itemId, locale: this.locale.get()},
            title: translate(editOverlayTitle || 'sulu_admin.edit'),
            view: editOverlayView,
        };
    };

    @action handleOverlayClose = () => {
        this.openedOverlay = undefined;

        if (this.listRef) {
            this.listRef.reload();
        }
    };

    setListRef = (listRef: ?ElementRef<typeof List>) => {
        this.listRef = listRef;
    };

    render() {
        const {addOverlayView, editOverlayView, overlaySize} = this.props.route.options;
        const {openedOverlay} = this;

        return (
            <Fragment>
                <List
                    {...this.props}
                    locale={this.locale}
                    onItemAdd={addOverlayView && this.handleItemAdd}
                    onItemClick={editOverlayView && this.handleItemClick}
                    ref={this.setListRef}
                />
                {!!openedOverlay &&
                    <ViewOverlay
                        attributes={openedOverlay.attributes}
                        onClose={this.handleOverlayClose}
                        route={openedOverlay.view}
                        size={overlaySize || 'small'}
                        title={openedOverlay.title}
                    />
                }
            </Fragment>
        );
    }
}

export default ViewOverlayList;
