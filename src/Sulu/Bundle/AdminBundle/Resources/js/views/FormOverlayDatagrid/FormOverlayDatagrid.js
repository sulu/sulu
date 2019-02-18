// @flow
import React from 'react';
import type {ViewProps} from '../../containers/ViewRenderer';
import Datagrid from '../Datagrid';

export default class FormOverlayDatagrid extends React.Component<ViewProps> {
    static getDerivedRouteAttributes = Datagrid.getDerivedRouteAttributes;

    render() {
        const {
            router: {
                route: {
                    options: {
                        addFormKey,
                        editFormKey,
                    },
                },
            },
        } = this.props;

        return (
            <Datagrid {...this.props} />
        );
    }
}
