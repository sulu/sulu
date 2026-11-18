// @flow
/* eslint-disable react/jsx-no-bind */
import React from 'react';
import {render, screen} from '@testing-library/react';
import userEvent from '@testing-library/user-event';
import Router, {Route} from '../../../services/Router';
import ViewOverlay from '../../../containers/ViewOverlay';
import ViewOverlayList from '../ViewOverlayList';

const mockListReload = jest.fn();

jest.mock('../../List', () => {
    const React = require('react');

    const ListMock = React.forwardRef((props, ref) => {
        React.useImperativeHandle(ref, () => ({
            reload: mockListReload,
        }));

        if (props.locale.get() === undefined) {
            props.locale.set('de');
        }

        return (
            <div>
                {props.onItemAdd &&
                    <button onClick={() => props.onItemAdd('parent-id')} type="button">add-item</button>
                }
                {props.onItemClick &&
                    <button onClick={() => props.onItemClick('item-id')} type="button">click-item</button>
                }
            </div>
        );
    });

    ListMock.displayName = 'ListMock';
    (ListMock: any).getDerivedRouteAttributes = jest.fn();

    return ListMock;
});

jest.mock('../../../containers/ViewOverlay', () => {
    const React = require('react');

    return jest.fn((props) => (
        <div data-testid="view-overlay">
            <button onClick={props.onClose} type="button">close-overlay</button>
        </div>
    ));
});

jest.mock('../../../services/Router/Router', () => jest.fn());

jest.mock('../../../utils/Translator', () => ({
    translate: jest.fn((key) => key),
}));

function renderViewOverlayList(options: Object) {
    const route = new Route({
        name: 'test_list',
        options: {
            adapters: ['table'],
            listKey: 'tests',
            resourceKey: 'tests',
            ...options,
        },
        path: '/tests',
        type: 'sulu_admin.view_overlay_list',
    });

    const router = new Router();
    router.route = route;
    router.attributes = {};

    return render(<ViewOverlayList route={route} router={router} />);
}

function getViewOverlayProps() {
    return (ViewOverlay: any).mock.lastCall[0];
}

test('Neither add nor click items without overlay views', () => {
    renderViewOverlayList({});

    expect(screen.queryByText('add-item')).not.toBeInTheDocument();
    expect(screen.queryByText('click-item')).not.toBeInTheDocument();
});

test('Open the edit view with the id and locale of the clicked item in an overlay', async() => {
    renderViewOverlayList({editOverlayTitle: 'app.edit_test', editOverlayView: 'test_edit', overlaySize: 'small'});

    expect(screen.queryByText('add-item')).not.toBeInTheDocument();
    expect(screen.queryByTestId('view-overlay')).not.toBeInTheDocument();

    await userEvent.click(screen.getByText('click-item'));

    expect(getViewOverlayProps()).toEqual(expect.objectContaining({
        attributes: {id: 'item-id', locale: 'de'},
        route: 'test_edit',
        size: 'small',
        title: 'app.edit_test',
    }));
});

test('Open the add view with the locale and the parent in a large overlay', async() => {
    renderViewOverlayList({addOverlayView: 'test_add'});

    expect(screen.queryByText('click-item')).not.toBeInTheDocument();

    await userEvent.click(screen.getByText('add-item'));

    expect(getViewOverlayProps()).toEqual(expect.objectContaining({
        attributes: {locale: 'de', parentId: 'parent-id'},
        route: 'test_add',
        size: 'large',
        title: 'sulu_admin.create',
    }));
});

test('Reload the list when the overlay closes', async() => {
    renderViewOverlayList({editOverlayView: 'test_edit'});

    await userEvent.click(screen.getByText('click-item'));
    await userEvent.click(screen.getByText('close-overlay'));

    expect(screen.queryByTestId('view-overlay')).not.toBeInTheDocument();
    expect(mockListReload).toHaveBeenCalledTimes(1);
});
